import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { Link, useParams } from 'react-router'
import { ApiError, apiGet } from '../api/client'
import type { Offer, ProductDetail } from '../api/types'
import { cart } from '../cart/cartStore'
import { formatPrice } from '../lib/money'
import { productTitle } from '../lib/productTitle'
import { NotFoundPage } from './NotFoundPage'

export function ProductPage() {
  const { slug = '' } = useParams()
  const product = useQuery({
    queryKey: ['product', slug],
    queryFn: ({ signal }) => apiGet<{ data: ProductDetail }>(`/api/products/${encodeURIComponent(slug)}`, undefined, signal),
    select: (response) => response.data,
  })

  if (product.isError && product.error instanceof ApiError && product.error.status === 404) {
    return <NotFoundPage />
  }

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-6">
      {product.isPending ? (
        <div aria-hidden="true" className="grid gap-8 md:grid-cols-[300px_1fr]">
          <div className="aspect-[3/4] animate-pulse bg-card" />
          <div className="space-y-4">
            <div className="h-9 w-3/4 animate-pulse bg-card" />
            <div className="h-24 animate-pulse bg-card" />
          </div>
        </div>
      ) : product.isError ? (
        <div className="bg-card p-6">
          <p className="mb-3 font-semibold">This product could not be loaded.</p>
          <button type="button" className="bg-accent px-4 py-2 font-bold text-card" onClick={() => void product.refetch()}>
            Try again
          </button>
        </div>
      ) : (
        <ProductDetails product={product.data} />
      )}
    </div>
  )
}

function ProductDetails({ product }: { product: ProductDetail }) {
  // The backend lists offers in stock first, cheapest first: the first offer is the best one, if any has stock.
  const firstOffer = product.offers.at(0)
  const bestOffer = firstOffer && firstOffer.stock > 0 ? firstOffer : undefined
  const title = productTitle(product)

  return (
    <>
      <title>{`${title} · anabe`}</title>
      <nav aria-label="Breadcrumb" className="mb-4 text-sm text-white/80">
        <Link to="/" className="hover:underline">
          Home
        </Link>
        <span className="mx-2">›</span>
        <Link to="/store" className="hover:underline">
          Store
        </Link>
        <span className="mx-2">›</span>
        {product.name}
      </nav>

      <div className="grid gap-8 md:grid-cols-[300px_1fr]">
        <div className="relative mx-auto w-full max-w-[300px]">
          <img src={product.image_url} alt={`${product.name} cover`} className="aspect-[3/4] w-full object-cover" />
          <span className="absolute inset-x-0 bottom-0 bg-black/60 py-1.5 text-center text-sm font-semibold">{product.platform}</span>
        </div>

        <div className="flex flex-col gap-4">
          <h1 className="text-2xl font-extrabold sm:text-3xl">{title}</h1>
          <ul className="flex flex-wrap gap-2 text-xs font-bold uppercase">
            <li className="bg-chip px-3 py-1.5">{product.platform}</li>
            <li className="bg-chip px-3 py-1.5 text-region">{product.region}</li>
            <li className="bg-chip px-3 py-1.5">{product.genre}</li>
            <li className="bg-chip px-3 py-1.5">{product.release_year}</li>
          </ul>
          <p className="max-w-2xl text-white/90">{product.description}</p>
          <p className="text-sm text-white/80">
            Activates on {product.platform} in {product.region === 'Global' ? 'any region' : product.region}.
          </p>
          {bestOffer ? (
            <p>
              <span className="text-sm text-white/70">Best price </span>
              <span className="text-3xl font-extrabold">{formatPrice(bestOffer.price_cents)}</span>
            </p>
          ) : (
            <p className="text-lg font-semibold text-white/70">Currently out of stock</p>
          )}
        </div>
      </div>

      <section aria-labelledby="offers-heading" className="mt-10">
        <h2 id="offers-heading" className="mb-4 text-2xl font-extrabold">
          Offers from {product.offers.length} sellers
        </h2>
        <ul className="flex flex-col gap-3">
          {product.offers.map((offer) => (
            <OfferRow key={offer.id} offer={offer} isBest={offer.id === bestOffer?.id} />
          ))}
        </ul>
      </section>
    </>
  )
}

function OfferRow({ offer, isBest }: { offer: Offer; isBest: boolean }) {
  const [justAdded, setJustAdded] = useState(false)
  const inStock = offer.stock > 0

  return (
    <li className={`grid grid-cols-2 items-center gap-4 bg-card p-4 sm:grid-cols-[1fr_auto_auto_auto] ${isBest ? 'ring-2 ring-mint' : ''}`}>
      <div>
        <p className="font-bold">
          {offer.seller.name}
          {isBest && <span className="ml-2 bg-mint px-2 py-0.5 text-xs font-extrabold text-card uppercase">Best price</span>}
        </p>
        <p className="text-sm text-white/70">
          <span aria-hidden="true">★</span> {offer.seller.rating.toFixed(1)} seller rating
        </p>
      </div>

      <p className={`text-sm ${inStock ? 'text-region' : 'text-white/60'}`}>{inStock ? `${offer.stock} in stock` : 'Out of stock'}</p>

      <div className="text-right">
        {offer.discount_percent !== null && offer.original_price_cents !== null && (
          <p className="text-xs text-white/70">
            <s>{formatPrice(offer.original_price_cents)}</s> <span className="font-bold text-discount">-{offer.discount_percent}%</span>
          </p>
        )}
        <p className="text-xl font-extrabold">{formatPrice(offer.price_cents)}</p>
      </div>

      <div className="text-right">
        <button
          type="button"
          disabled={!inStock}
          onClick={() => {
            cart.add(offer.id)
            setJustAdded(true)
          }}
          className="bg-accent px-5 py-2.5 font-extrabold text-card disabled:cursor-not-allowed disabled:opacity-40"
        >
          {justAdded ? 'Added ✓' : 'Add to cart'}
        </button>
        {justAdded && (
          <Link to="/cart" className="mt-1 block text-sm font-semibold text-accent hover:underline">
            Go to cart
          </Link>
        )}
      </div>
    </li>
  )
}
