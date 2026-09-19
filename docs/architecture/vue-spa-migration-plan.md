# Vue 3 + TypeScript SPA Rewrite of E-Lib Frontend

## Context

E-Lib's frontend today is server-rendered PHP (`App/Views`, page-per-file, no layout inheritance, duplicated header/footer boilerplate across ~15 files, ad-hoc per-page CSS, some accessibility/XSS-escaping gaps). For the thesis, the plan is to replace it entirely with a Vue 3 + Vite + TypeScript SPA — both to demonstrate a modern decoupled stack (API + component framework) as a thesis deliverable, and to fix the underlying engineering issues along the way (duplication, unescaped output, inconsistent auth model).

Key discovery that de-risks this: page controllers already pass **no data** to views — every current page is a shell that loads its real content client-side via axios/fetch against `/api/v1/*`. The backend API surface already exists; this is a frontend replacement plus a small, targeted backend auth cleanup, not a full-stack rewrite.

Decisions locked in:
- **Framework**: Vue 3 + Vite, **TypeScript** (matches the existing Playwright e2e suite, which is already TS).
- **Scope**: full SPA rewrite (not incremental/hybrid) — all pages migrate, old PHP views are deleted after cutover.
- **Auth model**: consolidate fully on **JWT bearer tokens** for all protected API routes (today it's an inconsistent split — some routes require the JWT, some require the PHP session cookie).
- **Deployment**: same-origin — PHP continues to serve the built SPA as static files from `public/`, so **no CORS headers are needed in production**; local dev uses a Vite dev-server proxy instead.
- **Route naming**: rename `/view-books`→`/browse`, `/search_results`→`/search`, `/dashboard`→`/admin` for a cleaner SPA scheme.
- **CAS-to-local-user mapping**: still open (unsure whether HMU's CAS response maps cleanly to email) — implement the CAS bridge's plumbing (ticket validation → JWT issuance → SPA callback) but leave the exact netid→user lookup policy as a clearly marked stub/config point to revisit before milestone 5 ships. Do not block earlier milestones on this.

Verified against source (not just inferred): `AuthenticatedUser::isAdmin()` (`App/Includes/AuthenticatedUser.php:38-44`) checks `$_SESSION` only; the JWT payload minted in `UserController::handleLogin()` (`App/Controllers/UserController.php:67-71`) contains only `user_id`/`email`, **no** `isAdmin` claim; `CasService::authenticate()` (`App/Services/CasService.php:23-56`) returns a bare `bool` and never reads the CAS username from the validation response body — so today there is no linkage at all between a validated CAS ticket and a local user.

## 1. Repo layout

New top-level `frontend/` directory (sibling to `App/`, `public/`, `qa/`):

```
frontend/
  package.json  vite.config.ts  tsconfig.json  tsconfig.node.json  index.html
  .env.development                # VITE_API_BASE_URL=/api
  src/
    main.ts  App.vue
    router/index.ts  router/guards.ts        # requireAuth / requireAdmin
    stores/auth.ts  stores/toast.ts           # Pinia
    api/client.ts  api/books.ts  api/users.ts  api/reviews.ts  api/admin.ts  api/support.ts
    views/
      Home.vue  Browse.vue  SearchResults.vue  BookDetail.vue  Reader.vue
      Login.vue  Signup.vue  AuthCallback.vue  Profile.vue
      admin/Dashboard.vue  admin/MassUpload.vue  admin/Logs.vue
      Docs.vue  NotFound.vue
    components/
      NavBar.vue  Footer.vue  BookCard.vue  StarRating.vue
      ReviewList.vue  ReviewForm.vue  ToastContainer.vue
      LoginForm.vue  SignupForm.vue           # reused by Login.vue/Signup.vue and Home.vue's popup
    composables/
      reader/usePdfDocument.ts  reader/useZoomRotate.ts  reader/useReaderSearch.ts
      reader/useReaderNotes.ts  reader/useReaderPersistence.ts  reader/useFullscreen.ts
      useToast.ts
    types/book.ts  types/user.ts  types/review.ts  types/api.ts
    assets/main.css
```

`vite.config.ts`: `build.outDir: '../public/dist'`, `build.emptyOutDir: true`, `base: '/dist/'`. Production artifacts (`public/dist/index.html`, `public/dist/assets/*`) are real static files under `public/`, so both Apache's rewrite rule and PHP's built-in server serve them directly without touching `public/index.php` — no new routing work needed for static assets. `public/index.php` stays the sole PHP entry point.

## 2. Backend changes (JWT consolidation)

**`public/index.php`** — move book-mutation routes from `AuthMiddleware` to `JwtAuthMiddleware`:
- Add to `JwtAuthMiddleware`'s allowlist: `/api/v1/books` POST/PUT/DELETE, `/api/v1/books/mass-upload` POST.
- Remove those same entries from `AuthMiddleware`'s allowlist (leave the page-route entries there for now; they get deleted wholesale in the cutover milestone).

**`App/Controllers/UserController.php::handleLogin`** (line ~67) — add `isAdmin` to the JWT payload itself, not just the session:
```php
$payload = [
    'user_id' => $user['_id'],
    'email' => $user['email'],
    'isAdmin' => $user['isAdmin'] ?? false,
];
```

**`App/Includes/AuthenticatedUser.php::isAdmin()`** — currently session-only; rewrite to also decode the Bearer token, mirroring the existing pattern in `id()`:
```php
public static function isAdmin(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['isAdmin'])) {
        return true;
    }
    $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        return false;
    }
    $decoded = JwtHelper::validateToken(trim(substr($authHeader, 7)));
    return $decoded !== null && !empty($decoded->isAdmin);
}
```
This directly fixes `massUploadBooks()` (`App/Controllers/BookController.php`), which already calls `AuthenticatedUser::isAdmin()` but silently fails for pure-JWT callers today.

**`App/Controllers/BookController.php`** — `addReview()` and `massUploadBooks()` currently read `$_SESSION['user_id']` directly; replace both with `AuthenticatedUser::id()`, returning 401 if null.

**Refresh strategy**: no refresh-token endpoint (1-hour JWT expiry is fine for thesis scope). Axios response interceptor catches 401 → clears the Pinia auth store → redirects to `/login?redirect=<path>` with a toast.

**`App/Services/CasService.php`** (milestone 5, plumbing only — mapping policy stays open per Context):
1. `authenticate()` must parse and return the CAS username from line 2 of the CAS validation response body (currently only inspects line 1), not just a bool.
2. Add `CasService::authenticateAndIssueToken(string $ticket, string $serviceUrl): ?string` — validates the ticket, resolves a local user from the CAS username (placeholder lookup — mark clearly as the pending decision point), mints a JWT the same way `handleLogin` does, returns it or null.
3. `PageRouter::handleRequest()`'s `/cas-login` branch (currently `App/Router/PageRouter.php:61-80`, plain `header('Location: /?login=success')`) redirects instead to `Environment::get('APP_URL') . '/auth/callback#token=' . $token` — a URL **fragment**, so it's never sent to/logged by the server. `AuthCallback.vue` reads `location.hash` on mount, stores the token, and redirects home.

## 3. Frontend tech choices

- **Vue Router**, `createWebHistory()` — no hash mode needed; `public/.htaccess`'s existing rewrite-to-index behavior (and PHP's built-in server fallback) already supports this.
- **Pinia**: one `auth` store (token, decoded claims via a small manual base64 decode, login/logout actions), plus a `books` store only where caching actually helps (e.g. browse/search so Back-nav doesn't refetch) — most views call the API layer directly, no store-per-view.
- **API layer**: one axios instance (`api/client.ts`), `baseURL` from `VITE_API_BASE_URL`, request interceptor attaching `Authorization: Bearer <token>`, response interceptor handling 401 as above. One thin wrapper module per backend resource (`books.ts`, `reviews.ts`, `users.ts`, `admin.ts`, `support.ts`), mirroring `ApiRouter`'s existing grouping.
- **Dev workflow**: `vite.config.ts` → `server.proxy: { '/api': { target: 'http://localhost:8000', changeOrigin: true } }`. Run `php -S localhost:8000 -t public` + `npm run dev` (frontend, port 5173) side by side — no CORS headers touched anywhere.

## 4. Route / component / API mapping

| Vue Route | Component(s) | API calls | Guard |
|---|---|---|---|
| `/` | `Home.vue` (+ `LoginForm.vue` scroll-triggered modal for guests) | `GET /books/featured` | none |
| `/browse` | `Browse.vue`, `BookCard.vue` | `GET /books/list` | none |
| `/search` | `SearchResults.vue`, `BookCard.vue`, `StarRating.vue` | `GET /search/{term}` | none |
| `/books/:id` | `BookDetail.vue`, `ReviewList.vue`, `ReviewForm.vue` | `GET /books/{id}`, `GET /reviews/{bookId}`, `POST /reviews`, `POST /save-book`, `GET /books/{id}/download` | Save/Download/Review gated at component level; page itself public |
| `/read/:id` | `Reader.vue` | `GET /books/{id}/file` (authenticated fetch → blob) | requires auth |
| `/login`, `/signup` | `Login.vue`/`Signup.vue` → `LoginForm.vue`/`SignupForm.vue` | `POST /login`, `POST /signup` | guest-only |
| `/auth/callback` | `AuthCallback.vue` (reads `location.hash`, no visible UI) | none | none |
| `/profile` | `Profile.vue` (tabs: saved/downloaded/edit) | `GET /user/profile`, `GET /saved-books`, `GET /downloaded-books`, `POST /update-profile`, `POST /change-password` | requires auth |
| `/admin` | `admin/Dashboard.vue` (book table: status/featured toggle, edit modal, delete, preview) | `GET /books`, `PUT /books/{id}`, `DELETE /books/{id}` | requires auth + admin |
| `/admin/mass-upload` | `admin/MassUpload.vue` | `POST /books/mass-upload` | requires auth + admin |
| `/admin/logs` | `admin/Logs.vue` | `GET /admin/logs` | requires auth + admin |
| `/docs` | `Docs.vue` | none | none |
| `/:pathMatch(.*)*` | `NotFound.vue` | none | none |

Guards (`router/guards.ts`, `router.beforeEach`) read the Pinia store's decoded `isAdmin` claim — replacing today's client-side-only `localStorage`/`sessionStorage` flag check. The real authorization boundary stays server-side (`AuthenticatedUser::isAdmin()` on every admin API call); the route guard is UX-only, as it should be.

While porting, fix the known frontend defects rather than carrying them over: Vue's template auto-escaping fixes the `view_books.php` unescaped-interpolation XSS by construction (never use `v-html` on untrusted data); `BookCard.vue` must use a single link/button hierarchy, not the nested-`<a>`-inside-`<a>` bug in today's `Components/BookCard.php`; replace `alert()`/`console.error` error handling with the `toast` store; bake in `alt`/`aria` attributes on interactive components as a baseline, not an afterthought.

## 5. PDF reader decomposition

Use `pdfjs-dist` (npm) instead of the current CDN `<script>` approach. Replace the ~470-line `Components/Viewers/PdfViewer.php` blob with `Reader.vue` (toolbar + page container + notes panel) orchestrating five single-concern composables:
- `usePdfDocument.ts` — load PDF via authenticated fetch blob, page count, lazy per-page canvas render via `IntersectionObserver`, navigation.
- `useZoomRotate.ts` — zoom/fit-width/rotation, re-render visible pages on change.
- `useReaderSearch.ts` — full-text search across pages (PDF.js `getTextContent`).
- `useReaderNotes.ts` — add/export-JSON/clear notes, localStorage-backed per book id.
- `useReaderPersistence.ts` — reading-progress + zoom persisted per book in localStorage.
- `useFullscreen.ts` — thin Fullscreen API wrapper.

Theme (light/sepia/dark) and print stay as local `Reader.vue` state/CSS — not worth their own composables.

## 6. Milestones

1. **Scaffold + auth + read-only browsing** — `frontend/` skeleton, Vite config + dev proxy, Pinia auth store, axios client, `Login`/`Signup`/`AuthCallback`, `Home`/`Browse`/`SearchResults`/`BookDetail` (read-only), `NavBar`. Backend items from §2 (payload `isAdmin` claim, `isAdmin()` rewrite, JWT middleware allowlist move) land here since login must work end-to-end. **Verify**: `php -S` + `npm run dev`, login round-trips a real JWT, browse/search/detail render real data.
2. **Authenticated book actions + reviews** — Save/Download/Review on `BookDetail.vue`, `Profile.vue` tabs.
3. **Reader** — `Reader.vue` + five composables, ported feature-by-feature against today's behavior (zoom/rotate/theme/search/notes/fullscreen/print/persistence).
4. **Admin** — `admin/Dashboard.vue`, `admin/MassUpload.vue`, `admin/Logs.vue`, admin route guard. Requires the `isAdmin()` JWT fix from milestone 1.
5. **CAS bridge + cutover** — `CasService` plumbing from §2 (mapping policy resolved by then or explicitly stubbed/config-gated), `AuthCallback.vue` wired up, then the actual cutover: `PageRouter`'s route table collapses to the existing `/cas-login` special case plus a single catch-all serving `public/dist/index.html` for anything not matched by `/api` or `/cas-login`. Delete `App/Views/*`, `App/Views/Components/*`, and the corresponding `PageController` methods once parity is confirmed — clean cutover, not a long-lived dual-stack.
6. **Docker/CI** — see §8.

Each milestone gets its own commit series and its own Playwright pass before moving on.

## 7. Testing / verification

- Per milestone: manual smoke test via `php -S localhost:8000 -t public` + `npm run dev`, exercising the routes added that milestone.
- `composer run check` after every backend edit in §2 — small surgical changes, but touch auth code, so this must stay green throughout.
- **Playwright (`qa/`)**: existing Page Objects (`qa/pages/HomePage.ts`, `ViewBooksPage.ts`, etc.) target server-rendered DOM selectors that won't survive the rewrite. Rewrite each Page Object in lockstep with its milestone (not deferred to the end), adding `data-testid` attributes to new Vue components for stable hooks instead of incidental CSS classes. `qa/support/api.ts` and `qa/fixtures/*` need no changes (same backend endpoints). Update each Page Object's `open()` for the renamed routes (`/browse`, `/search`, `/admin`).
- **CAS**: no CAS server exists in CI (`CAS_SERVER_URL` is a dummy) — verify manually against real HMU CAS once deployed; the ticket→username parsing logic in `CasService` is unit-testable in isolation (feed it a canned CAS response) even without a live server.

## 8. Docker / CI

- **`Dockerfile`**: install Node alongside existing `apt-get` deps, then after `COPY . .`: `RUN cd frontend && npm ci && npm run build`, run before the final `chown -R www-data:www-data` so `public/dist` gets consistent ownership. Multi-stage build is the cleaner long-term answer but not required — a single added `RUN` line is fine for thesis scope.
- **`docker-compose.yml`**: no structural change needed; note that the bind-mount (`.:/var/www/html`) shadows the image-build-time `public/dist` in local dev, so `npm run build` (or `npm run dev` against the proxy) needs to run on the host for local compose use.
- **`.github/workflows/playwright.yml`** and **`.github/workflows/elib.yml`**: add a `cd frontend && npm ci && npm run build` step before "Start PHP built-in server" in each, so `public/dist/index.html` exists when Playwright/the smoke test hits `/`.
- **`.github/workflows/lint.yml`**: stays PHP-only for now; optionally add `vue-tsc --noEmit`/eslint for `frontend/` later, not required for the rewrite itself.

## Critical files

- `public/index.php` — middleware allowlists
- `App/Includes/AuthenticatedUser.php` — `isAdmin()` rewrite
- `App/Controllers/UserController.php` — JWT payload
- `App/Controllers/BookController.php` — `addReview`/`massUploadBooks` session→`AuthenticatedUser::id()`
- `App/Router/PageRouter.php` — CAS branch, eventual catch-all cutover
- `App/Services/CasService.php` — ticket validation + token issuance
- `Dockerfile`, `.github/workflows/playwright.yml`, `.github/workflows/elib.yml` — build step
