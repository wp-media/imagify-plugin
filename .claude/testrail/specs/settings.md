---
testrail_sections: [32, 7689]
feature: "Imagify Settings"
source_files: [views/page-settings.php, views/notice-temporary.php, inc/classes/class-imagify-settings.php, inc/classes/class-imagify-options.php, inc/classes/class-imagify-auto-optimization.php, classes/Notices/Notices.php, classes/Abilities/UpdateSettings.php]
derived_sha: e4826be7
last_explored: 2026-06-30
---

## Overview
The Imagify settings screen at `/wp-admin/options-general.php?page=imagify` (TestRail section
32 = Settings, 7689 = general settings). Covers the General Settings toggles (auto-optimize,
backup, lossless, …), the temporary admin-notice rendering path (kses-escaped, `<code>`-only),
and the multisite/network save path. Settings persist in the WP option `imagify_settings`.
The General Settings section is visible because a VALID API key is configured in this env (the
account-only view is shown when the key is invalid/empty). Capability gate: `imagify_capacity`
via `imagify_get_context('wp')->current_user_can('manage')` (see `_foundation.md`).

## Ground truth
- Auto-optimize is option key **`auto_optimize`** in `imagify_settings`; integer enum **0|1**
  (UpdateSettings.php:64-68). Default 1 in the "live-defaults" merge, 0 in the bare defaults
  (class-imagify-options.php:33 / :59). Live value at explore time: **1** (enabled).
- Live `imagify/get-settings` returns these keys (captured live this explore):
  `admin_bar_menu, auto_optimize, backup, cdn_url, convert_to_avif, convert_to_webp,
  disallowed-sizes, display_nextgen, display_nextgen_method, display_webp, display_webp_method,
  lossless, optimization_format, optimization_level, partner_links, resize_larger,
  resize_larger_w`. NOTE: `api_key` and `version` are NOT returned by get-settings (managed
  separately) — do not expect them in a snapshot.
- Auto-optimize behaviour on upload (class-imagify-auto-optimization.php:183-239,
  hook `store_ids_to_optimize` on `wp_generate_attachment_metadata`):
  - enabled  → a NEW upload is queued for optimization; the action
    `imagify_auto_optimize_attachment` filter runs and the attachment gets optimized
    (writes `_imagify_status` / `_imagify_data` post meta).
  - disabled → the action **`imagify_new_attachment_auto_optimization_disabled`** fires and the
    method returns early; no `_imagify_*` meta is written for the new upload.
  - Gate also requires a valid API key (`Imagify_Requirements::is_api_key_valid()`).
- Temporary admin notices (C14169 — escaping) **`server: apache`**: rendered by `views/notice-temporary.php`, which
  emits the message through **`wp_kses( $details['message'], [ 'code' => [] ] )`** — i.e. only
  `<code>` survives, every other tag is stripped. Notice messages legitimately contain `<code>`
  (e.g. the .htaccess error in `classes/Webp/Display.php:114` wraps a file path in `<code>…</code>`).
  Storage transient: `imagify_temporary_notices` (site) / same site-transient name (network);
  read-once then deleted; messages deduplicated on insert (Notices.php:705-710). This is the
  fix from commit `f71ec9b3` "Coding Standards: WordPress security escape output (#994)".
- Multisite save path (C174): network settings are saved by
  `Imagify_Settings::update_site_option_on_network()` hooked on `admin_post_update`
  (class-imagify-settings.php:354-411), requires capability `manage_network_options` and the
  `<settings_group>-options` nonce, and writes each option via `update_site_option()`. Single
  vs network branching is wired in the constructor (lines 67-69). This path runs only in
  network admin and therefore requires a **multisite install** — NOT available in this env.

## How to invoke (grounded from live)
- Admin UI (single site): GET `http://localhost:10038/wp-admin/options-general.php?page=imagify`
  → toggle a checkbox → click Save (`#submit`, value "Save Changes"). Form POSTs to
  `options.php` (single) and redirects back to `?page=imagify` with a "Settings saved." notice.
