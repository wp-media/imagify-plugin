---
derived_sha: 4bc0e342
source_files: [imagify.php, inc/classes/class-imagify-options.php, classes/MCP/AbilitiesSubscriber.php, classes/Abilities/*.php, Tests/e2e/fixtures/auth.ts, Tests/e2e/pages/*.ts]
last_explored: 2026-06-30
---

# Foundation — environment, Playwright reuse, seeding (Imagify QA)

Non-DOM, low-volatility grounding shared by every feature spec. The run agent loads this
file plus the per-feature spec before executing any case. This file also absorbs the former
`imagify-playwright-fixtures.md` — it is the single knowledge home; agents carry process,
`Tests/e2e/` carries executable truth, this directory carries grounding.

## Environment

The QA environments are the **ephemeral Docker containers** managed by `bin/qa-env.sh`
(one nginx + one apache WordPress, plugin installed from `bin/build-zip.sh`'s zip) — never a
dev's hand-built local site. **Never hardcode a URL, port, or credential — everything comes
from `.ai/settings.local.json`**, which `bin/qa-env.sh up` GENERATES:

```json
{
  "environments": {
    "nginx":  { "url": "...", "username": "...", "password": "...", "wp_cli": "..." },
    "apache": { "url": "...", "username": "...", "password": "...", "wp_cli": "..." }
  },
  "imagify":  { "api_key": "..." },
  "testrail": { "username": "...", "api_key": "..." }
}
```

`wp_cli` is the full WP-CLI command prefix for that env (a `docker compose run … wp`
wrapper). All examples below write `$E2E_URL` and `$WPCMD` — resolve them from the config
for the case's target env before use.

- nginx is the default env; apache is for cases involving `.htaccess`, rewrite rules, or
  `$is_apache` code paths (flagged per-case via the feature spec's `apache_cases` frontmatter).
- WP admin: `$E2E_URL/wp-admin/` — login via `/wp-login.php`, role-based locators:
  `getByLabel("Username or Email Address")`, `getByLabel("Password", { exact: true })`,
  `getByRole("button", { name: "Log In" })`.
- WP version requirement: >= 6.9 for the Abilities API (registration no-ops below it).

## Playwright reuse (formerly imagify-playwright-fixtures.md)

Reuse the real code under `Tests/e2e/` — never reimplement:

- `Tests/e2e/fixtures/auth.ts` → `loginAsAdmin(page)` — idempotent login; reads
  `IMAGIFY_ADMIN_USER` / `IMAGIFY_ADMIN_PASS` env vars and the config `baseURL`, so generated
  specs stay env-agnostic (the run agent passes the env at invocation time).
- `Tests/e2e/pages/settings.ts` → `SettingsPage`;
  `Tests/e2e/pages/bulk-optimization.ts` → `BulkOptimizationPage`;
  `Tests/e2e/pages/media-library.ts` → `MediaLibraryPage`.
  **A POM method beats any raw locator written here or in a feature spec.**
- `Tests/e2e/fixtures/wp-cli.ts` (`wpCli()` / `hasApiKey()`) shells out to
  `npx @wordpress/env run cli` — the **wp-env CI stack**, NOT the TestRail environments.
  Never use it for TestRail seeding; use `$WPCMD`.
- No POM exists yet for the Custom Folders surface (`/wp-admin/upload.php?page=imagify-files`)
  — locators for it must be grounded fresh; add a POM if a spec starts depending on it.

### Imagify admin URLs

| Purpose        | Path                                                  |
|----------------|-------------------------------------------------------|
| Settings       | `/wp-admin/options-general.php?page=imagify`          |
| Bulk optimize  | `/wp-admin/upload.php?page=imagify-bulk-optimization` |
| Media library  | `/wp-admin/upload.php?mode=list`                      |
| Custom folders | `/wp-admin/upload.php?page=imagify-files`             |

Endpoints: WP Abilities `/wp-json/wp-abilities/v1/abilities`; MCP server
`/wp-json/mcp/mcp-adapter-default-server`.

### Named selectors (only where no POM covers the surface)

```
API key input:       #api_key, [name="imagify_settings[api_key]"]
Save button:         #submit
Success notice:      #setting-error-settings_updated  (.notice-success, .updated)
Invalid API key:     #imagify-check-api-container:not(.imagify-valid)   ← NOT .notice-error
Fatal error guard:   .wp-die-message, #error-page   → expect count 0 on every page load
Media column:        th[id*="imagify"], th.column-imagify
```

Imagify does **not** surface an invalid API key via `.notice-error` — assert the API-key
container's validity class instead.

### Ability slugs (all 7 — grounded live; see mcp-abilities.md for full behavior)

```
imagify/get-settings      imagify/update-settings   imagify/get-account
imagify/get-stats         imagify/get-media-status  imagify/get-nextgen-coverage
imagify/optimize-media
```

These 7 are what `classes/Abilities/` registers. Do not assert on any other slug without
re-grounding — earlier drafts listed slugs that do not exist in the code.

## Prerequisites

- Imagify API key: stored in the WP option **`imagify_settings`**, key **`api_key`**.
  Settings field: `name="imagify_settings[api_key]" id="api_key"`. The PHP constant
  **`IMAGIFY_API_KEY`** is an optional override: when defined+truthy it wins over the option,
  locks the settings field, and makes `update-settings api_key` return
  `imagify_api_key_immutable`. (Source: inc/classes/class-imagify-options.php:72-116;
  classes/Abilities/UpdateSettings.php:278-282.)
- Settings page: `/wp-admin/options-general.php?page=imagify` — the General Settings section
  renders only when a valid API key is configured.
- Capability gate: the `imagify_capacity` filter via
  `imagify_get_context('wp')->current_user_can('manage')` — NOT a bare `current_user_can()`.

## Test users (for permission cases)

| Role          | Login                | imagify_capacity | Seed                |
|---------------|----------------------|------------------|---------------------|
| administrator | from config          | full             | exists by default   |
| editor        | seeded per case      | limited          | see Seeding helpers |
| subscriber    | seeded per case      | denied           | see Seeding helpers |

## Authentication for live API/MCP/REST calls

Abilities REST + MCP endpoints require an authenticated WP session + REST nonce (both return
HTTP **401** anonymously):

```bash
# 1. Log in (cookie jar) — creds from the config, never hardcoded
curl -s -c cookies.txt -b cookies.txt \
  --data-urlencode "log=$WP_USER" --data-urlencode "pwd=$WP_PASS" \
  --data-urlencode "wp-submit=Log In" --data-urlencode "testcookie=1" \
  --data-urlencode "redirect_to=$E2E_URL/wp-admin/" \
  "$E2E_URL/wp-login.php" -o /dev/null
# 2. REST nonce
NONCE=$(curl -s -b cookies.txt "$E2E_URL/wp-admin/admin-ajax.php?action=rest-nonce")
# 3. Use: -H "X-WP-Nonce: $NONCE" -b cookies.txt
```

## Seeding helpers (via `$WPCMD` / REST — Bash, never the UI)

Each mutation has a matching teardown below.

```bash
# --- Set / clear the API key (option-based; only when IMAGIFY_API_KEY constant is NOT defined) ---
$WPCMD option patch update imagify_settings api_key "<key>"
$WPCMD option patch update imagify_settings api_key ""

# --- Toggle an arbitrary setting ---
$WPCMD option patch update imagify_settings <key> <value>
#   live keys: optimization_level, lossless, auto_optimize, backup, resize_larger,
#   resize_larger_w, display_nextgen, display_nextgen_method, display_webp,
#   display_webp_method, cdn_url, disallowed-sizes, admin_bar_menu, partner_links,
#   convert_to_avif, convert_to_webp, optimization_format
#   (api_key + version are managed separately; version is read-only)

# --- Seed an attachment (media-status / optimize-media cases) ---
curl -s -b cookies.txt -H "X-WP-Nonce: $NONCE" \
  -H "Content-Disposition: attachment; filename=seed.jpg" -H "Content-Type: image/jpeg" \
  --data-binary @<local.jpg> "$E2E_URL/wp-json/wp/v2/media" \
  | python3 -c "import sys,json;print(json.load(sys.stdin)['id'])"

# --- Create a non-admin test user (permission cases) ---
$WPCMD user create <login> <email> --role=<role> --user_pass=<pass>
```

## Teardown helpers (LIFO undo)

```bash
# --- Restore an attachment to its pre-optimization state ---
$WPCMD post meta delete <id> _imagify_data
$WPCMD post meta delete <id> _imagify_status
# (a freshly seeded attachment: delete it entirely)
curl -s -b cookies.txt -H "X-WP-Nonce: $NONCE" -X DELETE \
  "$E2E_URL/wp-json/wp/v2/media/<id>?force=true" -o /dev/null

# --- Re-apply a settings snapshot taken before a mutation ---
#   Snapshot BEFORE via GET get-settings/run; restore via update-settings, or:
$WPCMD option update imagify_settings '<json snapshot>' --format=json

# --- Delete a seeded test user ---
$WPCMD user delete <login> --yes --reassign=1

# --- Nuclear option (qa-env setups only): restore the post-setup DB snapshot ---
bash bin/qa-env.sh reset <nginx|apache>
```

## Drift detection inputs

`source_files` (frontmatter) are the globs whose latest commit is compared against
`derived_sha` **by git ancestry** (`git merge-base --is-ancestor <latest> <derived>` → FRESH),
not by string comparison. `/testrail-setup --check` reports this spec stale when any source
file changed after the stamp.
