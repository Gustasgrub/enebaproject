# anabe — Eneba-style marketplace — Project Plan

This file is the single source of truth for the project. Read it fully before starting any work,
and re-read the relevant phase before starting that phase.

## 1. Goal

Build a digital-game-key marketplace in the style of eneba.com (Eneba Intern Assignment 2027)
with a working purchase flow:

```
Homepage / Search → Product page → Cart → (sign in) → Checkout → Payment → Order + key
```

**What the brief requires** (summary; the brief itself is the authority):
- Parts: Store, Homepage built from blocks, Search (typos, partial names), Product page with offers
  from several sellers, Checkout with simulated payment and key delivery, Authentication, Eneba styling.
- React frontend (TypeScript preferred), PHP backend, REST, any database.
- Scope is ours to decide and justify: "a few features done extensively beat many fitting minimal
  requirements". The author must be able to explain all of the code.
- Deadline: 7 days from receiving the assignment. Received 2026-11-09, so due **2026-11-16**.
- **Deliverables (no evaluation without them):** GitHub link, public URL on a free tier,
  full agent chat history (prompts and responses) as Markdown.

**Scope decision:** every part of the flow works end-to-end at a solid baseline, and two parts
are built in depth:

1. **Typo-tolerant search** (Section 6)
2. **Homepage assembled from independent blocks** (Section 7)

Everything else (auth, cart, checkout, payment) is done correctly but kept simple.
Each simplification is documented in `docs/DECISIONS.md` with the reason.

**Branding:** the site is called **anabe**. It follows Eneba's look (colors, layout, components)
but uses its own name and wordmark, never Eneba's logo, and every page shows a small
"Demo project — not affiliated with Eneba. No real payments." note. A public look-alike of a real
store with a card form is exactly what phishing sites look like; this keeps the demo URL from being
flagged and keeps it honest. Render resource names use `anabe-*`, never `eneba`.

## 2. Tech stack

| Layer          | Choice                                                    | Why |
|----------------|-----------------------------------------------------------|-----|
| Frontend       | React 19 + **TypeScript** (strict) + Vite                 | Required React; TypeScript catches errors early |
| Data fetch     | TanStack Query v5                                         | Caching, loading/error states per request |
| Routing        | React Router (library mode)                               | Standard, simple |
| Styling        | Tailwind CSS v4, design tokens in CSS `@theme`            | Eneba values defined once, reused everywhere |
| Frontend tests | Vitest + React Testing Library + jsdom                    | Needed for the failing-block test (Section 7) |
| Backend        | **PHP 8.5 + current Laravel major**, REST API             | Required PHP; migrations, seeders, auth, tests built in |
| App server     | **FrankenPHP, classic mode** (no worker mode)             | Several PHP workers in one container; nothing shared between requests |
| Auth           | Laravel Sanctum (API tokens)                              | Frontend and backend are different sites (Section 10) |
| Database       | PostgreSQL with `pg_trgm`                                 | Fuzzy search without an extra service |
| Local dev      | Docker Compose (Postgres only)                            | One command to get a database |
| Hosting        | **Render Blueprint** (`render.yaml`): Static Site + Docker Web Service + Render Postgres, all free, region Frankfurt | One platform, one file, database on Render's private network |
| CI             | GitHub Actions: backend tests (Postgres service) + frontend typecheck/test/build | Catch breakage before deploy |

Versions (recorded in Phase 0):
- Backend: PHP 8.5 (locally 8.5.12; Docker image `dunglas/frankenphp:1-php8.5`), Laravel 13.42,
  Sanctum 4. PHP stays the same minor version locally and in Docker.
- Database: PostgreSQL 17 locally (`postgres:17`) and on Render (`postgresMajorVersion: "17"`).
- Frontend: Node 24, React 19.3, React Router 8.5, TanStack Query 5, Tailwind CSS 4.3, Vite 8.3,
  TypeScript 7.0, Vitest 5.1, oxlint (comes with the Vite template).
- Locally the backend runs with `php artisan serve` (FrankenPHP has no native Windows build); the
  Docker image runs FrankenPHP. Locally the homepage blocks are therefore served one at a time,
  which is fine for development.