- Admin UI (multisite, C174): `http://<site>/wp-admin/network/settings.php?page=imagify`
  → Save → POSTs to `admin_post_update` → `update_site_option_on_network()`. Requires multisite.
- Abilities REST (snapshot / programmatic mutate — captured live):
  - Read:  `GET  /wp-json/wp-abilities/v1/abilities/imagify/get-settings/run`
    (read-only ability → **GET required**; POST returns 405 `rest_ability_invalid_method`).
  - Write: `POST /wp-json/wp-abilities/v1/abilities/imagify/update-settings/run`
    body `{ "auto_optimize": 0 }` (integer enum 0|1).
  - Both require an authenticated WP session + `X-WP-Nonce` header (401 anonymously). Acquire
    nonce + cookie per `_foundation.md`. The run href is the ability's `_links["wp:action-run"]`.

## Locators (captured live — role-based preferred, then data-testid, then id)
Locators verified live this explore on the settings page (login admin/admin first; reuse the
`SettingsPage` POM at `Tests/e2e/pages/settings.ts` and `wp-login` fixture). No data-testid
attributes exist on this page; fall back is id/name.

- Settings page nav: `page.goto('/wp-admin/options-general.php?page=imagify')`.
- Section heading (General Settings):
  `page.getByRole('heading', { name: 'General Settings' })`  → 1 match.
- Auto-optimize checkbox (C155/C156) — PREFERRED:
  `page.getByLabel('Auto-Optimize images on upload')`  → 1 match (label text captured verbatim,
  note the capital "O" in "Optimize").
  - id fallback:   `page.locator('#imagify_auto_optimize')`  (input, type=checkbox)
  - name fallback: `page.locator('[name="imagify_settings[auto_optimize]"]')`
  - associated label: `page.locator('label[for="imagify_auto_optimize"]')`
    (text "Auto-Optimize images on upload"); the label overlays the input, so toggle via the
    checkbox with `{ force: true }` (matches the htaccess-notice-dedup spec pattern).
  - aria-describedby: `describe-auto_optimize`.
- Save button: `page.locator('#submit')` (input, value "Save Changes"). POM: `settings.saveButton`.
- "Settings saved." confirmation after save (captured live):
  `page.locator('#setting-error-settings_updated')` → 1 match, classes
  `notice notice-success settings-error is-dismissible`, text "Settings saved.".
  POM: `settings.successNotice` = `.notice-success, .updated`.
- Temporary notice wrapper (C14169) — the kses-escaped notice structure, captured live as the
  same WP settings-error wrapper used by `notice-temporary.php`:
  `page.locator('.settings-error.notice.is-dismissible')` (the message is inside `p > strong`:
  `page.locator('.settings-error.notice.is-dismissible p strong')`). For escaping assertions,
  read the `innerHTML` of that `strong` and confirm `<code>` is preserved while other tags are
  absent.
- Fatal-error guard (every case): `page.locator('.wp-die-message, #error-page')` → expect 0.
  POM: `settings.expectNoFatalError()`.

## Prerequisites & seeding (per operation)
- Valid API key configured (so General Settings section renders): live env already satisfies
  this (option-based key, see `_foundation.md`). If absent, the auto-optimize toggle is hidden.
- Snapshot BEFORE any mutation (settings cases C155/C156/C14169):
  - `GET /wp-json/wp-abilities/v1/abilities/imagify/get-settings/run` (auth + nonce) — capture
    the full settings JSON, in particular `auto_optimize`. (See `_foundation.md` auth block.)
- C155 (auto-optimize ON): ensure `auto_optimize = 1` before the test. Seed via UI (check the
  box + Save) or `POST update-settings {"auto_optimize":1}`, then upload a fresh supported image
  (REST media upload helper in `_foundation.md`) and let metadata generation run.
