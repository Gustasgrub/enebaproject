import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState, type FormEvent } from 'react'
import { ApiError, apiDelete, apiGet, apiPatch, apiPost } from '../api/client'
import type { ListResponse, Platform, ProductDetail, ProductSummary } from '../api/types'
import { useSession } from '../auth/session'
import { productTitle } from '../lib/productTitle'
import { NotFoundPage } from './NotFoundPage'

type AdminBlock = { id: number; type: string; title: string; position: number; enabled: boolean; config: Record<string, string> }
type AdminBanner = { id: number; title: string; subtitle: string; active: boolean }

const platforms: Platform[] = ['Steam', 'PlayStation', 'Xbox', 'Nintendo', 'EA App', 'Ubisoft Connect']
const sectionClass = 'bg-card p-6'
const buttonClass = 'bg-chip px-3 py-1.5 text-sm font-bold hover:bg-nav disabled:opacity-40'

// The back office: homepage layout, banners and key stock. The API checks the admin rights itself;
// this check only keeps the page out of other users' way.
export function AdminPage() {
  const session = useSession()

  if (!session?.user.is_admin) {
    return <NotFoundPage />
  }

  return (
    <div className="mx-auto flex max-w-4xl flex-col gap-6 px-4 py-8">
      <title>Admin · anabe</title>
      <h1 className="text-3xl font-extrabold">Admin</h1>
      <HomepageBlocksSection />
      <BannersSection />
      <KeyUploadSection />
    </div>
  )
}

function useAdminInvalidate() {
  const queryClient = useQueryClient()
  return () => {
    void queryClient.invalidateQueries({ queryKey: ['admin'] })
    void queryClient.invalidateQueries({ queryKey: ['homepage'] })
    void queryClient.invalidateQueries({ queryKey: ['homepage-block'] })
  }
}

function HomepageBlocksSection() {
  const invalidate = useAdminInvalidate()
  const blocks = useQuery({
    queryKey: ['admin', 'blocks'],
    queryFn: () => apiGet<ListResponse<AdminBlock> & { types: string[] }>('/api/admin/homepage-blocks'),
  })
  const change = useMutation({ mutationFn: (request: () => Promise<unknown>) => request(), onSuccess: invalidate })
  const [newBlock, setNewBlock] = useState({ type: 'platform_row', title: '', platform: 'PlayStation' })

  if (!blocks.data) {
    return <section className={sectionClass}>Loading the homepage layout…</section>
  }
  const list = blocks.data.data

  function move(index: number, offset: number) {
    const ids = list.map((block) => block.id)
    ;[ids[index], ids[index + offset]] = [ids[index + offset], ids[index]]
    change.mutate(() => apiPost('/api/admin/homepage-blocks/order', { ids }))
  }

  function addBlock(event: FormEvent) {
    event.preventDefault()
    const config = newBlock.type === 'platform_row' ? { platform: newBlock.platform } : {}
    change.mutate(() => apiPost('/api/admin/homepage-blocks', { type: newBlock.type, title: newBlock.title, config }))
    setNewBlock((current) => ({ ...current, title: '' }))
  }

  return (
    <section className={sectionClass} aria-labelledby="blocks-heading">
      <h2 id="blocks-heading" className="mb-1 text-xl font-extrabold">
        Homepage blocks
      </h2>
      <p className="mb-4 text-sm text-white/70">Top to bottom as on the homepage. Changes are live immediately.</p>

      <ul className="flex flex-col gap-2">
        {list.map((block, index) => (
          <li key={block.id} className={`flex flex-wrap items-center gap-3 bg-page p-3 ${block.enabled ? '' : 'opacity-60'}`}>
            <span className="w-28 text-xs font-bold uppercase text-white/70">{block.type}</span>
            <span className="flex-1 font-semibold">
              {block.title}
              {block.config.platform && <span className="text-white/70"> ({block.config.platform})</span>}
            </span>
            <button type="button" className={buttonClass} disabled={index === 0} onClick={() => move(index, -1)} aria-label={`Move ${block.title} up`}>
              ↑
            </button>
            <button type="button" className={buttonClass} disabled={index === list.length - 1} onClick={() => move(index, 1)} aria-label={`Move ${block.title} down`}>
              ↓
            </button>
            <button
              type="button"
              className={buttonClass}
              onClick={() => change.mutate(() => apiPatch(`/api/admin/homepage-blocks/${block.id}`, { enabled: !block.enabled }))}
            >
              {block.enabled ? 'Hide' : 'Show'}
            </button>
            <button
              type="button"
              className={buttonClass}
              onClick={() => {
                if (window.confirm(`Delete the block "${block.title}"?`)) {
                  change.mutate(() => apiDelete(`/api/admin/homepage-blocks/${block.id}`))
                }
              }}
            >
              Delete
            </button>
          </li>
        ))}
      </ul>

      <form onSubmit={addBlock} className="mt-4 flex flex-wrap items-end gap-3">
        <label className="flex flex-col gap-1 text-sm font-semibold">
          Type
          <select className="bg-field px-3 py-2" value={newBlock.type} onChange={(event) => setNewBlock({ ...newBlock, type: event.target.value })}>
            {blocks.data.types.map((type) => (
              <option key={type}>{type}</option>
            ))}
          </select>
        </label>
        {newBlock.type === 'platform_row' && (
          <label className="flex flex-col gap-1 text-sm font-semibold">
            Platform
            <select className="bg-field px-3 py-2" value={newBlock.platform} onChange={(event) => setNewBlock({ ...newBlock, platform: event.target.value })}>
              {platforms.map((platform) => (
                <option key={platform}>{platform}</option>
              ))}
            </select>
          </label>
        )}
        <label className="flex flex-1 flex-col gap-1 text-sm font-semibold">
          Title
          <input required className="bg-field px-3 py-2" value={newBlock.title} onChange={(event) => setNewBlock({ ...newBlock, title: event.target.value })} />
        </label>
        <button type="submit" className="bg-accent px-4 py-2 font-extrabold text-card">
          Add block
        </button>
      </form>
      {change.isError && <ErrorLine error={change.error} />}
    </section>
  )
}

