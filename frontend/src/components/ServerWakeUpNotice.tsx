import { useIsFetching } from '@tanstack/react-query'
import { useEffect, useState } from 'react'

// A request taking longer than this most likely means the backend is starting up.
const slowRequestThresholdMs = 3000

// The backend runs on Render's free plan, which puts it to sleep after 15 minutes without traffic.
// The next request then waits 30–60 seconds while it starts, and without a hint the page looks broken.
// This watches every request made through TanStack Query, so no page needs to handle it itself.
export function ServerWakeUpNotice() {
  const isFetching = useIsFetching() > 0
  const [isSlow, setIsSlow] = useState(false)

  useEffect(() => {
    if (!isFetching) {
      return
    }

    const timer = setTimeout(() => setIsSlow(true), slowRequestThresholdMs)

    // Runs when all requests have finished (or on unmount): cancel the timer and hide the notice.
    return () => {
      clearTimeout(timer)
      setIsSlow(false)
    }
  }, [isFetching])

  if (!isSlow) {
    return null
  }

  return (
    <p role="status" className="bg-accent px-4 py-2 text-center text-sm font-semibold text-card">
      Waking up the server… It sleeps when nobody uses it, so the first load can take up to a minute.
    </p>
  )
}
