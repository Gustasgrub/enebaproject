import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { HomePage } from './HomePage'

const product = {
  id: 7,
  slug: 'hades-steam-global',
  name: 'Hades',
  image_url: 'http://localhost:8000/covers/hades.svg',
  platform: 'Steam',
  region: 'Global',
  genre: 'Action',
  release_year: 2020,
  price_cents: 1299,
  original_price_cents: null,
  discount_percent: null,
}

// The backend as the homepage sees it: a layout of five blocks, and one response per block.
const responses: Record<string, () => Response> = {
  '/api/homepage': () =>
    Response.json({
      data: [
        { id: 1, type: 'banner', title: 'Featured' },
        { id: 2, type: 'deals', title: 'Top deals' },
        { id: 3, type: 'new_releases', title: 'New releases' },
        { id: 4, type: 'platform_row', title: 'Steam games' },
        { id: 5, type: 'a_type_from_the_future', title: 'Mystery' },
      ],
    }),
  '/api/homepage/blocks/1': () =>
    Response.json({ data: [{ id: 1, title: 'Winter Sale', subtitle: 'Big savings', image_url: 'x.svg', link_url: '/store' }] }),
  // This block's request fails...
  '/api/homepage/blocks/2': () => new Response(null, { status: 500 }),
  '/api/homepage/blocks/3': () => Response.json({ data: [product] }),
  // ...and this one answers with data its component cannot render (no region), so it throws.
  '/api/homepage/blocks/4': () => Response.json({ data: [{ id: 9, slug: 'broken', name: 'Broken' }] }),
}

function renderHomePage() {
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string) => {
      const path = new URL(url, 'http://localhost').pathname
      return responses[path]?.() ?? new Response(null, { status: 404 })
    }),
  )
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })

  render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter>
        <HomePage />
      </MemoryRouter>
    </QueryClientProvider>,
  )
}

describe('HomePage', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.restoreAllMocks()
  })

  it('renders every other block when one block fails to load or to render', async () => {
    const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
    const consoleWarn = vi.spyOn(console, 'warn').mockImplementation(() => {})

    renderHomePage()

    // The healthy blocks show their data.
    expect(await screen.findByText('Winter Sale')).toBeTruthy()
    expect(await screen.findByText('Hades Steam Key GLOBAL')).toBeTruthy()

    // The failed request and the crashed render each show only their own small fallback.
    const deals = await screen.findByRole('region', { name: 'Top deals' })
    expect(deals.textContent).toContain('could not be loaded')
    const steamRow = await screen.findByRole('region', { name: 'Steam games' })
    expect(steamRow.textContent).toContain('could not be loaded')
    expect(consoleError).toHaveBeenCalled()

    // A block type this frontend does not know is skipped, with a warning.
    expect(screen.queryByText('Mystery')).toBeNull()
    expect(consoleWarn).toHaveBeenCalledWith(expect.stringContaining('a_type_from_the_future'))
  })
})