function BannersSection() {
  const invalidate = useAdminInvalidate()
  const banners = useQuery({ queryKey: ['admin', 'banners'], queryFn: () => apiGet<ListResponse<AdminBanner>>('/api/admin/banners') })
  const toggle = useMutation({
    mutationFn: (banner: AdminBanner) => apiPatch(`/api/admin/banners/${banner.id}`, { active: !banner.active }),
    onSuccess: invalidate,
  })

  return (
    <section className={sectionClass} aria-labelledby="banners-heading">
      <h2 id="banners-heading" className="mb-4 text-xl font-extrabold">
        Hero banners
      </h2>
      <ul className="flex flex-col gap-2">
        {banners.data?.data.map((banner) => (
          <li key={banner.id} className="flex items-center gap-3 bg-page p-3">
            <span className="flex-1">
              <span className="font-semibold">{banner.title}</span> <span className="text-sm text-white/70">{banner.subtitle}</span>
            </span>
            <label className="flex items-center gap-2 text-sm font-semibold">
              <input type="checkbox" className="size-4 accent-accent" checked={banner.active} onChange={() => toggle.mutate(banner)} />
              Active
            </label>
          </li>
        ))}
      </ul>
      {toggle.isError && <ErrorLine error={toggle.error} />}
    </section>
  )
}

function KeyUploadSection() {
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')
  const [slug, setSlug] = useState<string | null>(null)
  const [offerId, setOfferId] = useState<number | null>(null)
  const [codesText, setCodesText] = useState('')

  const matches = useQuery({
    queryKey: ['suggest', search.trim()],
    queryFn: () => apiGet<ListResponse<ProductSummary>>('/api/search/suggest', new URLSearchParams({ q: search.trim() })),
    enabled: search.trim().length >= 2,
  })
  const product = useQuery({
    queryKey: ['product', slug],
    queryFn: () => apiGet<{ data: ProductDetail }>(`/api/products/${slug}`),
    enabled: slug !== null,
  })
  const upload = useMutation({
    mutationFn: (codes: string[]) => apiPost<{ data: { added: number; stock: number } }>(`/api/admin/offers/${offerId}/keys`, { codes }),
    onSuccess: () => {
      setCodesText('')
      void queryClient.invalidateQueries({ queryKey: ['product', slug] })
    },
  })

  function submit(event: FormEvent) {
    event.preventDefault()
    // One code per line; blank lines are ignored.
    const codes = codesText.split('\n').map((code) => code.trim()).filter(Boolean)
    if (offerId !== null && codes.length > 0) upload.mutate(codes)
  }

  return (
    <section className={sectionClass} aria-labelledby="keys-heading">
      <h2 id="keys-heading" className="mb-4 text-xl font-extrabold">
        Add keys to an offer
      </h2>
      <input
        aria-label="Find a product"
        placeholder="Find a product, e.g. elden ring"
        className="w-full bg-field px-3 py-2"
        value={search}
        onChange={(event) => {
          setSearch(event.target.value)
          setSlug(null)
          setOfferId(null)
        }}
      />
      {slug === null && (
        <ul className="mt-2 flex flex-col">
          {matches.data?.data.map((match) => (
            <li key={match.id}>
              <button type="button" className="w-full px-3 py-1.5 text-left hover:bg-chip" onClick={() => setSlug(match.slug)}>
                {productTitle(match)}
              </button>
            </li>
          ))}
        </ul>
      )}

      {product.data && (
        <form onSubmit={submit} className="mt-4 flex flex-col gap-3">
          <p className="font-bold">{productTitle(product.data.data)}</p>
          <label className="flex flex-col gap-1 text-sm font-semibold">
            Seller offer
            <select className="bg-field px-3 py-2" value={offerId ?? ''} onChange={(event) => setOfferId(Number(event.target.value))} required>
              <option value="" disabled>
                Choose an offer
              </option>
              {product.data.data.offers.map((offer) => (
                <option key={offer.id} value={offer.id}>
                  {offer.seller.name}: {offer.stock} in stock
                </option>
              ))}
            </select>
          </label>
          <label className="flex flex-col gap-1 text-sm font-semibold">
            Key codes, one per line
            <textarea rows={4} className="bg-field px-3 py-2 font-mono" value={codesText} onChange={(event) => setCodesText(event.target.value)} />
          </label>
          <button type="submit" disabled={upload.isPending || offerId === null} className="w-fit bg-accent px-4 py-2 font-extrabold text-card disabled:opacity-60">
            Add keys
          </button>
          {upload.isSuccess && (
            <p className="font-semibold text-mint">
              Added {upload.data.data.added} keys; this offer now has {upload.data.data.stock} in stock.
            </p>
          )}
          {upload.isError && <ErrorLine error={upload.error} />}
        </form>
      )}
    </section>
  )
}

function ErrorLine({ error }: { error: Error }) {
  const details = error instanceof ApiError ? Object.values(error.fieldErrors).flat().join(' ') : ''
  return <p className="mt-3 font-semibold text-red-300">{details || error.message}</p>
}
