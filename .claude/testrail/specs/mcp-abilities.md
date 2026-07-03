---
testrail_sections: [8724]
feature: "MCP Abilities"
source_files: [classes/Abilities/*.php, classes/MCP/*.php, config/providers.php, inc/classes/class-imagify-options.php]
derived_sha: 542b43d4
last_explored: 2026-06-30
---

## Overview
The Imagify MCP module exposes 7 abilities through the WordPress **Abilities API** (core, WP >= 6.9)
under the `imagify` category (TestRail section 8724 = MCP Abilities, parent 7685). Each ability is a
class in `classes/Abilities/` registered on the `wp_abilities_api_init` action by
`Imagify\MCP\AbilitiesSubscriber` (wired in `config/providers.php` via `Imagify\MCP\ServiceProvider`).
`Imagify\MCP\ConfigSubscriber` names the MCP server `imagify-plugin`. All 7 abilities gate on the
Imagify `manage` capability via `imagify_get_context('wp')->current_user_can('manage')` (the
`imagify_capacity` filter — NOT a bare `current_user_can()`), and all carry `meta.mcp.public = true`
and `meta.show_in_rest = true`, so they are reachable over the Abilities REST namespace
`wp-abilities/v1`. Registration no-ops silently when `wp_register_ability` is absent (WP < 6.9).

SOURCE-OF-TRUTH NOTE (read before grounding drift): the live behaviour below was captured against
the **nginx** site `$E2E_URL`, whose active plugin checkout is
`/Users/gaelrobin/Local Sites/e2eimagifynginx/.../imagify-plugin` on branch `feat/mixpanel`
(SHA 461e5839, plugin v2.2.9 header, but carrying the **2.3.0 abilities** with the
`AbstractAbility` base + `get_id()` template). This worktree (`imagify-e2e-worktree`, SHA 542b43d4)
carries an OLDER variant of the same classes (`implements AbilitiesInterface`, no `get_id()`); the
slugs, schemas, input wrapper, and responses are identical at the REST surface, so the grounding
holds. `derived_sha`/`source_files` track THIS worktree per `_foundation.md` convention.

## Ground truth
The 7 abilities (slugs captured live from the discovery endpoint — all kebab-case, no underscores
in the local part, confirming C26383):

| Slug | Label | readonly | destructive | idempotent | input | run method |
|------|-------|----------|-------------|------------|-------|-----------|
| `imagify/get-account`          | Get Imagify account status        | true  | false | true  | none                       | GET  |
| `imagify/get-settings`         | Get Imagify settings              | true  | false | true  | none                       | GET  |
| `imagify/get-stats`            | Get Imagify optimization stats    | true  | false | true  | none                       | GET  |
| `imagify/get-nextgen-coverage` | Get next-gen coverage             | true  | false | true  | none                       | GET  |
| `imagify/get-media-status`     | Get Media Status                  | true  | false | true  | `media_id` (int, required) | GET  |
| `imagify/update-settings`      | Update Imagify settings           | false | false | true  | partial settings object    | POST |
| `imagify/optimize-media`       | Optimize media                    | false | **true** | false | `media_id` (int, required), `optimization_level` (0-2 opt) | POST |

- Discovery (`GET wp-abilities/v1/abilities`) returns ALL public abilities including 2 core ones
  (`core/get-site-info`, `core/get-environment-info`); the 7 `imagify/*` slugs are a subset
  (C26359 asserts the 7 imagify slugs are present, not a total count of 9).
- `imagify/optimize-media` is the ONLY destructive ability (`meta.annotations.destructive = true`,
  captured live; C26384). All read abilities are `destructive:false, readonly:true, idempotent:true`.
- **Read-only abilities require GET** at the REST run endpoint: a POST returns HTTP 405
  `rest_ability_invalid_method` "Read-only abilities require GET method." (captured live on
  get-settings; same applies to all 5 read abilities). Write abilities (update-settings,
  optimize-media) use POST.
- `imagify/get-settings` strips `version` AND `api_key` from its output (GetSettings.php:117-118).
  Live keys returned (17, captured live): `admin_bar_menu, auto_optimize, backup, cdn_url,
  convert_to_avif, convert_to_webp, disallowed-sizes, display_nextgen, display_nextgen_method,
  display_webp, display_webp_method, lossless, optimization_format, optimization_level,
  partner_links, resize_larger, resize_larger_w`. `api_key`/`version` absent (C26362).
- `imagify/update-settings` output shape: `{ "updated": [<changed keys>], "settings": {<full
  post-update settings minus api_key+version> } }` (UpdateSettings.php:340-343). `version` and
  `api_key` are stripped from the returned `settings` too.
- update-settings rejection rules (captured live):
  - unknown key OR `version` key → HTTP **500** body `{"code":"imagify_unknown_setting","message":
    "\"<key>\" is not a valid Imagify setting key.","data":null}` (C26368). The check runs in the
    foreach BEFORE `$options->set()`, so a `version`/unknown key mixed with a valid key causes a
    FULL rejection with NO partial write (C26369 — verified live: a `{lossless:1, version:9}` call
    returned the 500 and a follow-up get-settings showed `lossless` still 0).
  - `optimization_level` out of [0,2] → HTTP **400** `ability_invalid_input`
    "input[optimization_level] must be between 0 (inclusive) and 2 (inclusive)" — this is the
    **input_schema** (`minimum:0, maximum:2`) rejecting BEFORE the PHP `validate_value()` even runs
    (C26371). Distinguish: schema-level enum/range violations are 400 `ability_invalid_input`;
    semantic key rejection is 500 `imagify_unknown_setting`.
  - `api_key` with the `IMAGIFY_API_KEY` constant defined → `imagify_api_key_immutable`
    (UpdateSettings.php:311-317). NOT reproducible live (constant undefined in this env) — C26370.
  - empty input `{}` → HTTP 200 `{"updated":[],"settings":{...}}` (no error; C26372). Captured live.
- update-settings input enums (input_schema, UpdateSettings.php:64-155): `optimization_level`
  int 0-2; `optimization_format` enum off|webp|avif; `display_nextgen_method`/`display_webp_method`
  enum picture|rewrite; the rest of the 0/1 toggles are int enum [0,1].
- `imagify/get-media-status` maps internal status → public: `success`|`already_optimized` →
  `"success"`; `error` → `"error"`; everything else → `"unoptimized"` (GetMediaStatus.php:252-261).
  It does NOT type-check the post — a non-attachment post id returns `"unoptimized"` (does not
  crash; C26377). Error responses still return HTTP 200 with a `status:"error"` body:
  - `media_id` <= 0 → `{status:"error", error_message:"Invalid or missing media_id", ...}` (C26376).
  - non-existent id → `{status:"error", error_message:"Media not found.", ...}` (C26375 — error,
    NOT "unoptimized"). All responses carry the full 7 fields (status, optimization_level,
    original_size, optimized_size, webp_available, avif_available, error_message).
- `imagify/optimize-media` validates in order (OptimizeMedia.php:160-175): `media_id<=0` →
  "Invalid or missing media_id."; missing post → "Invalid media."; non-attachment post →
  "The provided ID is not a media attachment." ALL error responses carry the complete **5-field**
  schema `{status, original_size, optimized_size, savings_percent, error_message}` with status
  `"error"` and the four data fields `null` (C26389, captured live for all 3 error paths). Zero
  media_id is rejected before any DB/post-type lookup (C26388); non-existent before the post-type
  check (C26387); non-attachment fails the post-type check (C26386). HTTP 200 in all cases.
- `imagify/get-account`: when the API key is invalid/empty returns the zeroed shape
  `{plan_label:"", quota:0, consumed_current_month_quota:0, extra_quota:0, extra_quota_consumed:0,
  next_date_update:"", is_api_key_valid:false}` (GetAccount.php:131-141) — all 7 fields present
  with schema-correct types (C26390, captured live: this env has NO valid key configured).
- `imagify/get-stats`: when nothing is optimized, `savings_percent` is `0` in JSON (PHP `0.0`
  float cast; do not assert on JSON-vs-PHP float rendering — assert the value equals 0 and the
  field is present; C26391). Live `wp` + `custom-folders` both zeroed in this empty env.
- `imagify/get-nextgen-coverage`: `{missing_nextgen_count:<int>, nextgen_format:<string>}` —
  `missing_nextgen_count` is always cast `(int)` (GetNextgenCoverage.php:129), never boolean false
  (C26381); `nextgen_format` mirrors the `optimization_format` setting (live value `"webp"`; C26382).

## How to invoke (grounded from live)
All calls require an authenticated WP session + `X-WP-Nonce` header (anonymous discovery and run
both return HTTP **401**, confirming C26360). Acquire cookie + nonce per `_foundation.md`.
Base for this feature: `$E2E_URL` (the nginx env from `.ai/settings.local.json` — never a
hardcoded port; the original capture ran against a Local nginx site).

- **Discovery (C26359 / C26360):**
  `GET /wp-json/wp-abilities/v1/abilities` (authed) → JSON array; filter `name` starting `imagify/`
  and assert all 7 slugs present. Anonymous → 401.
- **Per-ability detail / annotations (C26384):**
  `GET /wp-json/wp-abilities/v1/abilities/imagify/optimize-media` → `meta.annotations.destructive`.
  The run URL is the ability's `_links["wp:action-run"][0].href`
  (`…/abilities/imagify/<slug>/run`).
- **Run a READ ability (get-account, get-settings, get-stats, get-nextgen-coverage):**
  `GET …/abilities/imagify/<slug>/run`. POST → 405.
- **Run get-media-status (read, takes input):** GET with the input as a **bracketed query param**
  (the run arg is named `input`, an object): `…/imagify/get-media-status/run?input[media_id]=<id>`
  (URL-encode the brackets: `input%5Bmedia_id%5D=<id>`).
- **Run a WRITE ability (update-settings, optimize-media):** `POST …/imagify/<slug>/run` with
  `Content-Type: application/json` and body wrapping args under an **`input`** key:
  - update-settings: `{"input":{"lossless":1}}`
  - optimize-media:  `{"input":{"media_id":<id>,"optimization_level":2}}`
  WARNING: the run endpoint takes `{"name":..., "input":...}`; args MUST be under `input`. A raw
  top-level args body returns `ability_invalid_input` "input is not of type object" (400).
- **MCP adapter:** the MCP server is named `imagify-plugin` (ConfigSubscriber). The adapter's
  canonical server route/tools are left at defaults (ConfigSubscriber only overrides
  `server_name`/`server_description`); cases here exercise the Abilities REST surface, which is the
  grounded, observable entry point.

## Locators (captured live — role-based preferred, then data-testid, then id)
This feature is REST/MCP-driven; most cases assert on JSON responses, not the DOM. The only UI leg
is C26364 (update-settings persists to the WP Admin settings UI). Settings page locators captured
live on `/wp-admin/options-general.php?page=imagify` (no data-testid on this page; reuse the
`SettingsPage` POM at `Tests/e2e/pages/settings.ts` + `loginAsAdmin` fixture, same style as
`account-connection.spec.ts`):
- Settings nav: `page.goto('/wp-admin/options-general.php?page=imagify')`.
- Lossless toggle (used by the C26364 UI-persistence check) — id (no role label wrapper observed):
  `page.locator('#imagify_lossless')` (input type=checkbox), name
  `[name="imagify_settings[lossless]"]`, associated `label[for="imagify_lossless"]` text
  "Lossless compression". The label overlays the input; read its `checked` state, do not click to
  verify.
- API key field: `page.locator('#api_key')` (name `imagify_settings[api_key]`).
- Save button: `page.locator('#submit')`; "Settings saved." confirmation:
  `page.locator('#setting-error-settings_updated')`.
- Fatal-error guard (every case that loads a page): `page.locator('.wp-die-message, #error-page')`
  → expect 0.

## Prerequisites & seeding (per operation)
- **Plugin MUST be active** (HARD PREREQUISITE — discovered live): the abilities only register when
  `imagify-plugin/imagify` is active. On first explore this plugin was **inactive** in the capture env and
  ZERO imagify abilities appeared in discovery. Activate before any case:
  `POST /wp-json/wp/v2/plugins/imagify-plugin/imagify {"status":"active"}` (auth + nonce), or
  `wp plugin activate imagify-plugin` inside the Local site shell. Verify via
  `GET /wp-json/wp/v2/plugins/imagify-plugin/imagify` → `status:"active"`.
- **WP >= 6.9** (live env is WP 7.0; Abilities API in core `wp-includes/abilities-api.php`). For
  C26366 (no-op on WP < 6.9): cannot be reproduced on this env (7.0); verify by code/unit — when
  `wp_register_ability` is undefined, `AbilitiesSubscriber::register_abilities()` returns early and
  discovery contains no `imagify/*` slugs. Document as a version-guarded prerequisite.
- **Auth session + nonce** (all cases): cookie jar + `X-WP-Nonce` (see `_foundation.md`).
  Anonymous = 401 (C26360 expects this).
- **Subscriber user** (C26365 — subscriber cannot execute any ability): seed
  `wp user create mcp_sub mcp_sub@example.com --role=subscriber --user_pass=pass` inside the Local
  shell; log in as that user, acquire a fresh nonce, and call each `…/run` endpoint → expect a
  permission failure (the `manage` gate denies; `has_permission()` returns false and
  `imagify_mcp_permission_denied` fires).
- **imagify_capacity denial** (C26378): hook the `imagify_capacity` filter to return a cap the
  admin lacks (mu-plugin or `wp eval`), then call any `…/run` → all denied. Restore the filter after.
- **Multisite permission cases** (C26379 network non-admin denied / C26380 network admin granted):
  require a WordPress **multisite** with the plugin network-activated. NOT available in this
  single-site env — BLOCKED (same constraint as settings.md C174). Document as a multisite-only
  prerequisite; skip on this env.
- **get-account valid-key path** (C26361 expects "expected fields"): live env has NO valid API key
  (`is_api_key_valid:false`), so the zeroed shape is returned. C26361 asserts the FIELD SET (all 7
  keys present, types correct) which holds for both valid and invalid key — the invalid-key shape
  satisfies the schema. To exercise the populated path, configure a valid key first
  (`wp option patch update imagify_settings api_key "<valid key>"` in the Local shell) — optional.
- **optimize-media / get-media-status success cases** (C26363, C26374, C26385): require (a) a real
  attachment AND (b) a VALID API key (optimization calls the Imagify API). The media library on
  the capture env was EMPTY (live check returned 0 attachments). Seed an attachment via REST
  media upload (helper in `_foundation.md`, against `$E2E_URL`), and configure a valid key. Without a valid
  key, optimize-media returns a `status:"error"` (API not reachable), which still satisfies the
  5-field-schema cases (C26389) but NOT the success cases. Error/validation cases (C26375-C26377,
  C26386-C26389) need NO attachment seeding and NO key — they were all captured live as-is.
- update-settings single-value cases (C26367, C26372): no seeding needed; snapshot the full
  settings via `GET …/get-settings/run` BEFORE mutating (for teardown).

## Verification criteria — "success" means (observable)
- C26359 (discovery, 7 slugs): authed `GET …/abilities` returns a JSON array whose `name` values
  include exactly the 7 `imagify/*` slugs in the Ground-truth table (presence, not array length —
  core slugs coexist). NOT a tautology: assert each of the 7 slug strings is present.
- C26360 (unauthenticated rejected): anonymous `GET …/abilities` AND anonymous `…/<slug>/run`
  both return HTTP **401**.
- C26361 (get-account fields): authed `GET …/get-account/run` HTTP 200 with a JSON object
  containing all 7 keys (`plan_label, quota, consumed_current_month_quota, extra_quota,
  extra_quota_consumed, next_date_update, is_api_key_valid`) with schema-correct types.
- C26362 (get-settings no api_key/version): authed `GET …/get-settings/run` HTTP 200; response
  object has NEITHER `api_key` NOR `version` key, and DOES contain the 17 user-facing keys.
- C26363 / C26385 (optimize a valid attachment): with a valid key + seeded attachment, `POST
  …/optimize-media/run {"input":{"media_id":<id>}}` returns HTTP 200 `status:"success"` and (after
  the queued job) the attachment gains `_imagify_status`/`_imagify_data` meta; the response
  `original_size` is a positive int. (optimized_size may be 0/null until the background job
  completes — assert status success + the meta, not an immediate size.)
- C26364 (update-settings persists to Admin UI): `POST …/update-settings/run
  {"input":{"lossless":1}}` returns HTTP 200 `updated` containing `"lossless"`; THEN loading
  `/wp-admin/options-general.php?page=imagify` shows `#imagify_lossless` **checked** (the option
  written by the ability is reflected in the rendered settings form). Observable on both the REST
  response and the rendered checkbox state.
- C26365 (subscriber denied): logged in as a subscriber, every `…/run` call is denied
  (permission failure, not a successful ability result).
- C26366 (WP < 6.9 no-op): on WP < 6.9, discovery contains NO `imagify/*` slugs (abilities
  unregistered). Cannot be asserted on WP 7.0 — verify via the early-return guard in
  `AbilitiesSubscriber`/each ability's `register()`. BLOCKED on this env.
- C26367 (valid single update): `POST {"input":{"lossless":1}}` → HTTP 200, `updated == ["lossless"]`,
  and `settings.lossless == 1`. (Captured live exactly.)
- C26368 (`version` rejected): `POST {"input":{"version":"9"}}` → HTTP **500**,
  `code == "imagify_unknown_setting"`, message names `version`.
- C26369 (`version` + valid → full rejection, no partial write): `POST
  {"input":{"lossless":1,"version":"9"}}` → HTTP 500 `imagify_unknown_setting`; AND a subsequent
  `GET …/get-settings/run` shows `lossless` UNCHANGED from its pre-call value (no partial write).
- C26370 (api_key immutable with constant): with `IMAGIFY_API_KEY` defined,
  `POST {"input":{"api_key":"x"}}` → `code == "imagify_api_key_immutable"`. BLOCKED on this env
  (constant undefined) — verify by code or by defining the constant in wp-config first.
- C26371 (invalid optimization_level): `POST {"input":{"optimization_level":5}}` → HTTP **400**,
  `code == "ability_invalid_input"`, message references the 0..2 range (schema rejection, not the
  PHP validator).
- C26372 (empty input): `POST {"input":{}}` → HTTP 200, `updated == []`, no error, `settings`
  present and unchanged.
- C26373 (unoptimized status): `GET …/get-media-status/run?input[media_id]=<unoptimized id>` →
  HTTP 200 `status:"unoptimized"`, `optimization_level:null`. (Requires a real, un-optimized
  attachment seeded.)
- C26374 (already-optimized success): for an optimized attachment, `status:"success"`,
  `optimization_level` an int 0..2, `original_size`/`optimized_size` positive ints. (Requires a
  valid key + an optimized attachment.)
- C26375 (non-existent → error not unoptimized): `?input[media_id]=999999` → HTTP 200
  `status:"error"`, `error_message:"Media not found."` (NOT "unoptimized"). Captured live.
- C26376 (zero/negative media_id → error): `?input[media_id]=0` (and `-3`) → HTTP 200
  `status:"error"`, `error_message:"Invalid or missing media_id"`. Captured live.
- C26377 (non-attachment post does not crash): `?input[media_id]=1` (a regular post) → HTTP 200,
  no fatal, `status:"unoptimized"` (get-media-status does not type-check). Captured live.
- C26378 (imagify_capacity denial blocks all): with the filter forced to deny, every `…/run`
  call is denied. (Seed the filter; see Prerequisites.)
- C26379 / C26380 (multisite non-admin denied / network admin granted): BLOCKED — needs multisite.
- C26381 (missing_nextgen_count is int): `GET …/get-nextgen-coverage/run` → `missing_nextgen_count`
  is an integer (e.g. `0`), JSON type number, NOT boolean `false`. Captured live (`0`).
- C26382 (nextgen_format reflects setting): the response `nextgen_format` equals the current
  `optimization_format` option. Live: setting `optimization_format == "webp"` and coverage
  `nextgen_format == "webp"` — match. To strengthen, set `optimization_format` to `avif` via
  update-settings, re-call, expect `"avif"`, then restore.
- C26383 (slugs kebab-case, no underscores): each of the 7 `imagify/*` slugs' local part matches
  `^[a-z]+(-[a-z]+)*$` with no `_`. Captured live: all 7 pass.
- C26384 (optimize-media destructive=true): `GET …/abilities/imagify/optimize-media` →
  `meta.annotations.destructive === true` (and `readonly:false, idempotent:false`). Captured live.
- C26385 — see C26363.
- C26386 (non-attachment → error, no optimization): `POST …/optimize-media/run
  {"input":{"media_id":1}}` → HTTP 200 `status:"error"`,
  `error_message:"The provided ID is not a media attachment."`, all 4 data fields null; no
  optimization meta written. Captured live.
- C26387 (non-existent → error before post-type check): `{"input":{"media_id":999999}}` → HTTP 200
  `status:"error"`, `error_message:"Invalid media."`. Captured live.
- C26388 (zero media_id rejected pre-DB): `{"input":{"media_id":0}}` → HTTP 200 `status:"error"`,
  `error_message:"Invalid or missing media_id."`. Captured live.
- C26389 (all error responses carry the 5-field schema): every optimize-media error response
  contains exactly `status, original_size, optimized_size, savings_percent, error_message` with
  `status:"error"` and the other four `null`. Verified live across the 3 error paths.
- C26390 (get-account types when key invalid): with an invalid/empty key, `GET …/get-account/run`
  returns all 7 fields with correct types — `is_api_key_valid:false` (boolean), `plan_label:""`
  (string), numeric quota fields `0` (number), `next_date_update:""` (string). Captured live.
- C26391 (get-stats savings_percent 0.0 when none optimized): `GET …/get-stats/run` →
  `wp.savings_percent == 0` and `custom-folders.savings_percent == 0`, field present and numeric
  (PHP float 0.0; do not over-assert JSON float formatting). Captured live (both 0).

## Teardown (LIFO)
Restore in reverse order of seeding. Each case is independent; undo only what it changed.
1. **Restore any mutated setting** (C26364, C26367, C26369 if it had partially written — it does
   not, but snapshot anyway): snapshot via `GET …/get-settings/run` BEFORE the case, then restore
   the changed key via `POST …/update-settings/run` with the original value (e.g.
   `{"input":{"lossless":0}}` — live pre-test value was 0), or
   `wp option update imagify_settings '<json snapshot>' --format=json` in the Local shell.
   (This explore set then reverted `lossless` to 0; verified clean.)
2. **Restore `optimization_format`** if changed for C26382 (back to `"webp"`).
3. **Delete any seeded attachment** (C26363/C26373/C26374/C26385) and its Imagify meta:
   `wp post meta delete <id> _imagify_data` ; `wp post meta delete <id> _imagify_status`, or
   `DELETE /wp-json/wp/v2/media/<id>?force=true` (auth + nonce).
4. **Remove the imagify_capacity denial filter** (C26378) — drop the mu-plugin / undo the
   `wp eval` hook so the admin regains `manage`.
5. **Delete the subscriber test user** (C26365): `wp user delete mcp_sub --yes --reassign=1`.
6. **Remove a valid API key** if one was configured only for the success cases:
   `wp option patch update imagify_settings api_key ""` (Local shell).
7. **Plugin activation:** the explore left `imagify-plugin` ACTIVE (the run agent requires it
   active for every case). If the run protocol requires restoring the pre-run inactive state,
   `POST /wp-json/wp/v2/plugins/imagify-plugin/imagify {"status":"inactive"}` LAST — but note this
   disables every ability, so do it only after all MCP cases complete.
8. C26366 / C26370 / C26379 / C26380: n/a in this env (not executed — version/multisite/constant
   prerequisites unmet).
