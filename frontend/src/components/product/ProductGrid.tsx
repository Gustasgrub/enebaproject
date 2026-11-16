import type { ProductSummary } from '../../api/types'
import { ProductCard } from './ProductCard'

export function ProductGrid({ products }: { products: ProductSummary[] }) {
  return (
    <ul className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
      {products.map((product) => (
        <li key={product.id} className="flex">
          <ProductCard product={product} />
        </li>
      ))}
    </ul>
  )
}

/** Grey card shapes shown while a page of products loads, so the layout does not jump. */
export function ProductGridSkeleton({ count = 8 }: { count?: number }) {
  return (
    <ul className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4" aria-hidden="true">
      {Array.from({ length: count }, (_, index) => (
        <li key={index} className="flex animate-pulse bg-card sm:flex-col">
          <div className="aspect-[3/4] w-28 shrink-0 bg-white/10 sm:w-full" />
          <div className="flex-1 space-y-2 p-4">
            <div className="h-4 w-4/5 bg-white/10" />
            <div className="h-3 w-1/3 bg-white/10" />
            <div className="h-6 w-1/2 bg-white/10" />
          </div>
        </li>
      ))}
    </ul>
  )
}
