import { useQuery } from '@tanstack/react-query'
import { apiGet } from '../api/client'
import type { ListResponse, Offer } from '../api/types'
import type { CartLine } from './cartStore'

export type PricedCartLine = CartLine & {
  /** Undefined when the offer no longer exists. */
  offer: Offer | undefined
}

/**
 * The cart's lines with each offer's current price and stock, fetched from the backend: the cart in
 * localStorage holds only ids and quantities, so prices shown here are always the live ones.
 */
export function useCartOffers(lines: CartLine[]) {
  const ids = lines.map((line) => line.offerId).sort((a, b) => a - b)

  const offers = useQuery({
    queryKey: ['cart-offers', ids],
    queryFn: ({ signal }) => {
      const query = new URLSearchParams(ids.map((id) => ['ids[]', String(id)]))
      return apiGet<ListResponse<Offer>>('/api/offers', query, signal)
    },
    enabled: ids.length > 0,
    // Prices and stock decide what the buyer is about to pay: always fetch fresh.
    staleTime: 0,
  })

  const offersById = new Map((offers.data?.data ?? []).map((offer) => [offer.id, offer]))
  const pricedLines: PricedCartLine[] = lines.map((line) => ({ ...line, offer: offersById.get(line.offerId) }))
  const totalCents = pricedLines.reduce((sum, line) => sum + (line.offer ? line.offer.price_cents * line.quantity : 0), 0)
  // Checkout would refuse these: an offer that disappeared, or more units than its stock.
  const problems = pricedLines.filter((line) => !line.offer || line.quantity > line.offer.stock)

  return { query: offers, lines: pricedLines, totalCents, hasProblems: problems.length > 0 }
}
