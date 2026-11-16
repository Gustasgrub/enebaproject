import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router'
import { describe, expect, it } from 'vitest'
import type { ProductSummary } from '../../api/types'
import { ProductCard } from './ProductCard'

const product: ProductSummary = {
  id: 1,
  slug: 'elden-ring-steam-global',
  name: 'Elden Ring',
  image_url: 'http://localhost:8000/covers/elden-ring.svg',
  platform: 'Steam',
  region: 'Global',
  genre: 'RPG',
  release_year: 2022,
  price_cents: 2700,
  original_price_cents: 5999,
  discount_percent: 55,
}

function renderCard(overrides: Partial<ProductSummary>) {
  render(
    <MemoryRouter>
      <ProductCard product={{ ...product, ...overrides }} />
    </MemoryRouter>,
  )
}

describe('ProductCard', () => {
  it('shows a deal with the struck-through original price and the discount', () => {
    renderCard({})

    expect(screen.getByRole('heading').textContent).toBe('Elden Ring Steam Key GLOBAL')
    expect(screen.getByText('€27.00')).toBeTruthy()
    expect(screen.getByText('€59.99').tagName).toBe('S')
    expect(screen.getByText('-55%')).toBeTruthy()
    expect(screen.getByRole('link').getAttribute('href')).toBe('/product/elden-ring-steam-global')
  })

  it('shows a product without stock as out of stock, without a price', () => {
    renderCard({ price_cents: null, original_price_cents: null, discount_percent: null })

    expect(screen.getByText('Out of stock')).toBeTruthy()
    expect(screen.queryByText(/€/)).toBeNull()
  })
})
