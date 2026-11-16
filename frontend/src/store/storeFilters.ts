// The store's state lives in the URL (?platform=Steam&sort=price_asc&page=2), so a filtered view can be
// shared as a link, survives a reload, and Back/Forward step through earlier filters.

export type StoreSort = 'relevance' | 'price_asc' | 'price_desc' | 'newest' | 'name'

export type StoreFilters = {
  q: string
  platforms: string[]
  regions: string[]
  genres: string[]
  /** Euros as typed, kept as text so a half-typed "12." stays as typed. */
  minPrice: string
  maxPrice: string
  /** Empty means the backend's default: relevance when searching, newest otherwise. */
  sort: StoreSort | ''
  page: number
}

export type ListFilterKey = 'platforms' | 'regions' | 'genres'

const sorts: StoreSort[] = ['relevance', 'price_asc', 'price_desc', 'newest', 'name']

/** The query parameter name each list filter uses, in the page URL and in the API call. */
export const listFilterParams: Record<ListFilterKey, string> = {
  platforms: 'platform',
  regions: 'region',
  genres: 'genre',
}

export function readStoreFilters(params: URLSearchParams): StoreFilters {
  const sort = params.get('sort') ?? ''
  const page = Number(params.get('page'))

  return {
    q: params.get('q') ?? '',
    platforms: params.getAll('platform'),
    regions: params.getAll('region'),
    genres: params.getAll('genre'),
    minPrice: params.get('min_price') ?? '',
    maxPrice: params.get('max_price') ?? '',
    sort: sorts.includes(sort as StoreSort) ? (sort as StoreSort) : '',
    page: Number.isInteger(page) && page > 1 ? page : 1,
  }
}

/** The page URL's query. Defaults are left out, so a plain store page stays plain: /store. */
export function writeStoreFilters(filters: StoreFilters): URLSearchParams {
  return buildQuery(filters, (name) => name)
}

/** The same filters for GET /api/products, where PHP expects repeated values as name[]. */
export function toProductsApiQuery(filters: StoreFilters): URLSearchParams {
  return buildQuery(filters, (name) => `${name}[]`)
}

export function hasActiveFilters(filters: StoreFilters): boolean {
  return (
    filters.platforms.length + filters.regions.length + filters.genres.length > 0 ||
    filters.minPrice !== '' ||
    filters.maxPrice !== ''
  )
}

function buildQuery(filters: StoreFilters, listParamName: (name: string) => string): URLSearchParams {
  const params = new URLSearchParams()

  if (filters.q !== '') params.set('q', filters.q)
  for (const key of Object.keys(listFilterParams) as ListFilterKey[]) {
    for (const value of filters[key]) {
      params.append(listParamName(listFilterParams[key]), value)
    }
  }
  if (filters.minPrice !== '') params.set('min_price', filters.minPrice)
  if (filters.maxPrice !== '') params.set('max_price', filters.maxPrice)
  if (filters.sort !== '') params.set('sort', filters.sort)
  if (filters.page > 1) params.set('page', String(filters.page))

  return params
}
