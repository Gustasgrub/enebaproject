import type { ReactNode } from 'react'
import { Navigate, useLocation } from 'react-router'
import { loginPathFor } from './redirect'
import { useSession } from './session'

/** Wraps pages that need a logged-in user (checkout, orders): others go to login and come back after. */
export function RequireAuth({ children }: { children: ReactNode }) {
  const session = useSession()
  const location = useLocation()

  if (session === null) {
    return <Navigate to={loginPathFor(location.pathname + location.search)} replace />
  }

  return children
}