Do not add libraries outside this list without asking first and explaining why.

## 3. Code standards

The codebase must be easy to read, review and explain. Code is read far more often than it is written,
and reviewers of this project will read it closely.

- **Simple over clever.** Prefer the straightforward solution. No premature abstractions,
  no design patterns that the current size of the project does not need.
- **Clear names.** `cheapestOfferPrice`, not `cop`. Functions are named after what they do.
- **Small units.** Functions do one thing. Controllers stay thin; logic lives in service classes.
- **Comments explain *why*, not *what*.** Add a short comment wherever a decision is not obvious
  (e.g. why keys are locked with `SKIP LOCKED`, why a similarity threshold is 0.3).
- **One pattern per problem.** Once a way of doing something is established (fetching data,
  handling errors, validating input), reuse it everywhere instead of inventing a second way.
- **Strict TypeScript.** No `any`. Shared API response types live in `frontend/src/api/types.ts`.
- **Validate at the edges.** Every API endpoint validates input with Laravel Form Requests.
- **Money is stored as integer cents (EUR).** Never floats.
- **Card numbers never reach the backend** and are never logged (Section 8).
- **No secrets in the repo or in chat.** The chat logs are published as a deliverable.

## 4. Working process (for the AI agent)

- The project lives at `C:\dev\enebaproject` (outside OneDrive: syncing `node_modules/` and
  `vendor/` causes file locks during installs).
- Work **one phase at a time** (Section 11). Do not start the next phase until asked.
- At the start of a phase, briefly state the plan for that phase and any open questions.
- At the end of each phase:
  1. Run the tests and the app, confirm the phase's "done when" checklist.
  2. Give a short walkthrough: which files were created/changed, how the pieces connect,
     and the key decisions made. Write it so it could be read aloud in a code review.
  3. Append any non-obvious decision to `docs/DECISIONS.md` (format: decision / alternatives / reason).
  4. Suggest a commit message.
- If something in this plan looks wrong or there is a clearly better option, say so and
  explain the trade-off before changing course.
- Never hardcode homepage content, product data or prices in the frontend.

## 5. Data model

```
users           id, name, email (unique), password, created_at
products        id, slug (unique), name, description, image_url, platform, region, genre,
                release_year, search_keywords (not null, default ''), search_text, created_at
sellers         id, name, rating (0–5), created_at
offers          id, product_id → products, seller_id → sellers,
                price_cents, original_price_cents (nullable, for deals), created_at
product_keys    id, offer_id → offers, code (encrypted), status (available | reserved | sold),
                order_item_id → order_items (nullable), created_at
orders          id, user_id → users, status (pending | paid | failed), total_cents,
                payment_reference (nullable), created_at
order_items     id, order_id → orders, offer_id → offers, quantity, unit_price_cents
banners         id, title, subtitle, image_url, link_url, position, active
homepage_blocks id, type, title, position, enabled, config (jsonb)
```

Notes:
- **An offer's stock = number of its `available` keys.** No separate stock column to get out of sync.
- A product's displayed price = its cheapest offer that has stock.
- **Keys point to order items, not the other way round** (`product_keys.order_item_id` only), so the
  link is stored once and cannot disagree with itself. An order item with quantity 3 has 3 keys.
- `search_text` = normalized `name + ' ' + search_keywords`, written by the model on save using the
  same normalizer as search queries (Section 6).
- Key codes use Laravel's `encrypted` cast, so they depend on `APP_KEY`. Production's `APP_KEY` is set
  once and never rotated, and production seeds itself (Section 10), so codes are always readable.
- **Cart lives in the browser (localStorage).** The server re-checks prices and stock at checkout,
  so the client is never trusted. Decision recorded in DECISIONS.md.
- `platform` values: Steam, PlayStation, Xbox, Nintendo, EA App, Ubisoft Connect.
- `region` values: Global, Europe, North America.

## 6. Deep feature #1 — Typo-tolerant search

