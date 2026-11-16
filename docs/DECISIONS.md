# Decisions

Non-obvious decisions, each as **decision / alternatives / reason**. Entries made during planning
describe the intended design; they are updated if the implementation ends up different.

---

### 1. Two features in depth, the rest at a solid baseline

- **Decision:** Search (typo-tolerant) and the block-based homepage are built in depth. Store, product
  page, auth, cart, checkout and payment work end-to-end but are kept simple.
- **Alternatives:** every part at the same medium depth.
- **Reason:** the brief says "a few features done extensively beat many fitting minimal requirements".
  Search and the homepage are the two entry points of the purchase flow, and both have real design
  problems to solve (fuzzy matching and ranking; independent loading and failure isolation).

### 2. Everything hosted on Render, described in one Blueprint

- **Decision:** `render.yaml` defines a Static Site (frontend), a Docker Web Service (backend) and
  Render Postgres, all on the free plan in Frankfurt. Secrets are declared with `sync: false` and
  entered in the dashboard; database credentials are injected with `fromDatabase`.
- **Alternatives:** Vercel (frontend) + Render (backend) + Neon (database).
- **Reason:** one platform and one file make the whole infrastructure reproducible and reviewable. The
  backend reaches the database over Render's private network, and no secret values are in the repo.
- **Cost:** the free web service sleeps after 15 minutes (slow first request), and free Postgres
  expires 30 days after creation. Both are handled as described in PLAN.md, Section 10.

### 3. FrankenPHP in classic mode as the app server

- **Decision:** the backend container runs FrankenPHP in classic mode (a fresh PHP request each time).
- **Alternatives:**
  - `php artisan serve`: one worker by default, so the homepage's parallel block requests would be
    served one after another, which defeats the point of independent blocks.
  - nginx + php-fpm: works, but means two processes and two config files in one container.
  - FrankenPHP worker mode / Laravel Octane: faster, but the app stays in memory between requests, so
    state can leak from one request to the next. That is more to reason about than this project needs.
- **Reason:** several workers in one process with almost no configuration, and the same per-request
  model as classic PHP.
- **Local development** uses `php artisan serve`, because FrankenPHP has no native Windows build.
  Locally the homepage blocks are therefore served one at a time; production behaviour is what counts.

### 4. Search text is normalized in PHP, not with Postgres `unaccent()`

- **Decision:** a `search_text` column holds `name + keywords` passed through one PHP normalizer
  (accents → ASCII, lowercase, punctuation → spaces, collapsed spaces). Every search query goes through
  the same normalizer. A GIN trigram index (`pg_trgm`) sits on `search_text`.
- **Alternatives:** an index on `lower(unaccent(name || ' ' || search_keywords))`.
- **Reason:** Postgres rejects that index because `unaccent()` is not IMMUTABLE (it would need a wrapper
  function), and the concatenation is NULL whenever keywords are NULL. One normalizer for both stored
  text and queries guarantees both sides match, and only one extension is needed.

### 5. Homepage blocks: one provider per block inside one backend

- **Decision:** each block type has its own `BlockProvider` class that reads only its own data source
  (banners table, order history, offers, products), served through its own endpoint.
- **Alternatives:** separately deployed services per block.
- **Reason:** the brief asks for each block to get content "from its own backend service or database".
  Separate providers with separate sources and separate endpoints give the same isolation (one failing
  block does not affect the others) without running several services on a free tier. Moving a
  provider into its own service later only changes where its endpoint lives.

### 6. Filter counts are based on the current search only

- **Decision:** `GET /api/filters?q=` returns option counts (e.g. "Steam 12") for products matching the
  current search text. The other selected filters do not change the counts.
- **Alternatives:**
  - Counts over the whole catalog: simple, but they would not match what the user sees after searching.
  - Fully faceted counts (each facet counted with all *other* selected filters applied): most precise,
    but needs one count query per facet and more code to explain.
- **Reason:** counts always match the search the user typed, with one extra query. Known simplification:
  after selecting e.g. "Steam", the region counts still include other platforms, so a count can be
  higher than the results shown after combining filters.

### 7. Keys are locked with `FOR UPDATE SKIP LOCKED`

- **Decision:** checkout selects the keys it needs with `FOR UPDATE SKIP LOCKED`.
- **Alternatives:** plain `lockForUpdate()` (`FOR UPDATE`).
- **Reason:** with plain `FOR UPDATE ... LIMIT n`, a buyer who waits on a key that another buyer then
  takes can get fewer rows back than are actually available, and see a false "out of stock".
  `SKIP LOCKED` gives each concurrent buyer different free keys without waiting, and a key still
  cannot be taken twice.

### 8. Checkout runs as reserve → pay → confirm

- **Decision:** transaction 1 reserves keys and creates a pending order; payment runs outside any
  transaction; transaction 2 marks keys sold (paid) or releases them (failed).
