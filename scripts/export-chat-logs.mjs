// Converts the Claude Code transcripts of this project (JSONL) into readable Markdown for ai-logs/,
// scrubbing personal data and anything that looks like a secret. The assignment asks for the full agent
// chat history; review the output before committing it.
//
// Usage (from the repository root):
//   node scripts/export-chat-logs.mjs                 # the project's two transcript folders
//   node scripts/export-chat-logs.mjs <folder> ...    # other transcript folders
//
// Names to remove are passed in, so they are not written into this public file:
//   EXPORT_SCRUB_NAMES="First Last,Last" node scripts/export-chat-logs.mjs
// Email addresses, the Windows user name in paths, Render ids and key/token formats are removed always.
//
// Kept: every user prompt and every assistant reply in full; tool calls and their results as collapsed
// blocks, shortened (the code they wrote is in the repository). Left out: internal reasoning, harness
// reminders, sub-agent side chains and bookkeeping records.

import { existsSync, mkdirSync, readdirSync, readFileSync, writeFileSync } from 'node:fs'
import { homedir } from 'node:os'
import { basename, join } from 'node:path'

const TOOL_TEXT_LIMIT = 3000
const outputDir = join(process.cwd(), 'ai-logs')
const projectsDir = join(homedir(), '.claude', 'projects')
const homeName = basename(homedir()) // the Windows user name, part of every local path

// Claude Code names a project's transcript folder after its path: the project lived on the OneDrive
// desktop first, then moved to C:\dev (PLAN.md, Section 4).
const defaultFolders = [`c--Users-${homeName}-OneDrive-Desktop-enebaproject`, 'c--dev-enebaproject'].map((name) => join(projectsDir, name))
const folders = process.argv.length > 2 ? process.argv.slice(2) : defaultFolders

// --- Scrubbing ----------------------------------------------------------------------------------

const escapeRegExp = (text) => text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
const namesToScrub = (process.env.EXPORT_SCRUB_NAMES ?? '')
  .split(',')
  .map((name) => name.trim())
  .filter(Boolean)
  // Longest first, so "First Last" is replaced before "Last" alone.
  .sort((a, b) => b.length - a.length)

const replacements = [
  // Secrets first, so nothing below can split them.
  [/base64:[A-Za-z0-9+/]{40,}={0,2}/g, 'base64:<redacted-key>'],
  [/\b\d+\|[A-Za-z0-9]{40}\b/g, '<redacted-api-token>'],
  [/\bsk-[A-Za-z0-9_-]{20,}/g, '<redacted-secret>'],
  [/\bgh[pousr]_[A-Za-z0-9]{30,}/g, '<redacted-github-token>'],
  [/\b(?:usr|tea|srv|dpg|evm|exs)-[a-z0-9]{20}(?:-a)?\b/g, '<render-id>'],
  // Email addresses, except the reserved example domains used for demo data and commit trailers.
  [/[A-Za-z0-9._%+-]+@(?!example\.(?:com|org)\b|anthropic\.com\b)[A-Za-z0-9.-]+\.[A-Za-z]{2,}/g, '<email>'],
  // Personal names, from EXPORT_SCRUB_NAMES.
  ...namesToScrub.map((name) => [new RegExp(escapeRegExp(name), 'gi'), '<name>']),
  // Home folder in its Windows, Git Bash and Claude-folder spellings.
  [new RegExp(`([A-Za-z]:[\\\\/]+Users[\\\\/]+)${homeName}\\b`, 'gi'), '$1<user>'],
  [new RegExp(`(/[a-z]/Users/)${homeName}\\b`, 'gi'), '$1<user>'],
  [new RegExp(`(--Users-)${homeName}\\b`, 'gi'), '$1<user>'],
  // Anywhere else the user name appears on its own, e.g. as the file owner in `ls -l` output.
  [new RegExp(`\\b${escapeRegExp(homeName)}\\b`, 'gi'), '<user>'],
]

function scrub(text) {
  return replacements.reduce((current, [pattern, replacement]) => current.replace(pattern, replacement), text)
}

// --- Conversion ---------------------------------------------------------------------------------

function shorten(text) {
  return text.length > TOOL_TEXT_LIMIT ? `${text.slice(0, TOOL_TEXT_LIMIT)}\n… (${text.length - TOOL_TEXT_LIMIT} more characters)` : text
}