**Requirements**
- Search bar in the header, available on every page.
- Live suggestions dropdown while typing (debounced ~250 ms, max 8 results, matched part highlighted,
  keyboard navigation with ↑ ↓ Enter Esc).
- Enter opens `/search?q=...` with the full results grid (reuses the Store grid and filters).
- Handles **typos** (`wticher` → The Witcher 3), **partial names** (`cyber` → Cyberpunk 2077),
  **word order / extra words**, **case and accents**, and **common abbreviations** via
  `search_keywords` (`gta` → Grand Theft Auto V, `rdr2` → Red Dead Redemption 2).
- Empty-results state with a "Did you mean …?" suggestion when a close match exists.

**Implementation**
- Enable the Postgres extension `pg_trgm` in a migration.
- **Normalization happens in PHP, in one place:** `SearchNormalizer::normalize(string): string`
  (transliterate accents with `Str::ascii`, lowercase, turn punctuation into spaces, collapse spaces).
  It is applied both when writing `products.search_text` and to every query, so the two sides always
  match. This replaces an index on `lower(unaccent(name || ' ' || search_keywords))`, which Postgres
  rejects (`unaccent()` is not IMMUTABLE) and which is NULL whenever keywords are NULL.
- GIN trigram index on `search_text` (`gin_trgm_ops`).
- **Search is a query scope, not a finished list:** `SearchService::apply(Builder $query, string $q)`
  adds the match condition and relevance ordering to any product query. The suggest endpoint and the
  Store endpoint both use it, so search + filters + sort + pagination run as one SQL query.
  1. Normalize the query. Under 2 chars → no results.
  2. Match rows where `search_text ILIKE '%q%'` (with `%` and `_` in the query escaped) **or**
     `q <% search_text` (the word-similarity operator: unlike calling `word_similarity()` in a
     `WHERE`, it can use the index). Threshold set via `pg_trgm.word_similarity_threshold`;
     start at 0.3, tune with tests.
  3. Rank: exact name match → name starts with query → contains query → by `word_similarity` score.
- "Did you mean …?": when there are no results, find the closest product name by `similarity()` with a
  lower threshold; the frontend shows it as a link to that search.
- Endpoints:
  - `GET /api/search/suggest?q=` → top 8, lightweight (id, slug, name, image, from-price)
  - Store endpoint accepts `q` too, so search results get filters/sorting for free.
- **Tests (PHPUnit, against real Postgres):** a table of query → expected first result,
  including typos, partials, abbreviations, accents and a no-match case. These tests are what the
  threshold is tuned against.
- Document in DECISIONS.md why `pg_trgm` was chosen over Meilisearch/Elasticsearch
  (no extra service, free hosting, sufficient for catalog size) and what would change at scale.
  Be honest that at ~80 products Postgres scans the table anyway; the index is there so the same
  query keeps working as the catalog grows.

## 7. Deep feature #2 — Homepage from independent blocks

**Requirements**
- The homepage contains no hardcoded content. It asks the backend which blocks to show and in
  what order, then renders each block.
- Each block gets its data from its **own provider and its own data source**.
- Blocks load **independently**: each shows its own skeleton while loading, and if one block
  fails, the others still render (the failed one shows a small fallback or hides).
- Adding a new block of an existing type requires only a database row — no frontend change.

**Blocks**

| Type           | Data source                                                         |
|----------------|---------------------------------------------------------------------|
| `banner`       | `banners` table (hero carousel)                                     |
| `bestsellers`  | computed from `order_items` of paid orders (most sold in last 30 days, falls back to newest) |
| `deals`        | offers where `original_price_cents > price_cents`, sorted by discount % |
| `new_releases` | products ordered by `release_year` / `created_at`                   |
| `platform_row` | products for a platform given in block `config` (e.g. `{"platform": "Steam"}`) |

**Backend implementation**
- `GET /api/homepage` → ordered list of enabled blocks: `[{ id, type, title }]`.
- `GET /api/homepage/blocks/{id}` → data for one block. If that block's provider throws, only this
  request fails (and the error is logged); the layout and the other blocks are unaffected.
