import { useSyncExternalStore } from 'react'

// The logged-in session: the API token and who it belongs to, kept in localStorage so a reload keeps
// the user logged in. The frontend and the API are on different sites, so the token is sent as a
// bearer header instead of a cookie; the cost is that page scripts can read it (docs/DECISIONS.md #24).

export type SessionUser = { id: number; name: string; email: string; is_admin?: boolean }
export type Session = { token: string; user: SessionUser }

const STORAGE_KEY = 'anabe-session'

let session: Session | null = readStoredSession()
const listeners = new Set<() => void>()

function readStoredSession(): Session | null {
  try {
    const parsed: unknown = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null')
    return isSession(parsed) ? parsed : null
  } catch {
    return null
  }
}

function isSession(value: unknown): value is Session {
  return typeof value === 'object' && value !== null && typeof (value as Session).token === 'string' && typeof (value as Session).user === 'object'
}

function update(nextSession: Session | null) {
  session = nextSession
  try {
    if (nextSession) {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(nextSession))
    } else {
      localStorage.removeItem(STORAGE_KEY)
    }
  } catch {
    // Without storage the session lasts until the page is reloaded.
  }
  listeners.forEach((listener) => listener())
}

// Logging in or out in another tab applies here too.
window.addEventListener('storage', (event) => {
  if (event.key === STORAGE_KEY) {
    session = readStoredSession()
    listeners.forEach((listener) => listener())
  }
})

export function startSession(nextSession: Session) {
  update(nextSession)
}

export function endSession() {
  update(null)
}

/** For code outside React (the API client). */
export function currentToken(): string | null {
  return session?.token ?? null
}

/** The session, or null when logged out; components re-render when it changes. */
export function useSession(): Session | null {
  return useSyncExternalStore(
    (listener) => {
      listeners.add(listener)
      return () => listeners.delete(listener)
    },
    () => session,
  )
}
