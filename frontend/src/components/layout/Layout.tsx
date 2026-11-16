import { Link, NavLink, Outlet, ScrollRestoration, useLocation, useSearchParams } from 'react-router'
import { useQueryClient } from '@tanstack/react-query'
import { apiPost } from '../../api/client'
import { loginPathFor } from '../../auth/redirect'
import { endSession, useSession } from '../../auth/session'
import { useCartLines } from '../../cart/cartStore'
import { ServerWakeUpNotice } from '../ServerWakeUpNotice'
import { SearchBar } from './SearchBar'

const platformLinks = ['Steam', 'PlayStation', 'Xbox', 'Nintendo', 'EA App', 'Ubisoft Connect']

// The frame around every page: header with search, the secondary nav bar, and the demo disclaimer.
export function Layout() {
  const location = useLocation()
  const [searchParams] = useSearchParams()
  // On the results page the search box shows the current query. The key starts the box over whenever
  // that query changes (e.g. a "Did you mean" link), instead of syncing it with an effect.
  const searchText = location.pathname === '/search' ? (searchParams.get('q') ?? '') : ''

  return (
    <div className="flex min-h-screen flex-col">
      <ServerWakeUpNotice />

      <header className="bg-page">
        <div className="mx-auto flex max-w-[1200px] flex-wrap items-center gap-x-8 gap-y-3 px-4 py-4 md:flex-nowrap">
          <Link to="/" className="flex items-center gap-2 text-3xl font-extrabold tracking-tight" aria-label="anabe home">
            {/* Our own mark: two overlapping discs in the accent colours (not Eneba's logo). */}
            <svg aria-hidden="true" viewBox="0 0 32 32" className="size-8">
              <circle cx="13" cy="16" r="11" className="fill-accent" />
              <circle cx="21" cy="16" r="9" className="fill-mint opacity-90" />
            </svg>
            anabe
          </Link>
          <div className="order-last w-full md:order-none md:max-w-[560px] md:flex-1">
            <SearchBar key={searchText} initialText={searchText} />
          </div>
          <nav aria-label="Account" className="ml-auto flex items-center gap-5 text-sm font-semibold">
            <CartLink />
            <AccountLinks />
          </nav>
        </div>

        <nav aria-label="Categories" className="bg-nav">
          <div className="mx-auto flex max-w-[1200px] gap-6 overflow-x-auto px-4 py-3 text-sm font-semibold whitespace-nowrap">
            <NavLink to="/store" end className={({ isActive }) => (isActive && !location.search ? 'text-accent' : 'hover:text-accent')}>
              All games
            </NavLink>
            {platformLinks.map((platform) => (
              <Link key={platform} to={`/store?platform=${encodeURIComponent(platform)}`} className="hover:text-accent">
                {platform}
              </Link>
            ))}
          </div>
        </nav>
      </header>

      <main className="flex-1">
        <Outlet />
      </main>

      <footer className="bg-nav px-4 py-4 text-center text-sm text-white/70">
        Demo project — not affiliated with Eneba. No real payments.
      </footer>

      <ScrollRestoration />
    </div>
  )
}

function AccountLinks() {
  const session = useSession()
  const queryClient = useQueryClient()
  const location = useLocation()

  if (session === null) {
    const loginPath = loginPathFor(location.pathname + location.search)
    return (
      <span>
        <Link to={loginPath} className="hover:text-accent">
          Log in
        </Link>
        <span className="mx-1.5 text-white/60">|</span>
        <Link to={`/register${loginPath.slice('/login'.length)}`} className="hover:text-accent">
          Register
        </Link>
      </span>
    )
  }

  async function logOut() {
    try {
      await apiPost('/api/logout')
    } finally {
      // Logged out locally even if the server could not be reached; its token expires on its own.
      endSession()
      queryClient.removeQueries({ queryKey: ['orders'] })
    }
  }

  return (
    <span className="flex items-center gap-4">
      {session.user.is_admin && (
        <Link to="/admin" className="hover:text-accent">
          Admin
        </Link>
      )}
      <Link to="/orders" className="hover:text-accent">
        My orders
      </Link>
      <span className="hidden text-white/70 sm:inline">{session.user.name}</span>
      <button type="button" className="hover:text-accent" onClick={() => void logOut()}>
        Log out
      </button>
    </span>
  )
}

function CartLink() {
  const itemCount = useCartLines().reduce((sum, line) => sum + line.quantity, 0)

  return (
    <Link to="/cart" className="relative flex items-center gap-2 hover:text-accent" aria-label={`Cart, ${itemCount} items`}>
      <svg aria-hidden="true" viewBox="0 0 24 24" className="size-6 fill-none stroke-current stroke-2">
        <path d="M3 4h2l2.4 11.2a1 1 0 0 0 1 .8h9.2a1 1 0 0 0 1-.8L20 8H6" />
        <circle cx="9" cy="20" r="1.4" />
        <circle cx="17" cy="20" r="1.4" />
      </svg>
      {itemCount > 0 && (
        <span className="absolute -top-2 -right-3 grid size-5 place-items-center rounded-full bg-accent text-xs font-extrabold text-card">
          {itemCount}
        </span>
      )}
    </Link>
  )
}
