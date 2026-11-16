import { useSyncExternalStore } from 'react'

// The cart lives in the browser's localStorage, as offer ids and quantities only. Prices and stock are
// always fetched fresh (GET /api/offers) and checked again by the server at checkout, so nothing saved
// here is ever trusted (docs/DECISIONS.md).

export type CartLine = { offerId: number; quantity: number }

export const MAX_QUANTITY_PER_LINE = 10
const STORAGE_KEY = 'anabe-cart'

let lines: CartLine[] = readStoredLines()
const listeners = new Set<() => void>()

function readStoredLines(): CartLine[] {
  try {
    const parsed: unknown = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]')
    return Array.isArray(parsed) ? parsed.filter(isCartLine) : []
  } catch {
    // Storage can be unavailable (private mode) or hold something unreadable: start empty.
    return []
  }
}

function isCartLine(value: unknown): value is CartLine {
  return (
    typeof value === 'object' &&
    value !== null &&
    Number.isInteger((value as CartLine).offerId) &&
    Number.isInteger((value as CartLine).quantity) &&
    (value as CartLine).quantity > 0
  )
}

function save(nextLines: CartLine[]) {
  lines = nextLines
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(lines))
  } catch {
    // Still works for this page view; it just will not survive a reload.
  }
  listeners.forEach((listener) => listener())
}

function clampQuantity(quantity: number): number {
  return Math.min(Math.max(Math.round(quantity), 1), MAX_QUANTITY_PER_LINE)
}

// Another tab changed the cart: pick up its version.
window.addEventListener('storage', (event) => {
  if (event.key === STORAGE_KEY) {
    lines = readStoredLines()
    listeners.forEach((listener) => listener())
  }
})

export const cart = {
  add(offerId: number, quantity = 1) {
    const existing = lines.find((line) => line.offerId === offerId)
    save(
      existing
        ? lines.map((line) => (line.offerId === offerId ? { ...line, quantity: clampQuantity(line.quantity + quantity) } : line))
        : [...lines, { offerId, quantity: clampQuantity(quantity) }],
    )
  },
  setQuantity(offerId: number, quantity: number) {
    save(lines.map((line) => (line.offerId === offerId ? { ...line, quantity: clampQuantity(quantity) } : line)))
  },
  remove(offerId: number) {
    save(lines.filter((line) => line.offerId !== offerId))
  },
  clear() {
    save([])
  },
}

function subscribe(listener: () => void) {
  listeners.add(listener)
  return () => listeners.delete(listener)
}

/** The cart's lines; components re-render when they change, in this tab or another one. */
export function useCartLines(): CartLine[] {
  return useSyncExternalStore(subscribe, () => lines)
}