- Interface `BlockProvider { public function getData(array $config): array; }`
  with one class per block type in `app/Homepage/Blocks/`. A `BlockRegistry` maps
  `type → provider class`. Each provider queries only its own source.
- The brief says each block gets content "from its own backend service or database". This is
  implemented as one provider per block, each reading only its own source, inside one Laravel app;
  splitting providers into separately deployed services is the next step at scale (DECISIONS.md).
- Each block response is cached separately (Laravel `file` cache store, short TTL) so repeat visits
  are fast. What keeps one slow block from delaying the others is one HTTP request per block plus
  several FrankenPHP workers serving them in parallel, not the cache.

**Frontend implementation**
- `HomePage` fetches the layout, then renders `<BlockRenderer>` for each block.
- `BlockRenderer` looks the type up in a `blockComponents` map and fetches its own data
  with its own `useQuery`. A failed request (`isError`) shows that block's small fallback; a crash
  while rendering is caught by a small error boundary (own class component, no library). Both are
  needed: error boundaries do not catch failed fetches.
- Unknown block type → render nothing and log a warning (forward compatible).
- Tests:
  - Vitest + React Testing Library: one block's request fails → the other blocks still render.
  - PHPUnit: a provider that throws → that block's endpoint errors; the layout and other blocks respond normally.

## 8. Baseline features

**Store (`/store`)**
- `GET /api/products?q=&platform[]=&region[]=&genre[]=&min_price=&max_price=&sort=&page=`
- Sorts: relevance (when `q`), price low→high, price high→low, newest, name.
- Filters in a sidebar, all state kept in the URL so links are shareable and Back works.
- `GET /api/filters?q=` returns available filter options with counts. **Counts are based on the
  current search only** (they ignore the other selected filters). Recorded in DECISIONS.md.
- Paginated (24 per page). Eager-load relations to avoid N+1 queries.

**Product page (`/product/:slug`)**
- Details, image, platform/region badges.
- Offers table: seller, seller rating, price, stock; cheapest highlighted; "Add to cart" per offer.

**Auth**
- Register, login, logout with Sanctum tokens. Passwords hashed (Laravel default).
- The token is kept in localStorage: frontend and backend are on different sites (Section 10), so
  cookie-based auth would depend on third-party cookies. The trade-off (XSS exposure) goes in DECISIONS.md.
- Protected frontend routes: `/checkout`, `/orders`. Unauthenticated users are redirected to
  login and sent back afterwards.

**Cart & checkout**
- Cart drawer/page: change quantity, remove, total.
- `POST /api/checkout` (auth required), body `{ items: [{ offer_id, quantity }], payment_token }`.
  It runs in three steps, so no row lock is held while payment is being processed:
  1. **Reserve (transaction 1):** re-read offers and current prices from the DB (ignore client prices).
     For each item select `quantity` available keys with `FOR UPDATE SKIP LOCKED`. If there are not
     enough → roll back, return a clear error naming the item. Otherwise create a `pending` order and
     its items, and mark the keys `reserved` and linked to their order item.
  2. **Pay (no transaction):** `PaymentProvider::charge(int $amountCents, string $paymentToken)`.
  3. **Confirm (transaction 2):** success → keys `sold`, order `paid`, `payment_reference` stored.
     Declined → keys back to `available` and unlinked, order `failed`.
- Why `SKIP LOCKED`: with a plain `FOR UPDATE ... LIMIT n`, a buyer who waited on a key that another
  buyer just took can get fewer rows than are actually available, and see a false "out of stock".
  `SKIP LOCKED` hands each buyer different free keys without waiting.
- Known limitation: if the process dies between steps 1 and 3, keys stay `reserved`. A scheduled job
  releasing old `pending` orders is the fix (documented, not built).
- **Payment:** `PaymentProvider` interface, implementation `FakePaymentProvider`. The frontend's fake
  card form turns test cards into tokens, like Stripe Elements does: `4242 4242 4242 4242` →
  `tok_success`, `4000 0000 0000 0002` → `tok_declined`; any other number is rejected in the form.
  The backend only ever sees the token. A real Stripe integration would use PaymentIntents and
  webhooks, so the interface would change; the fake keeps the same boundary (server sees a token only).
