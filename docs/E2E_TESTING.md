# Imagify — End-to-End Testing

This document is the canonical reference for the Imagify Playwright E2E test suite. **Read this before writing any new tests.**

---

## Architecture overview

| Layer | Path | Purpose |
|-------|------|---------|
| Config | `Tests/e2e/playwright.config.ts` | Playwright base config (baseURL, reporters, timeouts) |
| Fixtures | `Tests/e2e/fixtures/` | Shared helpers: login, WP-CLI wrapper, API key guard |
| Page objects | `Tests/e2e/pages/` | One class per major admin surface |
| Specs | `Tests/e2e/specs/` | Test files, one per feature area |
| Dev scripts | `bin/dev-up.sh`, `bin/dev-down.sh`, `bin/dev-seed.sh` | Local environment lifecycle |
| CI | `.github/workflows/e2e.yml` | Automated runs on pull requests |


All tests run against a local WordPress environment managed by `@wordpress/env` (Docker). The environment maps the plugin root directly into the container at `wp-content/plugins/imagify`.

---

## Admin URLs

| Surface | URL |
|---------|-----|
| Settings | `/wp-admin/options-general.php?page=imagify` |
| Bulk optimization | `/wp-admin/upload.php?page=imagify-bulk-optimization` |
| Custom folders | `/wp-admin/upload.php?page=imagify-files` |
| Media library (list) | `/wp-admin/upload.php?mode=list` |
| Plugins list | `/wp-admin/plugins.php` |

---

## Running tests locally

### One-time setup

```bash
# From the repository root
bash bin/dev-up.sh       # Start wp-env + activate plugin + seed test data
cd Tests/e2e
npm install
npx playwright install chromium
```

### Running

```bash
cd Tests/e2e
npm test                 # Headless, list reporter
npm run test:headed      # With browser UI visible
npm run test:ui          # Playwright interactive UI mode
npm run report           # Open the last HTML report
```

### Environment variables

| Variable | Default | Purpose |
|----------|---------|---------|
| `IMAGIFY_BASE_URL` | `http://localhost:8888` | Override the WordPress base URL |
| `IMAGIFY_ADMIN_USER` | `admin` | WP admin username |
| `IMAGIFY_ADMIN_PASS` | `password` | WP admin password |
| `IMAGIFY_TESTS_API_KEY` | _(unset)_ | Real Imagify API key — required for optimization tests |

Set `IMAGIFY_TESTS_API_KEY` to run tests that call the Imagify API. Without it, those tests are automatically skipped.

---

## Page objects

Each major admin surface has a Page Object class. Use them instead of raw selectors — they centralize selector maintenance.

### `SettingsPage` (`Tests/e2e/pages/settings.ts`)

```typescript
import { SettingsPage } from '../pages/settings';
const settings = new SettingsPage( page );
await settings.goto();
await settings.setApiKey( process.env.IMAGIFY_TESTS_API_KEY! );
```

Key members: `apiKeyInput`, `saveButton`, `successNotice`, `goto()`, `setApiKey()`, `getApiKey()`, `expectNoFatalError()`.

### `BulkOptimizationPage` (`Tests/e2e/pages/bulk-optimization.ts`)

```typescript
import { BulkOptimizationPage } from '../pages/bulk-optimization';
const bulk = new BulkOptimizationPage( page );
await bulk.goto();
await expect( bulk.optimizeButton ).toBeVisible();
```

Key members: `optimizeButton`, `progressBar`, `statsTable`, `goto()`, `expectNoFatalError()`.

### `MediaLibraryPage` (`Tests/e2e/pages/media-library.ts`)

```typescript
import { MediaLibraryPage } from '../pages/media-library';
const library = new MediaLibraryPage( page );
await library.goto();
await expect( library.imagifyColumn ).toBeVisible();
```

Key members: `imagifyColumn`, `goto()`, `hasImagifyColumn()`, `getFirstAttachmentStatus()`, `expectNoFatalError()`.

