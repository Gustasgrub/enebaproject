import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { HealthStatus } from './HealthStatus'

function renderHealthStatus() {
  // No retries in tests: a failing request should show its error state immediately.
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })

  render(
    <QueryClientProvider client={queryClient}>
      <HealthStatus />
    </QueryClientProvider>,
  )
}

describe('HealthStatus', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('shows the database version reported by the backend', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () =>
        Response.json({ status: 'ok', database: 'PostgreSQL 17.12', checked_at: '2026-11-16T22:00:00+00:00' }),
      ),
    )

    renderHealthStatus()

    expect(await screen.findByText('PostgreSQL 17.12')).toBeTruthy()
  })

  it('shows an error when the backend reports it is unhealthy', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => new Response(null, { status: 503 })))

    renderHealthStatus()

    expect(await screen.findByText(/backend unavailable/i)).toBeTruthy()
  })
})
