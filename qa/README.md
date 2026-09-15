# E-Lib E2E Tests

Playwright + TypeScript end-to-end tests for E-Lib. Tests live in `qa/tests`.

## Setup

```bash
npm install
npx playwright install --with-deps   # downloads browser binaries
```

**On NixOS** (`nix-shell`), skip `playwright install`: the shell already provides
matching, pre-patched browsers via `pkgs.playwright-driver.browsers` and sets
`PLAYWRIGHT_BROWSERS_PATH` for you (see `shell.nix`). Playwright's own downloaded
binaries won't launch on NixOS (missing FHS shared libs). Just run `npm install`
inside `nix-shell`, then the commands below. If you bump `@playwright/test` in
`package.json`, bump the `playwright-driver.browsers` revision in `shell.nix` to
match, or launches will fail with a browser/revision mismatch.

## Running

The suite drives a real instance of the app, so start one first — MongoDB is required, there is no fallback (see `public/index.php`):

```bash
# Option A: PHP built-in server (needs MONGO_URI set in .env, e.g. Atlas)
php -S localhost:8000 -t public

# Option B: Docker Compose (app + MongoDB)
docker-compose up -d
```

Then, from the repo root:

```bash
npm run test:e2e            # headless, all browsers
npm run test:e2e:headed     # headed, for debugging
npm run test:e2e:ui         # Playwright UI mode
npm run test:e2e:report     # open the last HTML report
```

By default tests target `http://localhost:8000`. Point them elsewhere with:

```bash
PLAYWRIGHT_BASE_URL=http://localhost:8081 npm run test:e2e
```

## Layout

```
qa/
  tests/          # *.spec.ts test files
playwright.config.ts   # config (repo root, testDir points at qa/tests)
```
