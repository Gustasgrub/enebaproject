import { useQuery } from '@tanstack/react-query'
import { apiGet } from '../api/client'
import type { HomepageBlockSummary, ListResponse } from '../api/types'
import { BlockRenderer } from '../components/homepage/BlockRenderer'

// The homepage has no content of its own: it asks the backend which blocks to show, in which order,
// and each block then loads its own data (PLAN.md, Section 7).
export function HomePage() {
  const layout = useQuery({
    queryKey: ['homepage'],
    queryFn: ({ signal }) => apiGet<ListResponse<HomepageBlockSummary>>('/api/homepage', undefined, signal),
  })

  return (
    <>
      <title>anabe · Game keys for less</title>
      {layout.isPending ? (
        <div aria-hidden="true">
          <div className="h-[260px] animate-pulse bg-card sm:aspect-[7/2] sm:h-auto" />
          <div className="mx-auto max-w-[1200px] px-4 py-6">
            <div className="mb-4 h-7 w-48 animate-pulse bg-card" />
            <div className="h-[400px] animate-pulse bg-card/60" />
          </div>
        </div>
      ) : layout.isError ? (
        <div className="mx-auto max-w-[1200px] px-4 py-16 text-center">
          <p className="mb-4 text-lg font-semibold">The homepage could not be loaded.</p>
          <button type="button" className="bg-accent px-6 py-3 font-bold text-card" onClick={() => void layout.refetch()}>
            Try again
          </button>
        </div>
      ) : (
        layout.data.data.map((block) => <BlockRenderer key={block.id} block={block} />)
      )}
    </>
  )
}
