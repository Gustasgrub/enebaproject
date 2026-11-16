import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, render, screen } from '@testing-library/react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ServerWakeUpNotice } from './ServerWakeUpNotice'

describe('ServerWakeUpNotice', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('appears once a request has been pending for 3 seconds and hides when it finishes', async () => {
    const queryClient = new QueryClient()
    render(
      <QueryClientProvider client={queryClient}>
        <ServerWakeUpNotice />
      </QueryClientProvider>,
    )

    // A request that stays pending until the test finishes it, like one waiting for a sleeping server.
    let finishRequest = () => {}
    void queryClient.prefetchQuery({
      queryKey: ['slow-request'],
      queryFn: () => new Promise<null>((resolve) => (finishRequest = () => resolve(null))),
    })

    // Let TanStack Query tell its subscribers that a request has started.
    await act(() => vi.advanceTimersByTimeAsync(0))

    await act(() => vi.advanceTimersByTimeAsync(2999))
    expect(screen.queryByRole('status')).toBeNull()

    await act(() => vi.advanceTimersByTimeAsync(1))
    expect(screen.getByRole('status').textContent).toMatch(/waking up the server/i)

    await act(async () => {
      finishRequest()
      await vi.advanceTimersByTimeAsync(0)
    })
    expect(screen.queryByRole('status')).toBeNull()
  })
})
