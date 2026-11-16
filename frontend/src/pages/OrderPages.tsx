import { useQuery } from '@tanstack/react-query'
import { Link, useParams, useSearchParams } from 'react-router'
import { ApiError, apiGet } from '../api/client'
import type { ListResponse, Order } from '../api/types'
import { OrderSummary } from '../components/orders/OrderSummary'
import { NotFoundPage } from './NotFoundPage'

/** /orders/:id: one order. Right after paying (?placed=1) it is the confirmation page with the keys. */
export function OrderPage() {
  const { id = '' } = useParams()
  const [searchParams] = useSearchParams()
  const justPlaced = searchParams.has('placed')
  const order = useQuery({
    queryKey: ['order', Number(id)],
    queryFn: ({ signal }) => apiGet<{ data: Order }>(`/api/orders/${encodeURIComponent(id)}`, undefined, signal),
  })

  if (order.isError && order.error instanceof ApiError && order.error.status === 404) {
    return <NotFoundPage />
  }

  return (
    <div className="mx-auto max-w-3xl px-4 py-8">
      <title>{justPlaced ? 'Thank you · anabe' : `Order #${id} · anabe`}</title>
      {justPlaced && (
        <div className="mb-6">
          <h1 className="text-3xl font-extrabold">Payment successful</h1>
          <p className="mt-2 text-white/80">Your keys are below. Activate them on the platform shown; they also stay in your orders.</p>
        </div>
      )}
      {order.isPending ? (
        <div aria-hidden="true" className="h-48 animate-pulse bg-card" />
      ) : order.isError ? (
        <p className="bg-card p-6 font-semibold">This order could not be loaded.</p>
      ) : (
        <OrderSummary order={order.data.data} />
      )}
      <Link to="/orders" className="mt-6 inline-block font-bold text-accent hover:underline">
        All my orders
      </Link>
    </div>
  )
}

/** /orders: the order history, with the keys of every paid order. */
export function OrdersPage() {
  const orders = useQuery({
    queryKey: ['orders'],
    queryFn: ({ signal }) => apiGet<ListResponse<Order>>('/api/orders', undefined, signal),
  })

  return (
    <div className="mx-auto max-w-3xl px-4 py-8">
      <title>My orders · anabe</title>
      <h1 className="mb-6 text-3xl font-extrabold">My orders</h1>
      {orders.isPending ? (
        <div aria-hidden="true" className="h-48 animate-pulse bg-card" />
      ) : orders.isError ? (
        <p className="bg-card p-6 font-semibold">Your orders could not be loaded.</p>
      ) : orders.data.data.length === 0 ? (
        <div className="bg-card p-6">
          <p className="mb-3 font-semibold">No orders yet.</p>
          <Link to="/store" className="font-bold text-accent hover:underline">
            Find a game
          </Link>
        </div>
      ) : (
        <div className="flex flex-col gap-4">
          {orders.data.data.map((order) => (
            <OrderSummary key={order.id} order={order} />
          ))}
        </div>
      )}
    </div>
  )
}
