import type { StoreSort } from '../../store/storeFilters'

const sortLabels: Record<StoreSort, string> = {
  relevance: 'Best match',
  newest: 'Newest',
  price_asc: 'Price: low to high',
  price_desc: 'Price: high to low',
  name: 'Name: A to Z',
}

type SortSelectProps = {
  /** The sort in effect; when the URL has none, the backend's default for this page. */
  value: StoreSort
  isSearch: boolean
  onChange: (sort: StoreSort) => void
}

export function SortSelect({ value, isSearch, onChange }: SortSelectProps) {
  // Relevance only means something when there is a search query to rank against.
  const sorts = (Object.keys(sortLabels) as StoreSort[]).filter((sort) => isSearch || sort !== 'relevance')

  return (
    <label className="flex items-center gap-2 text-sm font-semibold">
      <span>Sort by:</span>
      <select
        value={value}
        onChange={(event) => onChange(event.target.value as StoreSort)}
        className="bg-page py-1 pr-2 font-bold focus:outline-2 focus:outline-accent"
      >
        {sorts.map((sort) => (
          <option key={sort} value={sort}>
            {sortLabels[sort]}
          </option>
        ))}
      </select>
    </label>
  )
}
