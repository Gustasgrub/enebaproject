import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useState, type FormEvent } from 'react'
import { Navigate, useNavigate } from 'react-router'
import { ApiError, apiPost } from '../api/client'
import type { Order } from '../api/types'
import { TextField } from '../components/form/TextField'
import { cart, useCartLines } from '../cart/cartStore'
import { useCartOffers } from '../cart/useCartOffers'
import { formatPrice } from '../lib/money'
import { productTitle } from '../lib/productTitle'
import { isValidExpiry, TEST_CARDS, tokenizeTestCard, type PaymentToken } from '../payments/testCards'

type CardForm = { cardNumber: string; expiry: string; cvc: string; holder: string }

export function CheckoutPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const cartLines = useCartLines()
  const { query, lines, totalCents, hasProblems } = useCartOffers(cartLines)
  const [card, setCard] = useState<CardForm>({ cardNumber: '', expiry: '', cvc: '', holder: '' })
  const [cardErrors, setCardErrors] = useState<Partial<Record<keyof CardForm, string[]>>>({})

  const placeOrder = useMutation({
    // Only offer ids, quantities and the token are sent. Prices come from the database.
    mutationFn: (paymentToken: PaymentToken) =>
      apiPost<{ data: Order }>('/api/checkout', {
        items: cartLines.map((line) => ({ offer_id: line.offerId, quantity: line.quantity })),
        payment_token: paymentToken,
      }),
    onSuccess: (response) => {
      const order = response.data
      queryClient.setQueryData(['order', order.id], response)
      void queryClient.invalidateQueries({ queryKey: ['orders'] })
      void navigate(`/orders/${order.id}?placed=1`, { replace: true })
      cart.clear()
    },
  })

  // An empty cart has nothing to check out, unless it is empty because the order was just placed:
  // onSuccess clears the cart while the mutation still reports "pending", so only an idle one counts.
  if (cartLines.length === 0 && placeOrder.isIdle) {
    return <Navigate to="/cart" replace />
  }

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    const token = tokenizeTestCard(card.cardNumber)
    const errors: typeof cardErrors = {}
    if (token === null) errors.cardNumber = ['Use one of the test cards shown below.']
    if (!isValidExpiry(card.expiry)) errors.expiry = ['Enter a future date as MM/YY.']
    if (!/^\d{3,4}$/.test(card.cvc.trim())) errors.cvc = ['Enter the 3 digits on the back of the card.']
    if (card.holder.trim() === '') errors.holder = ['Enter the name on the card.']
    setCardErrors(errors)

    if (token !== null && Object.keys(errors).length === 0) {
      placeOrder.mutate(token)
    }
  }

  const field = (name: keyof CardForm) => ({
    value: card[name],
    errors: cardErrors[name],
    onChange: (event: { target: { value: string } }) => setCard((current) => ({ ...current, [name]: event.target.value })),
  })

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-8">
      <title>Checkout · anabe</title>
      <h1 className="mb-6 text-3xl font-extrabold">Checkout</h1>

      <div className="grid gap-6 lg:grid-cols-[1fr_380px]">
        <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4 bg-panel p-6">
          <h2 className="text-xl font-extrabold">Payment</h2>
          <p className="bg-card p-4 text-sm">
            Demo checkout, no real payment. Use a test card with any future expiry date and any 3-digit CVC:
          </p>
          <ul className="-mt-2 bg-card px-4 pb-4 text-sm">
            {TEST_CARDS.map((testCard) => (
              <li key={testCard.number}>
                <code className="font-bold">{testCard.number}</code>: {testCard.outcome.toLowerCase()}
              </li>
            ))}
          </ul>

          <TextField label="Card number" inputMode="numeric" autoComplete="cc-number" placeholder="4242 4242 4242 4242" {...field('cardNumber')} />
          <div className="grid grid-cols-2 gap-4">
            <TextField label="Expiry (MM/YY)" inputMode="numeric" autoComplete="cc-exp" placeholder="12/29" {...field('expiry')} />
            <TextField label="CVC" inputMode="numeric" autoComplete="cc-csc" placeholder="123" {...field('cvc')} />
          </div>
          <TextField label="Name on card" autoComplete="cc-name" {...field('holder')} />

          {placeOrder.isError && (
            <p role="alert" className="bg-card p-4 font-semibold text-red-300">
              {placeOrder.error instanceof ApiError && [402, 409].includes(placeOrder.error.status)
                ? placeOrder.error.message
                : 'The order could not be placed. Please try again.'}
            </p>
          )}

          <button
            type="submit"
            disabled={placeOrder.isPending || query.isPending || hasProblems}
            className="bg-accent py-3 font-extrabold text-card disabled:opacity-60"
          >
            {placeOrder.isPending ? 'Paying…' : `Pay ${formatPrice(totalCents)}`}
          </button>
        </form>

        <aside className="h-fit bg-card p-6">
          <h2 className="mb-4 text-xl font-extrabold">Order summary</h2>
          <ul className="flex flex-col gap-3 text-sm">
            {lines.map((line) => (
              <li key={line.offerId} className="flex justify-between gap-4">
                <span>
                  {line.offer?.product ? productTitle(line.offer.product) : 'Unavailable offer'} × {line.quantity}
                </span>
                <span className="font-bold whitespace-nowrap">{line.offer ? formatPrice(line.offer.price_cents * line.quantity) : '–'}</span>
              </li>
            ))}
          </ul>
          <p className="mt-4 flex justify-between border-t border-white/20 pt-4 text-lg font-extrabold">
            <span>Total</span>
            <span>{formatPrice(totalCents)}</span>
          </p>
          {hasProblems && <p className="mt-3 text-sm font-semibold text-red-300">Some items changed. Review your cart before paying.</p>}
        </aside>
      </div>
    </div>
  )
}