- **Alternatives:**
  - Mark keys sold, then pay: a declined card leaves keys marked sold.
  - Everything in one transaction, including the payment call: row locks are held while waiting on
    the payment provider.
  - Pay first, then take keys: the buyer can be charged for something that is out of stock.
- **Reason:** no locks are held during payment, and a decline returns the keys.
- **Known limitation:** if the process dies between the two transactions, keys stay `reserved`. A
  scheduled job releasing old pending orders is the fix (not built).

### 9. The backend receives a payment token, never a card number

- **Decision:** the fake card form turns the documented test cards into tokens (`tok_success`,
  `tok_declined`) in the browser, as Stripe Elements does. `PaymentProvider::charge()` receives only
  the amount and the token. Other card numbers are rejected by the form.
- **Alternatives:** post the card number to the backend and decide there.
- **Reason:** card numbers never touch the server or its logs, which is how real providers work. Being
  honest: a real Stripe integration would use PaymentIntents and webhooks, so the interface would
  change; what carries over is the boundary (the server only ever sees a token).

### 10. Keys link to order items in one direction only

- **Decision:** `product_keys.order_item_id` links a key to its order item; `order_items` has
  `quantity` and no key column.
- **Alternatives:** foreign keys in both tables (`order_items.product_key_id` and
  `product_keys.order_item_id`).
- **Reason:** storing the link once means it cannot disagree with itself, and an order item can hold
  several keys, which the cart's quantity field needs.

### 11. Production seeds itself on startup when the catalog is empty

- **Decision:** the container start script runs migrations, then the seeder only if there are no
  products, then starts the server.
- **Alternatives:** seed from a developer machine against the external database URL; use Render's
  pre-deploy command or shell.
- **Reason:** Render's free tier has no pre-deploy command or shell. Key codes are encrypted with
  `APP_KEY`; seeding from a laptop would need production's `APP_KEY` there, or the codes would be
  unreadable in production. Seeding inside the running app always uses the right key, and an expired
  free database is replaced by recreating it and redeploying.

### 12. Own name ("anabe") with Eneba's look

- **Decision:** the site copies Eneba's colors, layout and components but uses its own name and
  wordmark, and shows "Demo project — not affiliated with Eneba. No real payments." on every page.
- **Alternatives:** use Eneba's name and logo.
- **Reason:** a public site that looks exactly like a real store and has a card form is what phishing
  sites look like, so the URL could be flagged by browser safe-browsing checks. It also avoids using
  Eneba's trademark on a public site that isn't theirs. The styling requirement is about look and
  feel, which is kept.

### 13. Backend tests run against a real Postgres database

- **Decision:** PHPUnit uses a separate `anabe_test` Postgres database (created by Docker Compose
  locally and by a Postgres service container in CI), not Laravel's default in-memory SQLite.
- **Alternatives:** SQLite in memory: faster and needs no setup.
- **Reason:** the parts most worth testing are Postgres features: trigram search (`pg_trgm`) and
  row locking (`FOR UPDATE SKIP LOCKED`). On SQLite those tests would either not run or pass while
  production fails.

### 14. Config and route caches are built when the container starts, not in the image

- **Decision:** `backend/docker/start.sh` runs `config:cache` and `route:cache` on every container start,
  before migrations and before FrankenPHP starts.
- **Alternatives:** cache them in the Dockerfile; do not cache at all.
- **Reason:** in classic mode Laravel boots on every request, and a cached config is one file instead of
  every file in `config/`. The cached config captures environment variables (`APP_KEY`, `DB_URL`, …),
  which Render only provides when the container runs, so caching during the image build would bake
  in empty values.

### 15. Laravel trusts `X-Forwarded-*` headers from any address

- **Decision:** `bootstrap/app.php` calls `trustProxies(at: '*')`.
- **Alternatives:** list Render's proxy IP ranges; trust no proxy.
- **Reason:** Render ends HTTPS at its own proxy and forwards plain HTTP to the container, so without
  this Laravel thinks every request is `http` and comes from the proxy's IP. That breaks generated
  `https` URLs and any limit per client IP. Trusting every address is safe here because the container
  cannot be reached except through Render's proxy, so every request that arrives has passed through it.

### 16. FrankenPHP runs a fixed 4 PHP threads

- **Decision:** `num_threads 4` in `backend/docker/Caddyfile`.
- **Alternatives:** FrankenPHP's default of 2 threads per CPU.
- **Reason:** the default depends on the CPUs the container sees, so it changes with the machine. On
  Render's free plan (0.1 CPU) it is 2 threads, which would serve only two of the homepage's block
  requests at a time; in a local container with no CPU limit it was 64 (32 CPUs). Waiting on the
  database barely uses the CPU, so 4 threads overlap that waiting even on 0.1 CPU, and four requests
  at PHP's 128 MB memory limit stay within the free instance's 512 MB.

