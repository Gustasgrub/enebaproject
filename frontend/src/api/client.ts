// The one way the frontend talks to the backend: base URL, auth header and error handling live here once.

import { currentToken, endSession } from '../auth/session'

const apiBaseUrl = import.meta.env.VITE_API_URL

export class ApiError extends Error {
  readonly status: number
  /** Laravel's validation messages per field (422 responses), e.g. { email: ['…'] }. */
  readonly fieldErrors: Record<string, string[]>

  constructor(status: number, message: string, fieldErrors: Record<string, string[]> = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.fieldErrors = fieldErrors
  }
}

type RequestOptions = {
  query?: URLSearchParams
  body?: unknown
  signal?: AbortSignal
}

async function request<T>(method: 'GET' | 'POST' | 'PATCH' | 'DELETE', path: string, { query, body, signal }: RequestOptions): Promise<T> {
  const token = currentToken()
  const queryString = query && query.size > 0 ? `?${query.toString()}` : ''

  const response = await fetch(`${apiBaseUrl}${path}${queryString}`, {
    method,
    headers: {
      Accept: 'application/json',
      ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: body === undefined ? undefined : JSON.stringify(body),
    signal,
  })

  // The token expired or was revoked: drop it, so the app shows the user as logged out.
  if (response.status === 401 && token) {
    endSession()
  }

  if (!response.ok) {
    const payload = await readErrorPayload(response)
    throw new ApiError(response.status, payload.message ?? `${method} ${path} failed with status ${response.status}`, payload.errors)
  }

  if (response.status === 204) {
    return undefined as T
  }

  // The response types in ./types.ts describe the backend's contract; the cast trusts that contract.
  return (await response.json()) as T
}

async function readErrorPayload(response: Response): Promise<{ message?: string; errors?: Record<string, string[]> }> {
  try {
    const payload: unknown = await response.json()
    return typeof payload === 'object' && payload !== null ? payload : {}
  } catch {
    return {}
  }
}

/**
 * GET a backend path, e.g. apiGet<ProductListResponse>('/api/products', query).
 * `signal` comes from TanStack Query, which aborts requests whose result is no longer needed
 * (for example a suggestion request for text the user has already changed).
 */
export function apiGet<T>(path: string, query?: URLSearchParams, signal?: AbortSignal): Promise<T> {
  return request<T>('GET', path, { query, signal })
}

/** POST a JSON body to a backend path. */
export function apiPost<T>(path: string, body?: unknown): Promise<T> {
  return request<T>('POST', path, { body })
}

/** PATCH a JSON body to a backend path (admin edits). */
export function apiPatch<T>(path: string, body: unknown): Promise<T> {
  return request<T>('PATCH', path, { body })
}

/** DELETE a backend path. */
export function apiDelete(path: string): Promise<void> {
  return request<void>('DELETE', path, {})
}
