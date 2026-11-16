type PaginationProps = {
  currentPage: number
  lastPage: number
  onChange: (page: number) => void
}

export function Pagination({ currentPage, lastPage, onChange }: PaginationProps) {
  if (lastPage <= 1) {
    return null
  }

  const buttonClass = 'min-w-10 px-3 py-2 text-sm font-bold disabled:opacity-40'

  return (
    <nav aria-label="Pages" className="flex flex-wrap items-center justify-center gap-1">
      <button type="button" className={`${buttonClass} bg-chip`} disabled={currentPage === 1} onClick={() => onChange(currentPage - 1)}>
        ‹ Prev
      </button>
      {visiblePages(currentPage, lastPage).map((page, index) =>
        page === null ? (
          <span key={`gap-${index}`} className="px-2 text-white/60">
            …
          </span>
        ) : (
          <button
            key={page}
            type="button"
            aria-current={page === currentPage ? 'page' : undefined}
            className={`${buttonClass} ${page === currentPage ? 'bg-accent text-card' : 'bg-chip hover:bg-nav'}`}
            onClick={() => onChange(page)}
          >
            {page}
          </button>
        ),
      )}
      <button type="button" className={`${buttonClass} bg-chip`} disabled={currentPage === lastPage} onClick={() => onChange(currentPage + 1)}>
        Next ›
      </button>
    </nav>
  )
}

/** First, last, and the pages next to the current one; null marks a gap: 1 … 4 5 6 … 12. */
function visiblePages(currentPage: number, lastPage: number): (number | null)[] {
  const pages: (number | null)[] = []

  for (let page = 1; page <= lastPage; page++) {
    const isNearCurrent = Math.abs(page - currentPage) <= 1
    if (page === 1 || page === lastPage || isNearCurrent) {
      pages.push(page)
    } else if (pages.at(-1) !== null) {
      pages.push(null)
    }
  }

  return pages
}
