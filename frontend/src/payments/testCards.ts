// The fake card form's stand-in for a payment provider's browser library (like Stripe Elements): it
// turns a card into a token, and only the token is sent to our backend. Only the two documented test
// cards are accepted; the card number itself never leaves the browser (docs/DECISIONS.md #9).

export const TEST_CARDS = [
  { number: '4242 4242 4242 4242', token: 'tok_success', outcome: 'Payment succeeds' },
  { number: '4000 0000 0000 0002', token: 'tok_declined', outcome: 'Card is declined' },
] as const

export type PaymentToken = (typeof TEST_CARDS)[number]['token']

/** The token for a test card number (spaces allowed), or null for any other number. */
export function tokenizeTestCard(cardNumber: string): PaymentToken | null {
  const digits = cardNumber.replace(/\D/g, '')
  return TEST_CARDS.find((card) => card.number.replace(/\s/g, '') === digits)?.token ?? null
}

/** "MM/YY" that is this month or later. */
export function isValidExpiry(expiry: string, today = new Date()): boolean {
  const match = /^(\d{2})\s*\/\s*(\d{2})$/.exec(expiry.trim())
  if (!match) return false

  const month = Number(match[1])
  const year = 2000 + Number(match[2])
  if (month < 1 || month > 12) return false

  return year > today.getFullYear() || (year === today.getFullYear() && month >= today.getMonth() + 1)
}
