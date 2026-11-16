import { Link } from 'react-router'

export function NotFoundPage() {
  return (
    <div className="mx-auto max-w-[1200px] px-4 py-16 text-center">
      <title>Page not found · anabe</title>
      <h1 className="mb-4 text-3xl font-extrabold">Page not found</h1>
      <Link to="/store" className="font-bold text-accent hover:underline">
        Browse all games
      </Link>
    </div>
  )
}
