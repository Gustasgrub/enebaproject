import { useQuery } from '@tanstack/react-query'
import { apiGet } from '../../api/client'
import type { ListResponse } from '../../api/types'

/**
 * One homepage block's items, from its own endpoint with its own query: each block loads, fails and
 * retries independently of the others. The item type is fixed by the block type (the API contract).
 */
export function useBlockData<Item>(blockId: number) {
  return useQuery({
    queryKey: ['homepage-block', blockId],
    queryFn: ({ signal }) => apiGet<ListResponse<Item>>(`/api/homepage/blocks/${blockId}`, undefined, signal),
  })
}
