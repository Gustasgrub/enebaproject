// The small stand-in for a block that failed to load or to render. The rest of the page is unaffected.
export function BlockUnavailable({ title, onRetry }: { title: string; onRetry?: () => void }) {
  return (
    <section aria-label={title} className="mx-auto max-w-[1200px] px-4 py-6">
      <h2 className="mb-3 text-2xl font-extrabold">{title}</h2>
      <p className="bg-card/60 p-4 text-sm text-white/80">
        This section could not be loaded.{' '}
        {onRetry && (
          <button type="button" className="font-semibold text-accent hover:underline" onClick={onRetry}>
            Try again
          </button>
        )}
      </p>
    </section>
  )
}
