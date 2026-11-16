import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { fireEvent, render, screen, waitFor } from '@testing-library/react'
import { createMemoryRouter, RouterProvider } from 'react-router'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { endSession, startSession } from '../auth/session'
import { cart } from '../cart/cartStore'
import { CheckoutPage } from './CheckoutPage'

const offer = {
  id: 3,
  price_cents: 1299,
  original_price_cents: null,
  discount_percent: null,
  stock: 5,
  seller: { name: 'PixelVault', rating: 4.9 },
  product: { slug: 'hades-steam-global', name: 'Hades', image_url: 'x.svg', platform: 'Steam', region: 'Global' },
}

describe('CheckoutPage', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    cart.clear()
    endSession()
  })

  it('sends a payment token, never the card number', async () => {
    startSession({ token: 'test-token', user: { id: 1, name: 'Ada', email: 'ada@example.com' } })
    cart.add(offer.id, 2)
    const checkoutBodies: string[] = []
    vi.stubGlobal(
      'fetch',
      vi.fn(async (url: string, init?: RequestInit) => {
        if (url.includes('/api/offers')) return Response.json({ data: [offer] })
        checkoutBodies.push(String(init?.body))
        return Response.json({ data: { id: 42, status: 'paid', total_cents: 2598, payment_reference: 'fake', created_at: '2026-11-17T00:00:00Z', items: [] } }, { status: 201 })
      }),
    )
    const router = createMemoryRouter(
      [
        { path: '/checkout', element: <CheckoutPage /> },
        { path: '/orders/:id', element: <p>Order page</p> },
      ],
      { initialEntries: ['/checkout'] },
    )
    render(
      <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
        <RouterProvider router={router} />
      </QueryClientProvider>,
    )

    fireEvent.change(screen.getByLabelText('Card number'), { target: { value: '4242 4242 4242 4242' } })
    fireEvent.change(screen.getByLabelText('Expiry (MM/YY)'), { target: { value: '12/39' } })
    fireEvent.change(screen.getByLabelText('CVC'), { target: { value: '123' } })
    fireEvent.change(screen.getByLabelText('Name on card'), { target: { value: 'Ada Lovelace' } })
    fireEvent.click(await screen.findByRole('button', { name: 'Pay €25.98' }))

    expect(await screen.findByText('Order page')).toBeTruthy()
    expect(JSON.parse(checkoutBodies[0])).toEqual({ items: [{ offer_id: 3, quantity: 2 }], payment_token: 'tok_success' })
    expect(checkoutBodies[0]).not.toContain('4242')
    await waitFor(() => expect(localStorage.getItem('anabe-cart')).toBe('[]'))
  })
})
