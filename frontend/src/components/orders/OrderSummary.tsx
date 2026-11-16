import { useState } from 'react'
import { Link } from 'react-router'
import type { Order, OrderStatus } from '../../api/types'
import { formatPrice } from '../../lib/money'
import { productTitle } from '../../lib/productTitle'

const statusLabels: Record<OrderStatus, string> = { paid: 'Paid', pending: 'Processing', failed: 'Payment failed' }
const statusClasses: Record<OrderStatus, string> = { paid: 'bg-mint text-card', pending: 'bg-accent text-card', failed: 'bg-red-300 text-card' }

/** One order: date, status, total, and each item with its key codes (paid orders only). */
export function OrderSummary({ order }: { order: Order }) {
  return (
    <article className="bg-card p-5">
      <header className="mb-4 flex flex-wrap items-center gap-x-4 gap-y-2">
        <Link to={`/orders/${order.id}`} className="font-extrabold hover:underline">
          Order #{order.id}
        </Link>
        <span className="text-sm text-white/70">
          {new Date(order.created_at).toLocaleString('en-IE', { dateStyle: 'medium', timeStyle: 'short' })}
        </span>
        <span className={`px-2 py-0.5 text-xs font-extrabold uppercase ${statusClasses[order.status]}`}>{statusLabels[order.status]}</span>
        <span className="ml-auto text-lg font-extrabold">{formatPrice(order.total_cents)}</span>
      </header>

      <ul className="flex flex-col gap-4">
        {order.items.map((item) => (
          <li key={item.id} className="flex gap-4">
            <img src={item.product.image_url} alt="" className="aspect-[3/4] w-14 shrink-0 object-cover" />
            <div className="min-w-0 flex-1">
              <Link to={`/product/${item.product.slug}`} className="font-bold hover:underline">
                {productTitle(item.product)}
              </Link>
              <p className="text-sm text-white/70">
                {item.quantity} × {formatPrice(item.unit_price_cents)} from {item.seller}
              </p>
              {item.keys.length > 0 && (
                <ul className="mt-2 flex flex-col gap-2" aria-label="Your keys">
                  {item.keys.map((code) => (
                    <KeyCode key={code} code={code} />
                  ))}
                </ul>
              )}
            </div>
          </li>
        ))}
      </ul>
    </article>
  )
}

function KeyCode({ code }: { code: string }) {
  const [copied, setCopied] = useState(false)

  return (
    <li className="flex flex-wrap items-center gap-3">
      <code className="bg-page px-3 py-1.5 font-mono text-base font-bold tracking-wider">{code}</code>
      <button
        type="button"
        className="text-sm font-semibold text-accent hover:underline"
        onClick={() => {
          void navigator.clipboard?.writeText(code).then(() => setCopied(true))
        }}
      >
        {copied ? 'Copied' : 'Copy'}
      </button>
    </li>
  )
}
