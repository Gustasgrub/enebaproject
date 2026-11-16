import { Link } from 'react-router'
import { cart, MAX_QUANTITY_PER_LINE, useCartLines } from '../cart/cartStore'
import { useCartOffers, type PricedCartLine } from '../cart/useCartOffers'
import { formatPrice } from '../lib/money'
import { productTitle } from '../lib/productTitle'

export function CartPage() {
  const cartLines = useCartLines()
  const { query, lines, totalCents, hasProblems } = useCartOffers(cartLines)

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-8">
      <title>Cart · anabe</title>
      <h1 className="mb-6 text-3xl font-extrabold">Your cart</h1>

      {cartLines.length === 0 ? (
        <div className="bg-card p-6">
          <p className="mb-3 text-lg font-semibold">Your cart is empty.</p>
          <Link to="/store" className="font-bold text-accent hover:underline">
            Browse games
          </Link>
        </div>
      ) : query.isPending ? (
        <div aria-hidden="true" className="h-40 animate-pulse bg-card" />
      ) : query.isError ? (
        <div className="bg-card p-6">
          <p className="mb-3 font-semibold">Your cart could not be loaded.</p>
          <button type="button" className="bg-accent px-4 py-2 font-bold text-card" onClick={() => void query.refetch()}>
            Try again
          </button>
        </div>
      ) : (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
          <ul className="flex flex-col gap-3">
            {lines.map((line) => (
              <CartLineRow key={line.offerId} line={line} />
            ))}
          </ul>

          <aside className="h-fit bg-panel p-6">
            <p className="flex justify-between text-lg font-bold">
              <span>Total</span>
              <span>{formatPrice(totalCents)}</span>
            </p>
            <p className="mt-1 text-sm text-white/70">Prices and stock are checked again when you pay.</p>
            {hasProblems ? (
              <p className="mt-4 font-semibold text-red-300">Fix the items marked above to continue.</p>
            ) : (
              <Link to="/checkout" className="mt-4 block bg-accent py-3 text-center font-extrabold text-card">
                Proceed to checkout
              </Link>
            )}
          </aside>
        </div>
      )}
    </div>
  )
}

function CartLineRow({ line }: { line: PricedCartLine }) {
  const { offer } = line

  if (!offer || !offer.product) {
    return (
      <li className="flex items-center justify-between gap-4 bg-card p-4">
        <p className="text-white/80">This offer is no longer available.</p>
        <RemoveButton offerId={line.offerId} />
      </li>
    )
  }

  // Never offer a quantity above the stock, but keep the current one selectable so it shows up.
  const maxQuantity = Math.min(MAX_QUANTITY_PER_LINE, Math.max(offer.stock, line.quantity))
  const isOverStock = line.quantity > offer.stock

  return (
    <li className="flex gap-4 bg-card p-4">
      <img src={offer.product.image_url} alt="" className="aspect-[3/4] w-16 shrink-0 object-cover" />
      <div className="flex min-w-0 flex-1 flex-col gap-1">
        <Link to={`/product/${offer.product.slug}`} className="font-bold hover:underline">
          {productTitle(offer.product)}
        </Link>
        <p className="text-sm text-white/70">Sold by {offer.seller.name}</p>
        {isOverStock && (
          <p className="text-sm font-semibold text-red-300">
            {offer.stock === 0 ? 'Out of stock: remove it to continue.' : `Only ${offer.stock} left: lower the quantity.`}
          </p>
        )}
        <div className="mt-2 flex items-center gap-4">
          <label className="flex items-center gap-2 text-sm">
            Qty
            <select
              value={line.quantity}
              onChange={(event) => cart.setQuantity(line.offerId, Number(event.target.value))}
              className="bg-field px-2 py-1 font-bold"
            >
              {Array.from({ length: maxQuantity }, (_, index) => index + 1).map((quantity) => (
                <option key={quantity} value={quantity}>
                  {quantity}
                </option>
              ))}
            </select>
          </label>
          <RemoveButton offerId={line.offerId} />
        </div>
      </div>
      <p className="text-lg font-extrabold whitespace-nowrap">{formatPrice(offer.price_cents * line.quantity)}</p>
    </li>
  )
}

function RemoveButton({ offerId }: { offerId: number }) {
  return (
    <button type="button" className="text-sm font-semibold text-accent hover:underline" onClick={() => cart.remove(offerId)}>
      Remove
    </button>
  )
}
