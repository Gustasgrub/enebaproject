const euroFormat = new Intl.NumberFormat('en-IE', { style: 'currency', currency: 'EUR' })

/** 1999 → "€19.99". Prices travel as integer cents and are only turned into euros for display. */
export function formatPrice(cents: number): string {
  return euroFormat.format(cents / 100)
}
