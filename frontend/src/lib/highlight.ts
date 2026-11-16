export type TextPart = { text: string; isMatch: boolean }

/**
 * Splits `text` around the first place the query appears, ignoring case and accents, so the match can
 * be shown in bold: ("Pokémon Scarlet", "pokemon") → [{"Pokémon", match}, {" Scarlet"}].
 * A typo-tolerant match ("wticher") has no exact place in the name, so nothing is highlighted then.
 */
export function splitForHighlight(text: string, query: string): TextPart[] {
  const characters = Array.from(text)
  // Folded character by character, so positions in the folded text are positions in the original.
  const folded = characters.map(foldCharacter)
  const foldedQuery = Array.from(query.trim()).map(foldCharacter).join('')

  if (foldedQuery === '') {
    return [{ text, isMatch: false }]
  }

  for (let start = 0; start < folded.length; start++) {
    let end = start
    let matched = ''
    while (end < folded.length && matched.length < foldedQuery.length) {
      matched += folded[end]
      end++
    }

    if (matched === foldedQuery) {
      return [
        { text: characters.slice(0, start).join(''), isMatch: false },
        { text: characters.slice(start, end).join(''), isMatch: true },
        { text: characters.slice(end).join(''), isMatch: false },
      ].filter((part) => part.text !== '')
    }
  }

  return [{ text, isMatch: false }]
}

function foldCharacter(character: string): string {
  // NFD splits "é" into "e" + a combining accent; the regular expression drops the accent.
  return character.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase()
}
