import { formatPrice } from '../../lib/money'
import { hasActiveFilters, type ListFilterKey, type StoreFilters } from '../../store/storeFilters'

type ActiveFiltersProps = {
  filters: StoreFilters
  onChange: (changes: Partial<StoreFilters>) => void
}

// One removable chip per active filter, plus "Clear all" (as above eneba.com's results grid).
export function ActiveFilters({ filters, onChange }: ActiveFiltersProps) {
  if (!hasActiveFilters(filters)) {
    return null
  }

  const listChips = (['platforms', 'regions', 'genres'] as ListFilterKey[]).flatMap((key) =>
    filters[key].map((value) => ({
      label: value,
      remove: () => onChange({ [key]: filters[key].filter((v) => v !== value) }),
    })),
  )
  const priceChip = priceLabel(filters)
  const chips = priceChip ? [...listChips, { label: priceChip, remove: () => onChange({ minPrice: '', maxPrice: '' }) }] : listChips

  return (
    <div className="flex flex-wrap items-center gap-2">
      {chips.map((chip) => (
        <button
          key={chip.label}
          type="button"
          onClick={chip.remove}
          className="flex items-center gap-2 rounded-full bg-chip px-4 py-1.5 text-sm font-semibold hover:bg-nav"
          aria-label={`Remove filter ${chip.label}`}
        >
          {chip.label}
          <span aria-hidden="true">✕</span>
        </button>
      ))}
      <button
        type="button"
        className="px-2 text-sm font-semibold text-accent hover:underline"
        onClick={() => onChange({ platforms: [], regions: [], genres: [], minPrice: '', maxPrice: '' })}
      >
        Clear all
      </button>
    </div>
  )
}

function priceLabel({ minPrice, maxPrice }: StoreFilters): string | null {
  const euros = (text: string) => (Number.isFinite(Number(text)) ? formatPrice(Math.round(Number(text) * 100)) : text)

  if (minPrice !== '' && maxPrice !== '') return `${euros(minPrice)} – ${euros(maxPrice)}`
  if (minPrice !== '') return `From ${euros(minPrice)}`
  if (maxPrice !== '') return `Up to ${euros(maxPrice)}`
  return null
}
