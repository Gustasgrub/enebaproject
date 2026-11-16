import { render, screen } from '@testing-library/react'
import { createMemoryRouter, RouterProvider, useLocation } from 'react-router'
import { afterEach, describe, expect, it } from 'vitest'
import { safeRedirectPath } from './redirect'
import { RequireAuth } from './RequireAuth'
import { endSession, startSession } from './session'

function CurrentLocation() {
  const location = useLocation()
  return <p>At {location.pathname + location.search}</p>
}

function renderAt(path: string) {
  const router = createMemoryRouter(
    [
      { path: '/checkout', element: <RequireAuth><p>Checkout page</p></RequireAuth> },
      { path: '/login', element: <CurrentLocation /> },
    ],
    { initialEntries: [path] },
  )
  render(<RouterProvider router={router} />)
}

describe('RequireAuth', () => {
  afterEach(() => endSession())

  it('sends a logged-out visitor to login, with the way back in the URL', () => {
    renderAt('/checkout?step=pay')

    expect(screen.getByText('At /login?redirect=%2Fcheckout%3Fstep%3Dpay')).toBeTruthy()
  })

  it('shows the page to a logged-in user', () => {
    startSession({ token: 't', user: { id: 1, name: 'Ada', email: 'ada@example.com' } })

    renderAt('/checkout')

    expect(screen.getByText('Checkout page')).toBeTruthy()
  })
})

describe('safeRedirectPath', () => {
  it('accepts paths on this site and rejects other sites', () => {
    expect(safeRedirectPath('/checkout')).toBe('/checkout')
    expect(safeRedirectPath('//evil.example')).toBe('/')
    expect(safeRedirectPath('https://evil.example')).toBe('/')
    expect(safeRedirectPath(null)).toBe('/')
  })
})