- Order confirmation page reveals the key(s). `/orders` lists order history with keys.
- **Concurrency test:** uses `DatabaseTruncation`, not `RefreshDatabase` (its wrapping transaction
  hides the test data from a second connection). A second DB connection locks the only key while the
  checkout service runs → checkout must report out of stock; with two keys, two buyers get different
  keys. No process forking (not available on Windows).

## 9. Eneba styling

- The design reference is eneba.com. Screenshots are in `design-reference/` (gitignored: they contain
  Eneba's and publishers' artwork): `homepage-desktop.png`, `homepage-mobile.png`, `store-desktop.png`.
- All design values live in one place: the Tailwind v4 `@theme` block in `frontend/src/index.css`.
  Components use tokens, never raw hex values.
- Colors given by the user (roles as read from the screenshots):
  - `#4618ac`: main purple (page and header background)
  - `#341281`: dark purple (secondary nav bar)
  - `#5825cc`: light purple (filter panels, inputs)
  - Remaining tokens are sampled from the screenshots in Phase 0: card body, yellow accent (checkboxes,
    "Clear all", CTA button, count badges), discount green, region teal, cashback mint.
- Sampled in Phase 0: card `#1f0a4d`, chip `#2c0e6c`, field `#461ea3`, accent yellow `#fad318`,
  mint `#63e3c2`, region teal `#22c299`, discount lime `#84e923`.
- Font: Montserrat (Google Fonts), the free geometric sans closest to the reference.
- Components seen in the reference:
  - **Header:** logo, wide outlined search input, cart, account ("Log in | Register"); dark
    secondary nav bar below it. Wishlist and language/currency are out of scope.
  - **Homepage:** hero banner area, then rows of product cards with a section title.
  - **Product card:** cover image with a platform label strip at its bottom edge; dark body with a
    bold white title, region in teal caps, "From €19.99 -95%" (old price struck through, discount in
    green), large price.
  - **Store:** breadcrumb, H1, left sidebar of collapsible filter panels (price range inputs,
    checkbox lists with counts), active-filter chips with "Clear all", "Results found: N",
    sort dropdown, 4-column grid on desktop.
  - **Mobile:** hamburger + logo + icons header; cards become horizontal list items
    (image left, details right).
- Offers table, buttons and price tags with discount badge follow the same tokens.
- Responsive: works on mobile and desktop.
- Product images and banners: generated placeholder covers (no copyrighted artwork).
- Tokens, header and product card are styled from the start (Phases 0 and 3); Phase 9 is a polish
  pass, not a restyle.

## 10. Hosting on Render

