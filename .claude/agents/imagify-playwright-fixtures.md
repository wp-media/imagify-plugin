---
name: imagify-playwright-fixtures
description: Read-only fixture reference for Playwright-driven QA on the Imagify WordPress plugin — admin URLs, selectors, ability slugs, and MCP endpoint patterns, expressed as @playwright/test code. Not a spawnable agent — read and inlined by testrail-run-agent and testrail-explorer-agent. Supersedes canary-imagify-session-agent.md (Canary retired for Imagify QA).
---

# Imagify Playwright Fixtures (reference — not spawnable)

This file is **not meant to be spawned as an agent.** Its callers (`testrail-run-agent`,
`testrail-explorer-agent`) inline the snippets below into the `.spec.ts` files they generate
or the `mcp__playwright` calls they drive. There is deliberately no `tools` or `model`
frontmatter: nothing here should ever execute independently.

Reuse the real fixtures under `Tests/e2e/` wherever they already cover what you need —
don't reimplement:
- `Tests/e2e/fixtures/auth.ts` → `loginAsAdmin(page)` (idempotent login; reads
  `IMAGIFY_ADMIN_USER` / `IMAGIFY_ADMIN_PASS` env vars, defaults `admin`/`password`)
- `Tests/e2e/fixtures/wp-cli.ts` → `wpCli()` / `runFromRepoRoot()` / `hasApiKey()` — these
  shell out to `npx @wordpress/env run cli`, which targets the **wp-env CI stack**, NOT the
  TestRail nginx/apache environments (those use `wp --path=$WP_PATH` directly against a
  different install). Do not use `wpCli()` for TestRail seeding — use `wp --path=$WP_PATH`
  as documented in `testrail-run-agent.md`.
- `Tests/e2e/pages/settings.ts` → `SettingsPage`
- `Tests/e2e/pages/bulk-optimization.ts` → `BulkOptimizationPage`
- `Tests/e2e/pages/media-library.ts` → `MediaLibraryPage`

There is currently **no POM** for the Custom Folders / Files admin surface
(`/wp-admin/upload.php?page=imagify-files`) — write locators fresh for that surface (and
consider adding a POM to `Tests/e2e/pages/` if a spec starts depending on it repeatedly).

## Imagify admin URLs (reference)

| Purpose          | URL                                                         |
|------------------|-------------------------------------------------------------|
| Settings         | `/wp-admin/options-general.php?page=imagify`                |
| Bulk optimize    | `/wp-admin/upload.php?page=imagify-bulk-optimization`       |
| Media library    | `/wp-admin/upload.php?mode=list`                            |
| Custom folders   | `/wp-admin/upload.php?page=imagify-files`                   |

Endpoints:
- WP Abilities: `/wp-json/wp-abilities/v1/abilities`
- MCP server:   `/wp-json/mcp/mcp-adapter-default-server`

## Imagify selectors (named fixtures — use these, do not invent alternatives)

```
API key input:       #imagify-api-key, [name="imagify_settings[api_key]"]
Save button:         #submit
Success notice:      .notice-success, .updated
Invalid API key:     #imagify-check-api-container:not(.imagify-valid)   ← NOT .notice-error
Bulk action btn:     #imagify-bulk-action
Progress bar:        .imagify-row-progress
Stats table:         .imagify-bulk-table
Fatal error check:   .wp-die-message, #error-page
Media column:        th[id*="imagify"], th.column-imagify
```

These match `Tests/e2e/pages/settings.ts` verbatim where overlapping — if a POM exists for the
surface you're touching, use the POM method instead of the raw locator below.

### FIXTURE: invalid API key is rejected

Imagify does **not** surface an invalid API key via `.notice-error`. Assert against the
API-key container instead:

```ts
await expect(page.locator('#imagify-check-api-container:not(.imagify-valid)'))
  .toBeVisible({ timeout: 5000 });
```

## Imagify ability slugs (all 10)

```
imagify/get-settings            imagify/update-settings
imagify/get-account             imagify/get-stats
imagify/get-media-status        imagify/get-nextgen-coverage
imagify/optimize-media          imagify/bulk-optimize
imagify/generate-missing-nextgen imagify/restore-media
```

### FIXTURE: assert all 10 expected ability slugs are registered

```ts
const result = await page.evaluate(async () => {
  const nonceResp = await fetch('/wp-admin/admin-ajax.php?action=rest-nonce', { credentials: 'same-origin' });
  const nonce = (await nonceResp.text()).trim();
  const resp = await fetch('/wp-json/wp-abilities/v1/abilities', {
    headers: { 'X-WP-Nonce': nonce }, credentials: 'same-origin',
  });
  return { status: resp.status, body: await resp.text() };
});
const data = JSON.parse(result.body);
const slugs = Array.isArray(data) ? data.map((a: any) => a.name) : [];
const expected = [
  'imagify/get-settings', 'imagify/update-settings', 'imagify/get-account',
  'imagify/get-stats', 'imagify/get-media-status', 'imagify/get-nextgen-coverage',
  'imagify/optimize-media', 'imagify/bulk-optimize', 'imagify/generate-missing-nextgen',
  'imagify/restore-media',
];
for (const s of expected) {
  expect.soft(slugs, `ability slug missing: ${s}`).toContain(s);
}
expect.soft(result.status, 'abilities endpoint status').toBe(200);
expect.soft(result.body, 'no PHP fatal in abilities response').not.toContain('Fatal error');
```

## DO NOT

- Do NOT assert invalid API keys via `.notice-error` — use
  `#imagify-check-api-container:not(.imagify-valid)`.
- Do NOT invent selectors — use the named ones above, or the matching `Tests/e2e/pages/` POM.
- Do NOT use `Tests/e2e/fixtures/wp-cli.ts`'s `wpCli()` for TestRail seeding — it targets the
  wrong environment (wp-env, not the TestRail nginx/apache installs).
