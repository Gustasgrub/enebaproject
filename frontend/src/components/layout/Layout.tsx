import { Link, NavLink, Outlet, ScrollRestoration, useLocation, useSearchParams } from 'react-router'
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
          <Link to="/" className="text-3xl font-extrabold tracking-tight">
            anabe
          </Link>
          <div className="order-last w-full md:order-none md:max-w-[560px] md:flex-1">
            <SearchBar key={searchText} initialText={searchText} />
          </div>
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
