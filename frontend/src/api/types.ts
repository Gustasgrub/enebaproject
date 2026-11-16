// Shapes of the backend's JSON responses. Every API call is typed with one of these.

export type Platform = 'Steam' | 'PlayStation' | 'Xbox' | 'Nintendo' | 'EA App' | 'Ubisoft Connect'

export type Region = 'Global' | 'Europe' | 'North America'

/** A product as shown on a card (ProductSummaryResource). Prices are euro cents. */
export type ProductSummary = {
  id: number
  slug: string
  name: string
  image_url: string
  platform: Platform
  region: Region
  genre: string
  release_year: number
  /** The cheapest offer that has stock; null means out of stock. */
  price_cents: number | null
  original_price_cents: number | null
  discount_percent: number | null
}

export type ListResponse<T> = {
  data: T[]
}

export type PaginatedResponse<T> = ListResponse<T> & {
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

/** GET /api/products */
export type ProductListResponse = PaginatedResponse<ProductSummary> & {
  /** Set when a search found nothing but a product name is close to the query. */
  did_you_mean: string | null
}

export type FilterOption = {
  value: string
  count: number
}

/** GET /api/filters */
export type FilterOptionsResponse = {
  data: {
    platforms: FilterOption[]
    regions: FilterOption[]
    genres: FilterOption[]
  }
}

/** GET /api/homepage: one entry per enabled block, in display order. */
export type HomepageBlockSummary = {
  id: number
  /** Decides which component renders the block; unknown types are skipped. */
  type: string
  title: string
}

/** An item of the `banner` block (GET /api/homepage/blocks/{id}). */
export type BannerItem = {
  id: number
  title: string
  subtitle: string
  image_url: string
  /** A path inside this frontend, e.g. /product/elden-ring-steam-global. */
  link_url: string
}

/** One seller's offer (OfferResource). `product` is included only by GET /api/offers (the cart). */
export type Offer = {
  id: number
  price_cents: number
  original_price_cents: number | null
  discount_percent: number | null
  /** Available keys right now. */
  stock: number
  seller: { name: string; rating: number }
  product?: { slug: string; name: string; image_url: string; platform: Platform; region: Region }
}

/** GET /api/products/{slug}. Offers in stock come first, cheapest first. */
export type ProductDetail = Omit<ProductSummary, 'price_cents' | 'original_price_cents' | 'discount_percent'> & {
  description: string
  offers: Offer[]
}
