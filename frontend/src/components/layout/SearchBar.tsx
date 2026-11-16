import { useQuery } from '@tanstack/react-query'
import { useId, useState, type FormEvent, type KeyboardEvent } from 'react'
import { useNavigate } from 'react-router'
import { apiGet } from '../../api/client'
import type { ListResponse, ProductSummary } from '../../api/types'
import { splitForHighlight } from '../../lib/highlight'
import { formatPrice } from '../../lib/money'
import { useDebouncedValue } from '../../lib/useDebouncedValue'

const SUGGEST_DELAY_MS = 250
const MIN_QUERY_LENGTH = 2

// The header search: live suggestions while typing (↑ ↓ to move, Enter to open, Esc to close), and
// Enter without a highlighted suggestion opens the full results page, /search?q=.
export function SearchBar({ initialText }: { initialText: string }) {
  const navigate = useNavigate()
  const listboxId = useId()
  const [text, setText] = useState(initialText)
  const [isOpen, setIsOpen] = useState(false)
  const [activeIndex, setActiveIndex] = useState(-1)

  const debouncedText = useDebouncedValue(text.trim(), SUGGEST_DELAY_MS)
  const isLongEnough = debouncedText.length >= MIN_QUERY_LENGTH
  const suggestions = useQuery({
    queryKey: ['suggest', debouncedText],
    queryFn: ({ signal }) =>
      apiGet<ListResponse<ProductSummary>>('/api/search/suggest', new URLSearchParams({ q: debouncedText }), signal),
    enabled: isLongEnough,
    staleTime: 60_000,
  })
  const items = isLongEnough ? (suggestions.data?.data ?? []) : []
  const showDropdown = isOpen && isLongEnough

  function close() {
    setIsOpen(false)
    setActiveIndex(-1)
  }

  function openProduct(product: ProductSummary) {
    close()
    void navigate(`/product/${product.slug}`)
  }

  function submit(event: FormEvent) {
    event.preventDefault()
    const activeItem = items[activeIndex]
    if (showDropdown && activeItem) {
      openProduct(activeItem)
    } else if (text.trim() !== '') {
      close()
      void navigate(`/search?q=${encodeURIComponent(text.trim())}`)
    }
  }

  function handleKeyDown(event: KeyboardEvent<HTMLInputElement>) {
    if (event.key === 'ArrowDown') {
      event.preventDefault()
      setIsOpen(true)
      setActiveIndex((index) => Math.min(index + 1, items.length - 1))
    } else if (event.key === 'ArrowUp') {
      event.preventDefault()
      setActiveIndex((index) => Math.max(index - 1, -1))
    } else if (event.key === 'Escape') {
      close()
    }
  }

  return (
    <form role="search" onSubmit={submit} className="relative w-full">
      <input
        type="search"
        role="combobox"
        aria-label="Search for games"
        aria-expanded={showDropdown}
        aria-controls={listboxId}
        aria-autocomplete="list"
        aria-activedescendant={showDropdown && activeIndex >= 0 ? `${listboxId}-${activeIndex}` : undefined}
        placeholder="Search for games"
        autoComplete="off"
        className="w-full border-2 border-white/80 bg-transparent px-4 py-3 font-semibold placeholder:text-white/70 focus:border-accent focus:outline-none"
        value={text}
        onChange={(event) => {
          setText(event.target.value)
          setIsOpen(true)
          setActiveIndex(-1)
        }}
        onFocus={() => setIsOpen(true)}
        onBlur={close}
        onKeyDown={handleKeyDown}
      />

      {showDropdown && (
        <ul id={listboxId} role="listbox" aria-label="Suggestions" className="absolute inset-x-0 top-full z-20 mt-1 bg-card shadow-2xl">
          {items.length === 0 && (
            <li className="px-4 py-3 text-sm text-white/70">{suggestions.isFetching ? 'Searching…' : 'No matching games'}</li>
          )}
          {items.map((product, index) => (
            <li
              key={product.id}
              id={`${listboxId}-${index}`}
              role="option"
              aria-selected={index === activeIndex}
              // mousedown, not click: it fires before the input's blur closes the list.
              onMouseDown={(event) => {
                event.preventDefault()
                openProduct(product)
              }}
              onMouseEnter={() => setActiveIndex(index)}
              className={`flex cursor-pointer items-center gap-3 px-4 py-2 ${index === activeIndex ? 'bg-chip' : ''}`}
            >
              <img src={product.image_url} alt="" className="h-12 w-9 shrink-0 object-cover" />
              <span className="min-w-0 flex-1">
                <span className="block truncate text-sm">
                  {splitForHighlight(product.name, text).map((part, partIndex) =>
                    part.isMatch ? (
                      <strong key={partIndex} className="text-accent">
                        {part.text}
                      </strong>
                    ) : (
                      <span key={partIndex}>{part.text}</span>
                    ),
                  )}
                </span>
                <span className="block text-xs text-white/60">
                  {product.platform} · {product.region}
                </span>
              </span>
              <span className="text-sm font-bold">{product.price_cents === null ? 'Sold out' : formatPrice(product.price_cents)}</span>
            </li>
          ))}
        </ul>
      )}
    </form>
  )
}
