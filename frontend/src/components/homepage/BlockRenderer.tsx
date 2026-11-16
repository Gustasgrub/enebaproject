import { useEffect, type ComponentType } from 'react'
import type { HomepageBlockSummary } from '../../api/types'
import { BannerBlock } from './BannerBlock'
import { BlockErrorBoundary } from './BlockErrorBoundary'
import { ProductRowBlock } from './ProductRowBlock'

type BlockComponent = ComponentType<{ block: HomepageBlockSummary }>

// Block type → component. A new block of an existing type needs only a database row; a new type
// needs an entry here and a provider in the backend's BlockRegistry.
const blockComponents: Record<string, BlockComponent> = {
  banner: BannerBlock,
  bestsellers: ProductRowBlock,
  deals: ProductRowBlock,
  new_releases: ProductRowBlock,
  platform_row: ProductRowBlock,
}

export function BlockRenderer({ block }: { block: HomepageBlockSummary }) {
  const BlockView = blockComponents[block.type]

  if (BlockView === undefined) {
    return <UnknownBlock block={block} />
  }

  return (
    <BlockErrorBoundary title={block.title}>
      <BlockView block={block} />
    </BlockErrorBoundary>
  )
}

// A type this frontend does not know yet (the backend may be newer): render nothing, but say so.
function UnknownBlock({ block }: { block: HomepageBlockSummary }) {
  useEffect(() => {
    console.warn(`Skipping homepage block ${block.id}: unknown type "${block.type}"`)
  }, [block.id, block.type])

  return null
}
