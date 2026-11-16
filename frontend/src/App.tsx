import { HealthStatus } from './components/HealthStatus'

export function App() {
  return (
    <div className="flex min-h-screen flex-col">
      <header className="px-4 py-4">
        <span className="text-3xl font-extrabold tracking-tight">anabe</span>
      </header>

      <main className="mx-auto w-full max-w-xl flex-1 px-4 py-10">
        <section className="bg-card p-6">
          <h1 className="mb-4 text-xl font-bold">Backend status</h1>
          <HealthStatus />
        </section>
      </main>

      <footer className="bg-nav px-4 py-3 text-center text-sm text-white/70">
        Demo project — not affiliated with Eneba. No real payments.
      </footer>
    </div>
  )
}
