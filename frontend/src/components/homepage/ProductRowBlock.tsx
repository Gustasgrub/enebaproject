import type { HomepageBlockSummary, ProductSummary } from '../../api/types'
import { ProductCard } from '../product/ProductCard'
import { BlockUnavailable } from './BlockUnavailable'
import { useBlockData } from './useBlockData'

const cardSlotClass = 'flex w-[200px] shrink-0 snap-start'

// A titled row of product cards (bestsellers, deals, new releases, platform rows). It scrolls sideways.
export function ProductRowBlock({ block }: { block: HomepageBlockSummary }) {
  const products = useBlockData<ProductSummary>(block.id)

  if (products.isError) {
    return <BlockUnavailable title={block.title} onRetry={() => void products.refetch()} />
  }
  if (products.data && products.data.data.length === 0) {
    return null
  }

  return (
    <section aria-label={block.title} className="mx-auto max-w-[1200px] px-4 py-6">
      <h2 className="mb-4 text-2xl font-extrabold">{block.title}</h2>
      <ul className="flex snap-x gap-4 overflow-x-auto pb-3">
        {products.isPending
          ? Array.from({ length: 5 }, (_, index) => (
              <li key={index} aria-hidden="true" className={`${cardSlotClass} h-[400px] animate-pulse bg-card`} />
            ))
          : products.data.data.map((product) => (
              <li key={product.id} className={cardSlotClass}>
                <ProductCard product={product} layout="vertical" />
              </li>
            ))}
      </ul>
    </section>
  )
}
