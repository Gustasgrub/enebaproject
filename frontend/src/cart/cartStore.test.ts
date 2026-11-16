import { afterEach, describe, expect, it } from 'vitest'
import { cart, MAX_QUANTITY_PER_LINE } from './cartStore'

function storedLines() {
  return JSON.parse(localStorage.getItem('anabe-cart') ?? '[]')
}

describe('cart', () => {
  afterEach(() => cart.clear())

  it('adds offers, merging repeated adds of the same offer, and saves them', () => {
    cart.add(5)
    cart.add(5)
    cart.add(9, 3)

    expect(storedLines()).toEqual([
      { offerId: 5, quantity: 2 },
      { offerId: 9, quantity: 3 },
    ])
  })

  it('keeps quantities between 1 and the per-line maximum', () => {
    cart.add(5, 50)
    expect(storedLines()[0].quantity).toBe(MAX_QUANTITY_PER_LINE)

    cart.setQuantity(5, 0)
    expect(storedLines()[0].quantity).toBe(1)
  })

  it('removes a line', () => {
    cart.add(5)
    cart.add(9)
    cart.remove(5)

    expect(storedLines()).toEqual([{ offerId: 9, quantity: 1 }])
  })
})
