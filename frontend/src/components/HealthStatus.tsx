import { useQuery } from '@tanstack/react-query'
import { apiGet } from '../api/client'
import type { HealthResponse } from '../api/types'

// Shows that the frontend can reach the backend and that the backend can reach its database.
export function HealthStatus() {
  const health = useQuery({
    queryKey: ['health'],
    queryFn: () => apiGet<HealthResponse>('/api/health'),
  })

  if (health.isPending) {
    return <p className="text-white/70">Checking the backend…</p>
  }

  if (health.isError) {
    return <p className="font-semibold text-accent">Backend unavailable: {health.error.message}</p>
  }

  return (
    <dl className="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2">
      <dt className="text-white/70">API</dt>
      <dd className="font-semibold text-discount">{health.data.status}</dd>
      <dt className="text-white/70">Database</dt>
      <dd>{health.data.database}</dd>
      <dt className="text-white/70">Checked at</dt>
      <dd>{new Date(health.data.checked_at).toLocaleString()}</dd>
    </dl>
  )
}
