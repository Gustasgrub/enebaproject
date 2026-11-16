import { Link } from 'react-router'
import type { ProductSummary } from '../../api/types'
import { formatPrice } from '../../lib/money'
import { productTitle } from '../../lib/productTitle'

// A product card: cover with the platform strip, title, region and price. Vertical in grids from the
// small breakpoint up; a horizontal list item (image left, details right) on phones.
export function ProductCard({ product }: { product: ProductSummary }) {
  // Only a real discount shows the struck-through original price.
  const dealOriginalPrice = product.discount_percent !== null ? product.original_price_cents : null

  return (
    <Link
      to={`/product/${product.slug}`}
      className="flex bg-card outline-offset-2 transition hover:ring-2 hover:ring-mint sm:flex-col"
    >
      <div className="relative aspect-[3/4] w-28 shrink-0 sm:w-full">
        <img src={product.image_url} alt="" loading="lazy" className="h-full w-full object-cover" />
        <span className="absolute inset-x-0 bottom-0 bg-black/60 py-1 text-center text-xs font-semibold">
          {product.platform}
        </span>
      </div>

      <div className="flex min-w-0 flex-1 flex-col gap-1 p-4">
        <h3 className="line-clamp-2 text-sm font-bold leading-snug">{productTitle(product)}</h3>
        <p className="text-xs font-bold uppercase text-region">{product.region}</p>

        <div className="mt-auto pt-2">
          {product.price_cents === null ? (
            <p className="font-semibold text-white/60">Out of stock</p>
          ) : (
            <>
              <p className="text-xs text-white/70">
                From{' '}
                {dealOriginalPrice !== null && (
                  <>
                    <s>{formatPrice(dealOriginalPrice)}</s>{' '}
                    <span className="font-bold text-discount">-{product.discount_percent}%</span>
                  </>
                )}
              </p>
              <p className="text-2xl font-extrabold">{formatPrice(product.price_cents)}</p>
            </>
          )}
        </div>
      </div>
    </Link>
  )
}