/** Harness context injected into user turns, not something the user typed. */
function withoutHarnessContext(text) {
  return text
    .replace(/<system-reminder>[\s\S]*?<\/system-reminder>/g, '')
    .replace(/<ide_(?:opened_file|selection)>[\s\S]*?<\/ide_(?:opened_file|selection)>/g, '')
    .trim()
}

function fence(text, language = '') {
  const longestRun = Math.max(2, ...[...text.matchAll(/`+/g)].map((match) => match[0].length))
  const ticks = '`'.repeat(longestRun + 1)
  return `${ticks}${language}\n${text}\n${ticks}`
}

function toolResultText(content) {
  if (typeof content === 'string') return content
  if (Array.isArray(content)) {
    return content.map((part) => (part.type === 'text' ? part.text : `[${part.type}]`)).join('\n')
  }
  return ''
}

function renderRecord(record) {
  const content = record.message?.content
  const blocks = typeof content === 'string' ? [{ type: 'text', text: content }] : Array.isArray(content) ? content : []
  const parts = []

  for (const block of blocks) {
    if (block.type === 'text') {
      const text = record.type === 'user' ? withoutHarnessContext(block.text) : block.text.trim()
      if (text) parts.push({ kind: record.type, markdown: text })
    } else if (block.type === 'tool_use') {
      const description = block.input?.description ? `: ${block.input.description}` : ''
      const input = shorten(JSON.stringify(block.input, null, 2))
      parts.push({ kind: 'tool', markdown: `<details><summary>Tool call: ${block.name}${description}</summary>\n\n${fence(input, 'json')}\n\n</details>` })
    } else if (block.type === 'tool_result') {
      const output = shorten(toolResultText(block.content))
      const label = block.is_error ? 'Tool error' : 'Tool result'
      parts.push({ kind: 'tool', markdown: `<details><summary>${label}</summary>\n\n${fence(output)}\n\n</details>` })
    } else if (block.type === 'image') {
      parts.push({ kind: 'tool', markdown: '_(image)_' })
    }
    // thinking / redacted_thinking blocks are internal reasoning and are left out.
  }

  return parts
}

function convertTranscript(file) {
  const records = readFileSync(file, 'utf8')
    .split('\n')
    .filter(Boolean)
    .flatMap((line) => {
      try {
        return [JSON.parse(line)]
      } catch {
        return []
      }
    })

  const title = records.findLast((record) => record.type === 'ai-title')?.aiTitle ?? basename(file, '.jsonl')
  const turns = records.filter((record) => (record.type === 'user' || record.type === 'assistant') && !record.isSidechain)
  const firstTimestamp = turns.find((record) => record.timestamp)?.timestamp ?? ''
  const lastTimestamp = turns.findLast((record) => record.timestamp)?.timestamp ?? ''

  const lines = [
    `# ${title}`,
    '',
    `Claude Code session \`${basename(file, '.jsonl')}\`, ${firstTimestamp.slice(0, 16).replace('T', ' ')} to ${lastTimestamp.slice(0, 16).replace('T', ' ')} UTC.`,
    '',
    '> Exported with `scripts/export-chat-logs.mjs`: prompts and replies are complete; tool calls and results are',
    `> collapsed and shortened to ${TOOL_TEXT_LIMIT} characters; internal reasoning is left out; personal data and`,
    '> secrets are replaced by placeholders such as `<email>`.',
    '',
  ]

  let lastHeading = null
  for (const record of turns) {
    for (const part of renderRecord(record)) {
      const heading = part.kind === 'user' ? '## User' : part.kind === 'assistant' ? '## Claude' : null
      if (heading && heading !== lastHeading) {
        lines.push(heading, '')
        lastHeading = heading
      }
      lines.push(part.markdown, '')
    }
  }

  return { title, firstTimestamp, markdown: scrub(lines.join('\n')) }
}

// --- Main ---------------------------------------------------------------------------------------

mkdirSync(outputDir, { recursive: true })

for (const folder of folders) {
  if (!existsSync(folder)) {
    console.warn(`Skipping missing folder ${scrub(folder)}`)
    continue
  }
  for (const name of readdirSync(folder).filter((entry) => entry.endsWith('.jsonl'))) {
    const { title, firstTimestamp, markdown } = convertTranscript(join(folder, name))
    const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 60)
    const outputFile = join(outputDir, `${firstTimestamp.slice(0, 10)}-${slug}.md`)
    writeFileSync(outputFile, markdown)
    console.log(`Wrote ${basename(outputFile)} (${Math.round(markdown.length / 1024)} KB)`)
  }
}
