---
testrail_sections: [8725]
feature: "Mixpanel Tracking / Analytics"
source_files: [classes/Tracking/*.php, views/part-settings-analytics.php, views/notice-analytics-thankyou.php, classes/Abilities/AbstractAbility.php, classes/Abilities/OptimizeMedia.php, vendor/wp-media/wp-mixpanel/src/Optin.php, vendor/wp-media/wp-mixpanel/src/TrackingPlugin.php, vendor/wp-media/wp-mixpanel/src/Tracking.php, vendor/wp-media/wp-mixpanel/src/WPConsumer.php]
derived_sha: 542b43d4
last_explored: 2026-06-30
---

## Overview
Imagify's Mixpanel analytics tracking (TestRail section **8725** "Mixpanel Tracking", parent 33).
Anonymous, opt-in usage telemetry added in plugin **2.3.0**. Two surfaces emit the same Mixpanel
events: the UI/optimization path (`Imagify\Tracking\Subscriber` → `Tracking`) and the MCP ability
path (`McpTrackingSubscriber` → `McpTracking`, context overridden to `wp_plugin_mcp`). Every event
is gated by a single opt-in flag and only sent when the admin has explicitly enabled "Imagify
Analytics" on the settings page. The opt-in toggle is **unchecked by default** and is a separate
control from the General Settings form — it persists via its own AJAX action, NOT the settings Save.

Events are delivered **server-side** via `wp_remote_post` (PHP) to the Mixpanel proxy host, so they
do NOT appear in the browser HAR/Network panel — see "How to verify". The Strauss-prefixed Mixpanel
library lives under namespace `Imagify\Dependencies\WPMedia\Mixpanel` (class alias
`Imagify_WPMedia_ConsumerStrategies_AbstractConsumer`), confirming the dependency was prefixed.

## Ground truth
Captured live this explore unless noted. Mixpanel token (`ServiceProvider::MIXPANEL_TOKEN`):
`517e881edc2636e99a2ecf013d8134d3`; application `imagify`, brand `wp media`.

- **Opt-in storage**: WP option **`imagify_mixpanel_optin`** (slug `imagify` + `_mixpanel_optin`,
  `Optin.php`). `enable()` → `update_option(..., true)`; `disable()` → **`delete_option(...)`**
  (the row is removed, not set to 0). Default: option absent ⇒ `can_track()` false ⇒ no events.
- **Opt-in capability**: `manage_options` (DI: `Optin(application='imagify', 'manage_options')`,
  `ServiceProvider.php:67`). `is_enabled()` returns false for users lacking the cap; the AJAX
  toggle returns **403** for non-`manage_options` users (`Notices::ajax_toggle_optin`).
- **Events emitted** (all via `TrackingPlugin::track_direct( $event, $props )`):
  | Event name | Source class / method | Fires when |
  |---|---|---|
  | `Media Optimized` (UI) | `Tracking::track_media_optimized` | hook `imagify_after_optimize`, only when `'full'` size done AND full-size `success` |
  | `Media Restored` | `Tracking::track_media_restored` | hook `imagify_after_restore_media`, only when `$response` not WP_Error |
  | `Settings Saved` | `Tracking::track_settings_saved` | hook `update_option_imagify_settings` / `update_site_option_imagify_settings` |
  | `Internal State Reset` | `Tracking::track_internal_state_reset` | hook `imagify_after_reset_internal_state`; prop `is_multisite` |
  | `Media Optimized` (MCP) | `McpTracking::track_media_optimized` | only ability `imagify/optimize-media` AND `result.status === 'success'`; adds `initiated_via: 'mcp'`, `execution_time_ms` |
  | `MCP Ability Executed` | `McpTracking::track_ability_executed` | EVERY ability invocation (all 7), regardless of outcome; props `ability_id`, `ability_name`, `execution_time_ms` |
  | `MCP Ability Permission Denied` | `McpTracking::track_permission_denied` | ability `check_permissions()` returns false; props `ability_id`, `ability_name`, `required_capability`, `user_role` |
- **The 7 abilities** (each fires `MCP Ability Executed` when run via MCP/abilities REST; captured
  from source slugs): `imagify/get-account`, `imagify/get-media-status`, `imagify/get-settings`,
  `imagify/get-stats`, `imagify/optimize-media`, `imagify/get-nextgen-coverage`,
  `imagify/update-settings`.
- **Required/default properties on every event** (`BaseTracking::get_default_event_properties`):
  `context` (`wp_plugin` for UI, **`wp_plugin_mcp`** for MCP — overridden in `McpTracking`),
  `license_owner` (**SHA-256 of the Imagify account email**, `hash('sha256', $user->email)`; empty
  string when the user/email is unavailable — confirmed empty in the REST-run capture this explore),
  `user_id`. The library then **auto-injects** (do NOT set these in event props — silently
  overwritten): `domain` (a hashed value, not raw), `wp_version`, `php_version`, `plugin`
  (`imagify <version>`), `brand`, `application`, plus `token`, `time`, `mp_lib: php`.
- **`next_gen_format`** values: `'avif'` (when AVIF present/enabled, highest priority), `'webp'`,
  or `null`. UI path resolves from per-size optimization data; MCP path resolves from the
  `convert_to_avif`/`convert_to_webp` settings.
- **`trigger`** (UI `Media Optimized` only): `'auto'` (item `is_new_upload`), `'bulk'` (a
  `imagify_wp_optimize_running`/`imagify_custom-folders_optimize_running` transient is set), else
  `'manual'`. (`Tracking::resolve_trigger`.)
- **`Media Optimized` thumbnails-only guard**: the UI event is suppressed unless the **`full`**
  size is in `item['sizes_done']` and the full-size data has `success` truthy — i.e. a
  thumbnails-only optimization does NOT emit `Media Optimized`.
- **AJAX toggle**: action **`wp_ajax_imagify_toggle_tracking_optin`**, nonce action
  **`imagify_tracking_optin`** (`check_ajax_referer(..., 'nonce')`), POST field `value` (1/0).
  On enable it also sets the transient **`imagify_analytics_optin_thanks`** (60s) which renders the
  thank-you notice once on the next page load (`Notices::render_thankyou_notice`, read-once then
  `delete_transient`).
- **Mixpanel network destination** (DESTRUCTIVE / external): `wp_remote_post` to
  `https://mixpanel-proxy.group.one/track/?ip=0` (`vendor/.../Tracking.php` HOST const + events
  endpoint). For tests, intercept this via a `pre_http_request` spy — DO NOT send live data.

## How to invoke (grounded from live)
- **Analytics opt-in UI**: GET `http://localhost:10043/wp-admin/options-general.php?page=imagify`.
  The opt-in section renders inside the General Settings form (hook `imagify_settings_after_lossless`),
  so it requires a valid API key (same gate as General Settings; live env satisfies this).
- **Toggle opt-in (AJAX, what the checkbox JS does — captured live)**: POST
  `/wp-admin/admin-ajax.php` form fields `action=imagify_toggle_tracking_optin`,
  `nonce=<data-nonce off the checkbox>`, `value=1|0`. Live results this explore:
  - valid `value=1` → HTTP **200** body `{"success":true}`, option set, thank-you transient set.
  - valid `value=0` → HTTP **200**, option deleted.
  - bad nonce → HTTP **403** body `-1` (`check_ajax_referer` die).
- **MCP / abilities (fires `MCP Ability Executed`)**: run any ability via the abilities REST
  endpoint, e.g. GET `/wp-json/wp-abilities/v1/abilities/imagify/get-stats/run` with `X-WP-Nonce`
  + session cookie (401 anonymously; see `_foundation.md` auth). Captured live: a `get-stats` run
  produced exactly one `MCP Ability Executed` event with `context: wp_plugin_mcp`,
  `ability_id: imagify/get-stats`, `ability_name: "Get Imagify optimization stats"`,
  `execution_time_ms`, and the auto-injected props (`domain`/`wp_version`/`php_version`/`plugin`/
  `brand`/`application`/`token`/`time`/`mp_lib: php`).
- **UI-triggered optimization does NOT fire the MCP event**: the UI path uses `Subscriber`
  (`imagify_after_optimize`), which calls `Tracking` (context `wp_plugin`), never `McpTracking`.
  The MCP events come ONLY from the `imagify_mcp_ability_executed` / `imagify_mcp_permission_denied`
  actions fired in `AbstractAbility` (`check_permissions()` + `do_execute` wrapper).

## Locators (captured live — role-based preferred, then data-testid, then id)
Verified live this explore on `/wp-admin/options-general.php?page=imagify` (login admin/admin;
reuse the `SettingsPage` POM at `Tests/e2e/pages/settings.ts` + `wp-login` fixture). No
`data-testid` on this UI; fall back to id/class. The opt-in checkbox was **unchecked** on a fresh
load (matches "unchecked by default").

- Opt-in section wrapper: `page.locator('.imagify-analytics-optin')` → 1 match.
- Opt-in checkbox (C: unchecked by default / toggle ON / toggle OFF) — PREFERRED role-based:
  `page.getByLabel('Imagify Analytics')` → 1 match.
  - id fallback:   `page.locator('#imagify-analytics-enabled')` (input type=checkbox, value="1").
  - The checkbox carries the AJAX nonce as **`data-nonce`** (read it for the toggle call):
    `page.locator('#imagify-analytics-enabled').getAttribute('data-nonce')` (10-char nonce live).
  - associated label: `page.locator('label[for="imagify-analytics-enabled"]')`. The toggle UI
    (`.imagify-analytics-toggle-ui` span) overlays the input, so click the label or use
    `{ force: true }` on the checkbox (same overlay pattern as the auto-optimize toggle in settings.md).
  - assert state: `await expect(checkbox).not.toBeChecked()` on fresh load.
- Description + "What info" link text: `page.locator('.imagify-analytics-description')` → 1 match,
  text "I agree to share anonymous data with the development team to help improve Imagify. What
  info will we collect?".
- "What info will we collect?" modal trigger (C: What-info modal):
  `page.locator('.imagify-modal-trigger')` → 1 match, text "What info will we collect?".
  (It is a `<button class="imagify-btn-link imagify-modal-trigger" href="#imagify-analytics-info-modal">`.)
- Modal + its contents:
  - `page.locator('#imagify-analytics-info-modal')` → 1 (role="dialog", aria-hidden toggled by JS).
  - data table inside modal: `page.locator('.imagify-analytics-data-table')` → 1 (rows: WordPress
    version, PHP version, Plugin version, Optimization level, Media type, Savings percentage,
    Next-gen format, Trigger, License type).
  - activate-from-modal button (only rendered when opt-in is OFF):
    `page.locator('#imagify-analytics-enable-from-modal')` → 1 (text "Activate Imagify Analytics").
- Thank-you notice (C: thank-you notice after enabling) — captured live after a valid toggle ON +
  reload: `page.locator('.imagify-analytics-thankyou-notice')` (classes
  `notice notice-success is-dismissible`); contains `p > strong` "Thank you!" and the same
  `.imagify-analytics-data-table`. Read-once (transient deleted on render), so it appears on the
  FIRST page load after enabling and not subsequently.
- Save button (regression: saving settings must NOT touch opt-in): `page.locator('#submit')`
  (POM `settings.saveButton`). Saving the General Settings form does not POST the opt-in field
  (the opt-in is a separate AJAX action), so opt-in state is unchanged by a settings Save.
- Fatal-error guard (every case): `page.locator('.wp-die-message, #error-page')` → expect 0
  (POM `settings.expectNoFatalError()`).

## Prerequisites & seeding (per operation)
General: valid API key configured so the opt-in section renders (live env satisfies; see
`_foundation.md`). Each tracking assertion needs (a) a known opt-in state and (b) an outbound
Mixpanel-request spy — because events are sent server-side and never reach the browser network.

- **Mixpanel event spy (REQUIRED to observe any event)** — events are PHP `wp_remote_post`, NOT
  visible in HAR. Install a temporary **mu-plugin** that hooks `pre_http_request`, matches the host
  `mixpanel-proxy.group.one` (or `mixpanel.com`), base64-decodes the `data=` body to capture the
  event name + properties, records them (option/transient), and returns a fake 200 to block the
  real call. This explore verified the exact path: a `get-stats` MCP run produced one
  `MCP Ability Executed` event with all documented props. Expose captured events to the test via a
  small admin-only REST route (e.g. `GET /wp-json/imagify-spy/v1/events`) or read the option in the
  Local site shell. Reset before each case (`DELETE` the spy option). Teardown: delete the mu-plugin.
- **Opt-in ON** (events should fire): POST the AJAX toggle `value=1` with the live `data-nonce`
  (preferred — exercises the real path), or in the Local shell `wp option update imagify_mixpanel_optin 1`.
- **Opt-in OFF** (events must NOT fire): toggle `value=0`, or in the Local shell
  `wp option delete imagify_mixpanel_optin`. Default state has the option absent.
- **`Media Optimized` (UI, trigger=auto)**: opt-in ON, `auto_optimize=1`, upload a fresh supported
  image (REST media helper in `_foundation.md`) so `imagify_after_optimize` runs with the full size.
- **`Media Optimized` (UI, trigger=bulk)**: opt-in ON, run bulk optimization
  (`/wp-admin/upload.php?page=imagify-bulk-optimization`) — the bulk transient makes `resolve_trigger`
  return `'bulk'`.
- **Thumbnails-only suppression**: opt-in ON, optimize only thumbnail sizes (no `full` in
  `sizes_done`) → assert NO `Media Optimized` event captured.
- **`Media Optimized` via MCP**: opt-in ON, run ability `imagify/optimize-media` with a `media_id`
  for an unoptimized attachment via abilities REST (auth+nonce) → expect both `MCP Ability Executed`
  AND `Media Optimized` (with `initiated_via: 'mcp'`).
- **`MCP Ability Executed` for all 7**: opt-in ON, run each ability slug once via abilities REST
  (get-account, get-media-status, get-settings, get-stats, optimize-media, get-nextgen-coverage,
  update-settings). update-settings/optimize-media mutate state — snapshot + teardown (below).
- **`MCP Ability Permission Denied`**: opt-in ON, seed a non-admin user (subscriber) per
  `_foundation.md`, log in as them, run an ability whose `check_permissions()` fails → expect the
  permission-denied event with `user_role` of that user. (Note opt-in is a per-site option, so it
  is ON regardless of who is logged in; the denied event still fires because `can_track()` only
  reads the option.)
- **`Internal State Reset`**: opt-in ON, trigger `imagify_after_reset_internal_state` (see
  `reset-internal-state.spec.ts` / the reset action) → expect `Internal State Reset` with
  `is_multisite` boolean.
- **`Settings Saved`**: opt-in ON, change any setting and Save (or `update-settings` ability) →
  expect `Settings Saved` with the boolean-mapped properties (`auto_optimize_on_upload`, `lossless`,
  `backup_original`, etc.).
- **Saving does not touch opt-in (regression)**: set a known opt-in state, Save the General Settings
  form, re-read `imagify_mixpanel_optin` → unchanged.
- **Invalid nonce rejected / non-admin blocked (Analytics Toggle)**: POST the AJAX toggle with a
  bogus nonce → 403 `-1`; as a non-admin → 403 "Unauthorized." (`ajax_toggle_optin` cap check).

## Verification criteria — "success" means (observable)
Opt-in gating is the spine: with opt-in OFF, the spy captures ZERO Imagify events for any action;
with opt-in ON, the specific event below is captured with the named properties. Never assert merely
"page loaded".

- **Opt-in unchecked by default**: on a fresh load (option absent), `#imagify-analytics-enabled`
  is **not checked** (`expect(...).not.toBeChecked()`).
- **Toggle ON**: AJAX `value=1` → HTTP 200 `{"success":true}`; on reload the checkbox is checked and
  option `imagify_mixpanel_optin` is truthy.
- **Thank-you notice**: after a fresh enable, the next admin page load shows
  `.imagify-analytics-thankyou-notice` with "Thank you!" and the data table; it does NOT reappear on
  the subsequent load (transient consumed).
- **Toggling OFF removes opt-in**: AJAX `value=0` → 200; option row is **deleted** (`get_option`
  returns false/default), checkbox unchecked on reload, and subsequent actions capture no events.
- **"What info" modal**: clicking `.imagify-modal-trigger` reveals `#imagify-analytics-info-modal`
  containing `.imagify-analytics-data-table` with the nine listed metric rows.
- **Non-admin blocked**: AJAX toggle as a subscriber/editor → HTTP 403, opt-in option unchanged.
- **Invalid nonce rejected**: AJAX toggle with a wrong nonce → HTTP 403 body `-1`, option unchanged.
- **Saving settings does not touch opt-in**: opt-in option value is identical before and after a
  General Settings Save (no event of type opt-in-change; opt-in is AJAX-only).
- **`Media Optimized` (UI)**: with opt-in ON and a full-size successful optimization, the spy
  captures exactly one `Media Optimized` event whose props include `context: wp_plugin`,
  `optimization_level` (int), `media_type` (mime), `original_size`/`optimized_size` (int),
  `savings_percent` (number), `next_gen_format` (`avif|webp|null`), and `trigger`
  (`auto` for a new upload / `bulk` during bulk). `license_owner` is a 64-hex SHA-256 of the account
  email (or empty string if unavailable).
- **`next_gen_format` correctness**: equals `avif` when AVIF was generated/enabled, else `webp` when
  only WebP, else `null` — matching the site's conversion settings / per-size data.
- **Thumbnails-only**: with opt-in ON, optimizing only non-full sizes captures NO `Media Optimized`
  event (the `'full' in sizes_done` + full-success guard fails).
- **`Media Optimized` via MCP**: running `imagify/optimize-media` (success) captures a
  `Media Optimized` event with `context: wp_plugin_mcp`, `initiated_via: 'mcp'`, and
  `execution_time_ms` present — AND a separate `MCP Ability Executed` event for the same call.
- **`MCP Ability Executed` for all 7 abilities**: each of the seven ability runs captures exactly
  one `MCP Ability Executed` event with `context: wp_plugin_mcp`, the correct `ability_id`
  (matching the slug), a non-empty `ability_name`, and a numeric `execution_time_ms`. (Verified live
  for `imagify/get-stats`.)
- **`MCP Ability Permission Denied`**: a denied ability run captures one
  `MCP Ability Permission Denied` event with the `ability_id`, `ability_name`,
  `required_capability` ("manage"), and the denied user's `user_role`.
- **Context field MCP vs UI**: MCP-path events carry `context: wp_plugin_mcp`; UI-path events carry
  `context: wp_plugin`. A UI-triggered optimization captures NO `MCP Ability Executed` event.
- **`Internal State Reset`**: the reset action captures one `Internal State Reset` event with an
  `is_multisite` boolean property.
- **`license_owner` is SHA-256**: when an account email is available, `license_owner` is a 64-char
  lowercase hex string equal to `sha256(email)` — never the raw email (privacy assertion).
- **Strauss prefixing (regression)**: the Mixpanel library is namespaced under
  `Imagify\Dependencies\WPMedia\Mixpanel` (and aliases like
  `Imagify_WPMedia_ConsumerStrategies_AbstractConsumer`); no unprefixed top-level `Mixpanel`/
  `WPMedia` class collides. Observable by class_exists checks or that tracking works without fatals.
- **Opt-out = total silence (master assertion)**: with opt-in OFF, NONE of the above actions
  (optimize, restore, settings save, reset, any of the 7 MCP abilities, permission denied) produce
  any captured event — `can_track()` short-circuits every tracker before `track_direct`.

## Teardown (LIFO)
Restore in reverse order of seeding; each case undoes only what it changed.
1. Restore opt-in to its pre-test state: it is OFF by default (option absent), so after a test that
   enabled it, disable via AJAX `value=0` or `wp option delete imagify_mixpanel_optin`. (This explore
   left it OFF.)
2. Delete any attachment seeded for `Media Optimized`/`Media Restored` and its Imagify meta
   (`_imagify_data`, `_imagify_status`) — see `_foundation.md` teardown helpers.
3. Restore any settings snapshot taken for `Settings Saved` / `update-settings` cases
   (`POST update-settings <snapshot>` or `wp option update imagify_settings '<json>' --format=json`).
4. Delete any non-admin test user seeded for the permission-denied case
   (`wp user delete <login> --yes --reassign=1`).
5. Remove the Mixpanel-spy mu-plugin (`wp-content/mu-plugins/<spy>.php`) and delete its capture
   option/transient. (NOTE: this explore left an orphaned `imagify_spy_events` option in the
   localhost:10043 DB after removing the spy mu-plugin; it is inert — no code reads it — but can be
   cleared with `wp option delete imagify_spy_events` in the Local site shell.)
6. Clear any leftover `imagify_analytics_optin_thanks` transient if a thank-you case was interrupted
   before the notice rendered (`wp transient delete imagify_analytics_optin_thanks`); normally it is
   read-once and self-clears.
