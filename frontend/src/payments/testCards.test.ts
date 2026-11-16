import { describe, expect, it } from 'vitest'
import { isValidExpiry, tokenizeTestCard } from './testCards'

describe('tokenizeTestCard', () => {
  it('turns the test cards into tokens, with or without spaces', () => {
    expect(tokenizeTestCard('4242 4242 4242 4242')).toBe('tok_success')
    expect(tokenizeTestCard('4000000000000002')).toBe('tok_declined')
  })

  it('rejects every other card number', () => {
    expect(tokenizeTestCard('5555 5555 5555 4444')).toBeNull()
    expect(tokenizeTestCard('')).toBeNull()
  })
})

describe('isValidExpiry', () => {
  const today = new Date(2026, 10, 17) // November 2026

  it('accepts this month and later', () => {
    expect(isValidExpiry('11/26', today)).toBe(true)
    expect(isValidExpiry('01/30', today)).toBe(true)
  })

  it('rejects past months and malformed dates', () => {
    expect(isValidExpiry('10/26', today)).toBe(false)
    expect(isValidExpiry('13/27', today)).toBe(false)
    expect(isValidExpiry('1127', today)).toBe(false)
  })
})
