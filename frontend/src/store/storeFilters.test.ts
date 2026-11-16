import { describe, expect, it } from 'vitest'
import { readStoreFilters, toProductsApiQuery, writeStoreFilters } from './storeFilters'

describe('store filters in the URL', () => {
  it('reads every filter from the page URL and writes the same URL back', () => {
    const url = 'q=witcher&platform=Steam&platform=Xbox&region=Global&min_price=5&max_price=20&sort=price_asc&page=2'

    const filters = readStoreFilters(new URLSearchParams(url))

    expect(filters).toEqual({
      q: 'witcher',
      platforms: ['Steam', 'Xbox'],
      regions: ['Global'],
      genres: [],
      minPrice: '5',
      maxPrice: '20',
      sort: 'price_asc',
      page: 2,
    })
    expect(writeStoreFilters(filters).toString()).toBe(url)
  })

  it('ignores invalid sort and page values', () => {
    const filters = readStoreFilters(new URLSearchParams('sort=cheapest&page=-3'))

    expect(filters.sort).toBe('')
    expect(filters.page).toBe(1)
  })

  it('uses PHP array names for repeated values in the API query', () => {
    const filters = readStoreFilters(new URLSearchParams('platform=Steam&platform=Xbox&genre=RPG'))

    expect(toProductsApiQuery(filters).toString()).toBe('platform%5B%5D=Steam&platform%5B%5D=Xbox&genre%5B%5D=RPG')
  })
})