Everything is described in one Blueprint, `render.yaml`, region `frankfurt`, deployed into its own
Render Hobby workspace `anabe` (the default workspace's one free database belongs to another project):

| Resource | Render type                 | Name        | Notes |
|----------|-----------------------------|-------------|-------|
| Frontend | Static Site (free)          | `anabe-web` | builds `frontend/`; rewrite `/*` → `/index.html` so deep links survive a reload |
| Backend  | Web Service, Docker (free)  | `anabe-api` | `backend/Dockerfile`, FrankenPHP classic mode, health check `/api/health` |
| Database | Render Postgres (free)      | `anabe-db`  | same major version as local Docker Compose |

Environment variables:
- **Every secret** (`APP_KEY`, …) is declared with `sync: false`. The user enters the values in the
  Render dashboard; no secret values in the repo or in chat.
- Database credentials come from `fromDatabase` (Render generates and injects them; nobody copies them).
- Non-secret values (`VITE_API_URL` for the frontend build, `FRONTEND_URL` for CORS) are plain values
  in the Blueprint, confirmed after the first deploy (Render can add a suffix to a hostname).

Free-tier constraints and how they are handled:
- **The web service sleeps after 15 minutes without traffic**; the first request then takes 30–60 s+.
  → The frontend shows "Waking up the server…" when a request takes longer than ~3 s.
- **No pre-deploy command and no shell on free web services.**
  → The container start script runs `php artisan migrate --force`, then seeds **only if the catalog
  is empty**, then starts FrankenPHP. Seeding inside the app means it uses production's own `APP_KEY`.
- **Free Postgres expires 30 days after creation** (then 14 days' grace before deletion); one free
  database per workspace; no backups; 1 GB.
  → Recreating is cheap: a new database from the Blueprint plus a redeploy reseeds it. Before
  submitting, check that the expiry date covers the review period (recreate close to submission if
  needed) and note the date in the README.
- **The disk is ephemeral** → fine for the `file` cache store, since the cache is disposable.
- **`onrender.com` is a public suffix**, so `anabe-web.onrender.com` and `anabe-api.onrender.com` are
  different sites → token auth (Section 8), CORS allows only the frontend origin.

## 11. Phases

Each phase ends with a working, committed state.

**Phase 0 — Setup**
Monorepo at `C:\dev\enebaproject`: `backend/`, `frontend/`, `docs/`, `ai-logs/`, `design-reference/`
(gitignored). `git init`, public GitHub repo `enebaproject` (via `gh`). Enable `pdo_pgsql`/`pgsql` in
the scoop PHP config. Laravel + FrankenPHP-compatible setup, Vite + React 19 + TypeScript + Tailwind v4
with the design tokens, Vitest. Docker Compose Postgres, `.env.example` files, root README skeleton,
`.gitignore`.
*Done when:* both apps run locally and the frontend shows data from `GET /api/health`.

**Phase 1 — Deploy skeleton**
`backend/Dockerfile` (FrankenPHP classic mode), start script (migrate, then serve), `render.yaml` per
Section 10, CORS, `.env.example` updated. The user enters secret values in the dashboard.
*Done when:* the public static-site URL shows data from the public backend, which reads Render Postgres.

**Phase 2 — Data model & seed**
Migrations and models per Section 5. Seeder: ~80 real game titles (good for testing search),
6–10 sellers, 2–5 offers per product with different prices, keys per offer, some deals, banners,
homepage blocks, and some past paid orders so the bestsellers block has data.
The start script seeds production when the catalog is empty.
*Done when:* `php artisan migrate:fresh --seed` produces a realistic catalog locally, and production
seeds itself on first start.

**Phase 3 — Store**
Products API with filters/sort/pagination, filters API, Store page with sidebar, grid, URL state.
Product card and filter sidebar styled with tokens from the start.
*Done when:* filters, sort and paging all work and survive a page reload.

**Phase 4 — Search (deep)** — Section 6, including the test table.

**Phase 5 — Homepage (deep)** — Section 7, including both failing-block tests.

**Phase 6 — Product page** — Section 8.

**Phase 7 — Auth** — Section 8.

**Phase 8 — Cart, checkout, payment, orders** — Section 8, including the concurrency test.

**Phase 9 — Eneba styling polish** — Section 9, against the screenshots, mobile and desktop.

**Phase 10 — Quality & docs**
GitHub Actions CI, review for N+1 queries and missing validation, final README
(live URL, demo account, test cards, architecture diagram, data model, scope decisions,
"what I would do next", Render Postgres expiry date).
Export AI chat logs to `ai-logs/` as Markdown: a small script converts the Claude Code transcripts
(JSONL in `C:\Users\grubl\.claude\projects\`, both the old OneDrive-path folder and the
`C--dev-enebaproject` folder) to Markdown and scrubs secrets and personal data; the user reviews the
result before it is committed.
*Done when:* the full purchase flow works on the public URL from a fresh browser.

## 12. Definition of done (whole project)

- [ ] Public URL works; full purchase flow works end-to-end in production
- [ ] Search handles typos, partials and abbreviations; covered by tests
- [ ] Homepage blocks load independently; one failing block does not break the page
- [ ] No hardcoded content on the frontend
- [ ] Checkout is transactional; a key can never be sold twice
- [ ] Own branding ("anabe") with the demo disclaimer; no Eneba logo
- [ ] Render Postgres expiry date covers the review period
- [ ] README explains scope, decisions and how to run locally
- [ ] Full AI chat logs exported to `ai-logs/`
