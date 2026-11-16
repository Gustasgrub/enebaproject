import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { Link, useSearchParams } from 'react-router'
import { apiGet } from '../api/client'
import type { FilterOptionsResponse, ProductListResponse } from '../api/types'
import { ProductGrid, ProductGridSkeleton } from '../components/product/ProductGrid'
import { ActiveFilters } from '../components/store/ActiveFilters'
import { FilterSidebar } from '../components/store/FilterSidebar'
import { Pagination } from '../components/store/Pagination'
import { SortSelect } from '../components/store/SortSelect'
import { hasActiveFilters, readStoreFilters, toProductsApiQuery, writeStoreFilters, type StoreFilters } from '../store/storeFilters'

type StorePageProps = {
  /** /store and /search?q= share this page; search changes the heading and the default sort. */
  mode: 'store' | 'search'
}

export function StorePage({ mode }: StorePageProps) {
  const [searchParams, setSearchParams] = useSearchParams()
  const [isFilterPanelOpen, setIsFilterPanelOpen] = useState(false)
  const filters = readStoreFilters(searchParams)
  const apiQuery = toProductsApiQuery(filters)
  const isSearch = filters.q !== ''

  const products = useQuery({
    queryKey: ['products', apiQuery.toString()],
    queryFn: ({ signal }) => apiGet<ProductListResponse>('/api/products', apiQuery, signal),
    // While the next page or filter loads, keep showing the current cards instead of a blank grid.
    placeholderData: keepPreviousData,
  })

  const filterOptions = useQuery({
    queryKey: ['filters', filters.q],
    queryFn: ({ signal }) =>
      apiGet<FilterOptionsResponse>('/api/filters', isSearch ? new URLSearchParams({ q: filters.q }) : undefined, signal),
  })

  // Every change goes through the URL. A filter change starts again at page 1; only paging sets a page.
  function updateFilters(changes: Partial<StoreFilters>) {
    setSearchParams(writeStoreFilters({ ...filters, page: 1, ...changes }))
  }

  const heading = mode === 'search' && isSearch ? `Results for “${filters.q}”` : 'Store'

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-6">
      <title>{`${heading} · anabe`}</title>

      <nav aria-label="Breadcrumb" className="mb-3 text-sm text-white/80">
        <Link to="/" className="hover:underline">
          Home
        </Link>
        <span className="mx-2">›</span>
        {mode === 'search' ? 'Search' : 'Store'}
      </nav>
      <h1 className="mb-6 text-2xl font-extrabold sm:text-3xl">{heading}</h1>

      <div className="gap-5 lg:grid lg:grid-cols-[220px_1fr]">
        <aside className="mb-4 lg:mb-0">
          <button
            type="button"
            className="mb-3 w-full bg-panel px-4 py-3 text-left font-bold lg:hidden"
            aria-expanded={isFilterPanelOpen}
            onClick={() => setIsFilterPanelOpen((isOpen) => !isOpen)}
          >
            Filters {isFilterPanelOpen ? '▴' : '▾'}
          </button>
          <div className={isFilterPanelOpen ? 'block' : 'hidden lg:block'}>
            <FilterSidebar filters={filters} options={filterOptions.data?.data} onChange={updateFilters} />
          </div>
        </aside>

        <section aria-label="Results" className="flex min-w-0 flex-col gap-4">
          <ActiveFilters filters={filters} onChange={updateFilters} />

          <div className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm">
              Results found: <strong>{products.data?.meta.total ?? '…'}</strong>
            </p>
            <SortSelect
              value={filters.sort || (isSearch ? 'relevance' : 'newest')}
              isSearch={isSearch}
              onChange={(sort) => updateFilters({ sort })}
            />
          </div>

          {products.isPending ? (
            <ProductGridSkeleton />
          ) : products.isError ? (
            <div className="bg-card p-6">
              <p className="mb-3 font-semibold">The products could not be loaded.</p>
              <button type="button" className="bg-accent px-4 py-2 font-bold text-card" onClick={() => void products.refetch()}>
                Try again
              </button>
            </div>
          ) : products.data.data.length === 0 ? (
            <NoResults
              searchText={filters.q}
              didYouMean={products.data.did_you_mean}
              canClearFilters={hasActiveFilters(filters)}
              onClearFilters={() => updateFilters({ platforms: [], regions: [], genres: [], minPrice: '', maxPrice: '' })}
            />
          ) : (
            <div className={products.isPlaceholderData ? 'opacity-60 transition' : 'transition'}>
              <ProductGrid products={products.data.data} />
            </div>
          )}

          {products.data && (
            <Pagination
              currentPage={products.data.meta.current_page}
              lastPage={products.data.meta.last_page}
              onChange={(page) => setSearchParams(writeStoreFilters({ ...filters, page }))}
            />
          )}
        </section>
      </div>
    </div>
  )
}

type NoResultsProps = {
  searchText: string
  didYouMean: string | null
  canClearFilters: boolean
  onClearFilters: () => void
}

function NoResults({ searchText, didYouMean, canClearFilters, onClearFilters }: NoResultsProps) {
  return (
    <div className="bg-card p-6">
      <p className="mb-2 text-lg font-bold">{searchText ? `No games found for “${searchText}”.` : 'No games match these filters.'}</p>
      {didYouMean && (
        <p className="mb-2">
          Did you mean{' '}
          <Link to={`/search?q=${encodeURIComponent(didYouMean)}`} className="font-bold text-accent hover:underline">
            {didYouMean}
          </Link>
          ?
        </p>
      )}
      {canClearFilters && (
        <button type="button" className="font-semibold text-accent hover:underline" onClick={onClearFilters}>
          Clear all filters
        </button>
      )}
    </div>
  )
}
