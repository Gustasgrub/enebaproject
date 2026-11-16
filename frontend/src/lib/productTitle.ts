import type { ProductSummary } from '../api/types'

/** The title as eneba.com writes it: "Elden Ring Steam Key GLOBAL". */
export function productTitle(product: Pick<ProductSummary, 'name' | 'platform' | 'region'>): string {
  return `${product.name} ${product.platform} Key ${product.region.toUpperCase()}`
}