---

## Fixtures

### `loginAsAdmin( page )` — `Tests/e2e/fixtures/auth.ts`

Logs in as the WordPress administrator. Idempotent: skips the login form if a session cookie is already active.

```typescript
import { loginAsAdmin } from '../fixtures/auth';
test.beforeEach( async ( { page } ) => {
    await loginAsAdmin( page );
} );
```

### `wpCli( command )` — `Tests/e2e/fixtures/wp-cli.ts`

Runs a WP-CLI command inside the wp-env `cli` container. Returns stdout as a string.

```typescript
import { wpCli } from '../fixtures/wp-cli';
const value = wpCli( 'option get imagify_settings --format=json' );
```

### `hasApiKey()` — `Tests/e2e/fixtures/wp-cli.ts`

Returns `true` if `IMAGIFY_TESTS_API_KEY` is set. Use with `test.skip` to gate API-dependent tests:

```typescript
import { hasApiKey } from '../fixtures/wp-cli';
test.skip( ! hasApiKey(), 'IMAGIFY_TESTS_API_KEY not set' );
```

---

## Writing new tests

### Determinism rules

- **Never** use `setTimeout` or `waitForTimeout`. Use web-first assertions (`toBeVisible`, `toHaveValue`, `toHaveURL`, etc.) which have built-in retry.
- **Never** assert on volatile values (timestamps, auto-increment IDs) without normalization.
- **Always** seed state before tests that depend on specific DB content.

### API key guard

Any test that triggers image optimization through the Imagify API must be guarded:

```typescript
test( 'Optimizing an image succeeds', async ( { page } ) => {
    test.skip( ! process.env.IMAGIFY_TESTS_API_KEY, 'IMAGIFY_TESTS_API_KEY not set — skipping live optimization test' );
    // ... test body
} );
```

### Adding a new page object

Create `Tests/e2e/pages/<feature>.ts`:

```typescript
import { Page, Locator, expect } from '@playwright/test';

export class FeaturePage {
    readonly page: Page;
    // locators...

    constructor( page: Page ) {
        this.page = page;
        // initialize locators
    }

    async goto(): Promise<void> {
        await this.page.goto( '/wp-admin/...' );
        await this.page.waitForLoadState( 'networkidle' );
    }

    async expectNoFatalError(): Promise<void> {
        await expect( this.page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
    }
}
```

### Adding a new spec

Create `Tests/e2e/specs/<feature>.spec.ts`. Follow this template:

```typescript
import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { FeaturePage } from '../pages/<feature>';

test.describe( 'Feature area', () => {
    test.beforeEach( async ( { page } ) => {
        await loginAsAdmin( page );
    } );

    test( 'Something works', async ( { page } ) => {
        const featurePage = new FeaturePage( page );
        await featurePage.goto();
        await featurePage.expectNoFatalError();
        // assertions...
    } );
} );
```

---

## CI

The E2E workflow (`.github/workflows/e2e.yml`) runs on pull requests that touch:
- `classes/**`, `inc/**`, `assets/**`, `views/**` — PHP/JS plugin code
- `Tests/e2e/**` — test files themselves
- `.wp-env.json`, `bin/dev-up.sh`, `bin/dev-seed.sh` — environment config
- `composer.json`, `.github/workflows/e2e.yml`

The `IMAGIFY_TESTS_API_KEY` secret must be set in the GitHub repository settings for API-dependent tests to run. Without it, those tests are automatically skipped and the suite still passes.

---

## Known coverage gaps

The following areas are not yet covered by automated E2E tests (good places to contribute):

- Custom folders optimization workflow
- WP-CLI bulk-optimize and bulk-restore commands (use `wpCli()` fixture)
- Admin notices (API quota reached, outdated plugin)
- Network mode (proxy URL, login/password settings)
- Multisite network activation
