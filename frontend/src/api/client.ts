// The one way the frontend talks to the backend: the base URL and error handling are defined here once.

const apiBaseUrl = import.meta.env.VITE_API_URL

export class ApiError extends Error {
  readonly status: number

  constructor(status: number, message: string) {
    super(message)
    this.name = 'ApiError'
    this.status = status
  }
}

/**
 * GET a backend path, e.g. apiGet<ProductListResponse>('/api/products', query).
 * `signal` comes from TanStack Query, which aborts requests whose result is no longer needed
 * (for example a suggestion request for text the user has already changed).
 */
export async function apiGet<T>(path: string, query?: URLSearchParams, signal?: AbortSignal): Promise<T> {
  const queryString = query && query.size > 0 ? `?${query.toString()}` : ''
  const response = await fetch(`${apiBaseUrl}${path}${queryString}`, {
    headers: { Accept: 'application/json' },
    signal,
  })

  if (!response.ok) {
    throw new ApiError(response.status, `GET ${path} failed with status ${response.status}`)
  }

  // The response types in ./types.ts describe the backend's contract; the cast trusts that contract.
  return (await response.json()) as T
}
