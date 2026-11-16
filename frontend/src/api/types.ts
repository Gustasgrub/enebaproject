// Shapes of the backend's JSON responses. Every API call is typed with one of these.

export type HealthResponse = {
  status: 'ok' | 'error'
  database: string | null
  checked_at: string
}
