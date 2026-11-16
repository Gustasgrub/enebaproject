import { useEffect, useState } from 'react'
import { Link } from 'react-router'
import type { BannerItem, HomepageBlockSummary } from '../../api/types'
import { useBlockData } from './useBlockData'

const ROTATE_EVERY_MS = 7000

// The hero carousel. It moves on by itself; choosing a slide restarts the timer.
export function BannerBlock({ block }: { block: HomepageBlockSummary }) {
  const banners = useBlockData<BannerItem>(block.id)
  const [activeIndex, setActiveIndex] = useState(0)
  const count = banners.data?.data.length ?? 0

  useEffect(() => {
    if (count < 2) return
    const timer = setTimeout(() => setActiveIndex((index) => (index + 1) % count), ROTATE_EVERY_MS)
    return () => clearTimeout(timer)
  }, [activeIndex, count])

  if (banners.isPending) {
    return <div aria-hidden="true" className="h-[260px] animate-pulse bg-card sm:aspect-[7/2] sm:h-auto" />
  }
  // A broken hero would look worse than none: the banner block hides instead of showing a fallback.
  if (banners.isError || count === 0) {
    return null
  }

  const banner = banners.data.data[activeIndex % count]

  return (
    <section aria-roledescription="carousel" aria-label={block.title} className="relative">
      <Link to={banner.link_url} className="relative block h-[260px] overflow-hidden sm:aspect-[7/2] sm:h-auto">
        <img src={banner.image_url} alt="" className="absolute inset-0 h-full w-full object-cover" />
        <div className="relative mx-auto flex h-full max-w-[1200px] flex-col justify-center gap-3 px-4">
          <h2 className="text-3xl font-extrabold sm:text-5xl">{banner.title}</h2>
          <p className="max-w-md sm:text-lg">{banner.subtitle}</p>
          <span className="mt-2 w-fit bg-accent px-8 py-3 font-extrabold uppercase text-card">Shop now</span>
        </div>
      </Link>

      {count > 1 && (
        <div className="absolute inset-x-0 bottom-3 flex justify-center gap-2">
          {banners.data.data.map((item, index) => (
            <button
              key={item.id}
              type="button"
              aria-label={`Show banner ${index + 1}: ${item.title}`}
              aria-current={index === activeIndex % count}
              onClick={() => setActiveIndex(index)}
              className={`h-2 rounded-full transition-all ${index === activeIndex % count ? 'w-8 bg-accent' : 'w-2 bg-white/60'}`}
            />
          ))}
        </div>
      )}
    </section>
  )
}