- C156 (auto-optimize OFF): set `auto_optimize = 0` first (UI uncheck + Save, or
  `POST update-settings {"auto_optimize":0}`), then upload a fresh image.
- C14169 (temporary-notice escaping): no settings mutation strictly required to verify the
  render path — open the settings page and read the `.settings-error.notice` markup. To exercise
  a real temporary notice carrying `<code>`, the .htaccess-not-writable path can be triggered
  (set .htaccess to 444 then enable `display_nextgen` + Save — see `htaccess-notice-dedup.spec.ts`),
  which stores a notice whose message wraps a file path in `<code>`.
- C174 (multisite, no fatal on settings change): **BLOCKED in this env** — requires a WordPress
  **multisite** install with the plugin network-active. This Local site (test-temp) is single
  site (the network admin URL `/wp-admin/network/settings.php?page=imagify` does not exist here).
  Seeding a multisite is out of scope for the live env; document as a prerequisite and skip.

## Verification criteria — "success" means (observable)
- C155 (Auto-optimize ON): after toggling ON + Save, the page reloads to `?page=imagify` with
  the "Settings saved." notice (`#setting-error-settings_updated`) and NO fatal
  (`.wp-die-message, #error-page` count 0); `get-settings` returns `auto_optimize == 1`. On a
  fresh upload with a valid key, the new attachment becomes optimized — observable as
  `_imagify_status` post meta = `success` / `_imagify_data` present (or the media-library Imagify
  column showing an optimization result), i.e. the attachment was auto-optimized on upload.
- C156 (Auto-optimize OFF / disabling): after toggling OFF + Save, page reloads with "Settings
  saved.", no fatal, and `get-settings` returns `auto_optimize == 0`; the checkbox is unchecked
  on reload (`#imagify_auto_optimize` not checked). On a fresh upload, the attachment is NOT
  auto-optimized — observable as absence of `_imagify_status`/`_imagify_data` meta on the new
  attachment (the `imagify_new_attachment_auto_optimization_disabled` early-return path).
- C14169 (escape code for temporary notices): the temporary-notice message is rendered with only
  `<code>` allowed — observable by reading the notice `strong` innerHTML: a `<code>…</code>`
  segment is preserved and any other HTML tag in the source message is stripped (kses with
  `['code'=>[]]`); the page shows no raw/unescaped markup and no fatal error. Tautology guard:
  it is NOT enough that "the page loaded" — the assertion is on the kses-filtered HTML content.
- C174 (multisite no fatal): on a multisite network-admin save of Imagify settings, the request
  completes (HTTP 302 back to the settings page), the option is written via `update_site_option`,
  and NO PHP fatal/`imagify_die()` error page is shown (`.wp-die-message, #error-page` count 0).
  Cannot be asserted in this single-site env — BLOCKED pending a multisite install.

## Teardown (LIFO)
Restore in reverse order of seeding. Each case is independent; undo only what it changed.
1. Delete any attachment seeded for C155/C156 (and its Imagify meta):
   - `wp post meta delete <id> _imagify_data` ; `wp post meta delete <id> _imagify_status`
     (inside the Local site shell), or delete the attachment entirely via REST:
     `DELETE /wp-json/wp/v2/media/<id>?force=true` (auth + nonce — see `_foundation.md`).
2. Restore the settings snapshot taken in "Prerequisites & seeding":
   - `POST /wp-json/wp-abilities/v1/abilities/imagify/update-settings/run` with the snapshotted
     keys (in particular restore `auto_optimize` to its pre-test value — live value was **1**),
     or `wp option update imagify_settings '<json snapshot>' --format=json` inside the Local shell.
3. C14169: if a temporary notice was triggered via the .htaccess path, restore
   `.htaccess` permissions (e.g. back to 644) and turn `display_nextgen` back to its prior value
   (UI uncheck + Save). The temporary-notices transient is read-once and auto-cleared, so no
   explicit transient delete is needed.
4. C174: n/a in this env (not executed).
