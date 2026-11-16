import { describe, expect, it } from 'vitest'
import { splitForHighlight } from './highlight'

describe('splitForHighlight', () => {
  it('marks the matching part, ignoring case', () => {
    expect(splitForHighlight('Cyberpunk 2077', 'CYBER')).toEqual([
      { text: 'Cyber', isMatch: true },
      { text: 'punk 2077', isMatch: false },
    ])
  })

  it('matches an accented name typed without accents, keeping the original letters', () => {
    expect(splitForHighlight('God of War Ragnarök', 'ragnarok')).toEqual([
      { text: 'God of War ', isMatch: false },
      { text: 'Ragnarök', isMatch: true },
    ])
  })

  it('highlights nothing for a typo-tolerant match', () => {
    expect(splitForHighlight('The Witcher 3', 'wticher')).toEqual([{ text: 'The Witcher 3', isMatch: false }])
  })
})
