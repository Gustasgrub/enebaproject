import { useState, type FormEvent, type ReactNode } from 'react'
import type { FilterOption, FilterOptionsResponse } from '../../api/types'
import type { ListFilterKey, StoreFilters } from '../../store/storeFilters'

type FilterSidebarProps = {
  filters: StoreFilters
  options: FilterOptionsResponse['data'] | undefined
  onChange: (changes: Partial<StoreFilters>) => void
}

// The store's left column: collapsible panels for price range and the three option lists.
export function FilterSidebar({ filters, options, onChange }: FilterSidebarProps) {
  function toggleValue(key: ListFilterKey, value: string) {
    const selected = filters[key]
    onChange({ [key]: selected.includes(value) ? selected.filter((v) => v !== value) : [...selected, value] })
  }

  return (
    <div className="flex flex-col gap-1">
      <FilterPanel title="Price range (EUR)">
        {/* key: the inputs start over from the URL when the price filter changes elsewhere (chips, Clear all). */}
        <PriceRangeForm
          key={`${filters.minPrice}-${filters.maxPrice}`}
          minPrice={filters.minPrice}
          maxPrice={filters.maxPrice}
          onApply={(minPrice, maxPrice) => onChange({ minPrice, maxPrice })}
        />
      </FilterPanel>

      <OptionPanel title="Platforms" options={options?.platforms} selected={filters.platforms} onToggle={(value) => toggleValue('platforms', value)} />
      <OptionPanel title="Regions" options={options?.regions} selected={filters.regions} onToggle={(value) => toggleValue('regions', value)} />
      <OptionPanel title="Genres" options={options?.genres} selected={filters.genres} onToggle={(value) => toggleValue('genres', value)} />
    </div>
  )
}

function FilterPanel({ title, badge, children }: { title: string; badge?: number; children: ReactNode }) {
  // <details> gives a collapsible panel with keyboard support and no state of our own.
  return (
    <details open className="group bg-panel px-5 py-4">
      <summary className="flex cursor-pointer list-none items-center justify-between font-bold">
        <span className="flex items-center gap-2">
          {title}
          {badge !== undefined && badge > 0 && (
            <span className="grid size-5 place-items-center rounded-full bg-accent text-xs text-card">{badge}</span>
          )}
        </span>
        <span aria-hidden="true" className="transition group-open:rotate-180">
          ▾
        </span>
      </summary>
      <div className="mt-3">{children}</div>
    </details>
  )
}

type OptionPanelProps = {
  title: string
  options: FilterOption[] | undefined
  selected: string[]
  onToggle: (value: string) => void
}

function OptionPanel({ title, options, selected, onToggle }: OptionPanelProps) {
  return (
    <FilterPanel title={title} badge={selected.length}>
      {options === undefined ? (
        <p className="text-sm text-white/60">Loading…</p>
      ) : (
        <ul className="flex flex-col gap-2">
          {options.map((option) => (
            <li key={option.value}>
              <label className="flex cursor-pointer items-center gap-3 text-sm">
                <input
                  type="checkbox"
                  className="size-4 accent-accent"
                  checked={selected.includes(option.value)}
                  onChange={() => onToggle(option.value)}
                />
                <span className="flex-1">{option.value}</span>
                <span className="text-white/70">{option.count}</span>
              </label>
            </li>
          ))}
        </ul>
      )}
    </FilterPanel>
  )
}

type PriceRangeFormProps = {
  minPrice: string
  maxPrice: string
  onApply: (minPrice: string, maxPrice: string) => void
}

// Applied on Enter or when a field loses focus, not on every keystroke: each change is a new request.
function PriceRangeForm({ minPrice, maxPrice, onApply }: PriceRangeFormProps) {
  const [min, setMin] = useState(minPrice)
  const [max, setMax] = useState(maxPrice)

  function apply(event?: FormEvent) {
    event?.preventDefault()
    if (min !== minPrice || max !== maxPrice) {
      onApply(min.trim(), max.trim())
    }
  }

  const inputClass = 'w-full min-w-0 bg-field px-3 py-2 text-sm placeholder:text-white/50 focus:outline-2 focus:outline-accent'

  return (
    <form onSubmit={apply} className="flex items-center gap-2">
      <input
        aria-label="Minimum price in euros"
        inputMode="decimal"
        placeholder="From"
        className={inputClass}
        value={min}
        onChange={(event) => setMin(event.target.value)}
        onBlur={() => apply()}
      />
      <span aria-hidden="true">-</span>
      <input
        aria-label="Maximum price in euros"
        inputMode="decimal"
        placeholder="To"
        className={inputClass}
        value={max}
        onChange={(event) => setMax(event.target.value)}
        onBlur={() => apply()}
      />
      {/* Lets Enter submit the form; visually the fields apply themselves. */}
      <button type="submit" className="sr-only">
        Apply price range
      </button>
    </form>
  )
}