### 17. The production database accepts no connections from the internet

- **Decision:** `ipAllowList: []` on `anabe-db` in `render.yaml`.
- **Alternatives:** Render's default for new databases, which allows every IP (still password-protected).
- **Reason:** only the API needs the database, and it connects over Render's private network.
  Production seeds itself from inside the container (#11), so no developer machine needs access
  either. Closing the port removes the database from the internet entirely.

### 18. The seeders use a fixed-seed random generator, not Faker

- **Decision:** prices, stock and order history come from PHP's `Random\Randomizer` with a fixed seed;
  names, descriptions and sellers are written out in `database/seeders/`.
- **Alternatives:** Faker and model factories, as Laravel projects usually seed.
- **Reason:** production seeds itself (#11), and the production image installs no dev dependencies, so
  Faker is not there. A fixed seed also makes every run produce the same catalog, locally and in
  production, so a problem seen with one product's price or stock can be reproduced. Factories (with Faker)
  remain for tests.

### 19. Placeholder covers are generated SVG files, committed and served as static files

- **Decision:** `php artisan catalog:generate-art` draws a cover per game and an image per banner
  (`App\Catalog\PlaceholderArt`), writes them to `backend/public/`, and they are committed. FrankenPHP
  serves them directly, with a one-day cache header. The database stores the path; the API returns the URL.
- **Alternatives:**
  - Real cover art: copyrighted.
  - Drawing each image in a PHP request: on a tenth of a CPU, ~50 covers on the homepage would compete
    with the block data requests for the same four PHP threads.
  - Generating images during seeding: the container disk is temporary, and production seeds only once,
    so the images would vanish on the next restart.
  - An external placeholder service: another dependency that can be down or slow.
- **Reason:** static files cost the server almost nothing, the art is reviewable in the repository, and
  the same name always draws the same picture. Colours avoid the site's purple so covers stand out.

### 20. Status and catalog values are enforced by the database too

- **Decision:** `status`, `platform` and `region` columns have check constraints matching their PHP enums,
  and `product_keys` has a check constraint: a key is `available` exactly when it has no `order_item_id`.
- **Alternatives:** validate only in PHP.
- **Reason:** checkout (Phase 8) moves keys between available, reserved and sold under concurrency. If a
  code path ever forgets to link or unlink an order item, the database refuses the write instead of
  silently storing a key that is "sold" to nobody. The constraint costs one line of SQL.

### 21. Search runs on Postgres `pg_trgm`, not a search engine

- **Decision:** typo-tolerant search uses trigram matching inside Postgres: `LIKE '%query%'` for partial
  names, plus the word-similarity operator `<%` for typos, word order and extra words, with a GIN
  trigram index on `products.search_text`. Abbreviations come from each product's `search_keywords`.
- **Alternatives:** Meilisearch, Typesense or Elasticsearch.
- **Reason:** no extra service to host, keep in sync or pay for on a free tier, and search stays one SQL
  query together with the store's filters, sort and pagination. For a catalog of ~90 products it is
  more than enough. Honestly, at this size Postgres scans the table anyway; the index is there so the
  same query keeps working as the catalog grows.
- **Tuning:** the match threshold is 0.35, not the plan's starting 0.3. On this catalog every real typo
  tried scored 0.38 or more, while games we do not sell scored up to 0.33 against some title
  ("fortnite" vs Forza Horizon 5) and would have shown unrelated results. `tests/Feature/SearchTest.php`
  holds the table of queries the value is tuned against.
- **Limits, and what changes at scale:** trigrams count shared three-letter pieces, so they cannot tell
  a swapped letter in a short word from an unrelated word with similar letters: "wichter hnut" finds
  nothing, and "Did you mean" can suggest a look-alike title. A search engine scores each word by
  edit distance, adds synonyms, popularity ranking and facets, and runs outside the main database. With
  thousands of products, or search traffic that competes with checkout, that is the point to move.

### 22. A product's price comes from a lateral join, computed per query

- **Decision:** `Product::withCheapestOffer()` adds the cheapest in-stock offer with
  `LEFT JOIN LATERAL (SELECT … ORDER BY price_cents LIMIT 1)`, and the store filters and sorts on it.
- **Alternatives:** store a `min_price_cents` column on products and keep it updated; load all offers and
  compute the price in PHP.
- **Reason:** a stored price goes stale the moment a key sells or an offer changes, which is exactly the
  stock-drift problem the data model avoids (stock = available keys). Computing it in PHP cannot filter
  or sort in SQL and would need every offer of every product. The lateral join keeps one query per page,
  and the existing `(offer_id, status)` index serves its stock check.

### 23. How a homepage block fails, and how long its data is cached

- **Decision:** a block whose request fails shows a small "could not be loaded" note with a retry
  button, except the hero banner, which hides (a broken hero looks worse than none). A block that crashes
  while rendering is caught by its own error boundary. Each block's data is cached for 60 seconds; saving
  or deleting the block (or changing a banner) clears its entry.
- **Alternatives:** hide every failed block; cache for minutes or hours; clear the cache on every sale.
- **Reason:** a visible note tells the visitor something is missing and offers a retry without
  reloading the page. Sixty seconds absorbs repeated visits while keeping prices and stock close to
  live; checkout re-reads both from the database anyway, so a slightly stale card can never sell at a
  wrong price. Clearing the entry on change makes an admin edit show up immediately. (A first version
  put `updated_at` in the cache key instead; timestamps have whole-second precision, so two edits within
  a second reused the stale entry. The admin tests caught it.)

### 24. Auth uses Sanctum bearer tokens kept in localStorage

- **Decision:** login and registration return a Sanctum API token; the frontend keeps it in
  `localStorage` and sends it as `Authorization: Bearer …`. Tokens expire after 7 days, logout revokes
  the token of that browser, and login/registration are rate-limited to 10 attempts a minute per IP.
- **Alternatives:** Sanctum's cookie-based SPA sessions (an HttpOnly cookie that scripts cannot read).
- **Reason:** the frontend (`anabe-web.onrender.com`) and the API (`anabe-api.onrender.com`) are
  different sites, because `onrender.com` is a public suffix. A session cookie sent from one to the
  other is a third-party cookie, which browsers increasingly block, so login would break for some
  visitors. A bearer token works everywhere.
- **Cost:** any script running on the page can read `localStorage`, so an XSS bug would expose the
  token. The mitigations: React escapes all rendered text, the app renders no user-provided HTML, the
  token expires within a week, and login rejects unknown emails and wrong passwords with the same
  message. With both apps under one domain (e.g. `shop.example` and `api.shop.example`), cookie sessions
  would become the better choice.

### 25. The cart lives in the browser and holds only offer ids and quantities

- **Decision:** the cart is kept in `localStorage` as `[{offerId, quantity}]`. The cart and checkout
  pages fetch current prices and stock from `GET /api/offers?ids[]=`, and `POST /api/checkout` reads
  prices from the database again. It answers 409 naming the item when stock ran out (nothing reserved or
  charged) and 402 when the card is declined (the order is kept as failed, its keys go back on sale).
- **Alternatives:** a server-side cart table; storing prices and names in the browser's cart.
- **Reason:** a server cart needs a login (or anonymous cart sessions) before the first "Add to cart",
  and merging carts at login. A browser cart works for visitors right away and survives reloads. Saving
  prices in the browser would show stale or tampered prices; storing only ids makes the server the
  single source of truth, and the client is never trusted with an amount.

### 26. Abandoned checkouts are released by the scheduler, in the same container

- **Decision:** `checkout:release-expired` runs every minute (`php artisan schedule:work`, started in the
  background by `docker/start.sh`). Pending orders older than 15 minutes become `failed` and their
  reserved keys go back on sale; the orders are locked with `SKIP LOCKED`, so a checkout that is
  confirming at that moment is left alone. Confirm re-reads its order under a lock and does nothing if
  the order already expired.
- **Alternatives:** a Render cron job (not on the free plan); releasing at the start of every checkout
  (stock shown on pages would stay wrong until someone checks out).
- **Reason:** works on the free plan with no extra service. While the free instance sleeps the
  scheduler sleeps too, but then nobody can check out either, and it catches up on the next start.

### 27. A public demo admin, plus private admins by email

- **Decision:** `admin@example.com` / `anabe-admin` is always an admin (`config('anabe.demo_admin_email')`,
  created on every start by `DemoAccountSeeder`), so reviewers can try `/admin` on the live site. Further
  admins are listed in `ADMIN_EMAILS`, set in the Render dashboard and never committed.
- **Alternatives:** an `is_admin` column or a roles table; no admin login on the live site at all (the
  first version did this, to stop visitors from switching off the homepage).
- **Reason:** showing the admin pages to reviewers was judged worth more than protecting a demo
  homepage. The cost: any visitor can hide or delete homepage blocks. Recovery is easy (re-add the blocks
  in `/admin`, or recreate the database, which reseeds it). With real staff, a roles table would replace
  this and the public login would go.

### 28. CI gates deploys

- **Decision:** GitHub Actions runs Pint and PHPUnit against a Postgres 17 service, and the frontend's
  typecheck, lint, tests and build. Both Render services use `autoDeployTrigger: checksPass`.
- **Alternatives:** deploy on every commit and rely on running tests locally.
- **Reason:** a red commit can no longer reach the public URL.
