---
derived_sha: 4bc0e342
source_files: [imagify.php, inc/classes/class-imagify-options.php, classes/MCP/AbilitiesSubscriber.php, classes/Tools/*.php]
last_explored: 2026-06-30
---

# Foundation — environment, prerequisites, seeding (Imagify local QA)

Non-DOM, low-volatility grounding shared by every feature spec. The run agent loads this
file plus the per-feature spec before executing any case. Locators captured live this explore
are role/id-based; verify against the page if the build moves.

## Environment

URLs and credentials are read from `.ai/settings.local.json` (gitignored). Two environments:

| Key | URL | Server | Use when |
|-----|-----|--------|----------|
| `nginx`  | http://localhost:10043 | Nginx  | default — all cases unless `server: apache` |
| `apache` | http://localhost:10048 | Apache | cases involving `.htaccess`, rewrite rules, `$is_apache` paths |

- WP admin:  `$E2E_URL/wp-admin/`
- Login:     admin / admin via /wp-login.php
             role-based: getByLabel("Username or Email Address"), getByLabel("Password", { exact: true })
             submit:     getByRole("button", { name: "Log In" })
- WP version requirement: >= 6.9 (the Abilities API is a no-op below this; abilities register
  only when `wp_register_ability` exists).
- ACTIVE plugin (live, captured this explore): `wp-content/plugins/imagify-plugin` on branch
  `develop` (SHA 53340d05). Live plugin reports version **2.3.0-alpha1** on the settings page.
  NOTE: a second checkout `imagify-plugin/` (this worktree is `imagify-e2e-worktree/`) is the
  one WordPress loads — its `classes/Abilities/*` is byte-identical to this worktree's, so the
  grounding below holds for both. The spec files + agent infra live only in this worktree, so
  `derived_sha`/`source_files` track this worktree.

## Prerequisites
- Imagify API key (CORRECTED — confirmed live this explore):
  - Stored in the WP option **`imagify_settings`**, key **`api_key`** (NOT `settings.local.json`,
    which does not exist in this env). The settings field is `name="imagify_settings[api_key]"
    id="api_key"`.
  - The PHP constant **`IMAGIFY_API_KEY`** is an OPTIONAL override: when defined+truthy it wins
    over the option, locks the settings field, and makes `update-settings api_key` return the
    error `imagify_api_key_immutable`. It is NOT defined in this env's wp-config.php.
    (Source: inc/classes/class-imagify-options.php:72-116; classes/Abilities/UpdateSettings.php:278-282.)
  - Live env state: a VALID key is configured (option-based, field editable). `get-account` live
    returns `is_api_key_valid: true`, plan "Growth", quota 500.
- Settings page:    /wp-admin/options-general.php?page=imagify
- Capability gate:  the `imagify_capacity` filter, via `imagify_get_context('wp')->current_user_can('manage')`
                    — NOT a direct `current_user_can()` call.

## Test users (for permission cases)
| Role          | Login          | imagify_capacity | Seed                         |
|---------------|----------------|------------------|------------------------------|
| administrator | admin / admin  | full             | exists by default            |
| editor        | <seed>         | limited          | see Seeding helpers          |
| subscriber    | <seed>         | denied           | see Seeding helpers          |

## Authentication for live API/MCP/REST calls (captured this explore)
The Abilities REST + MCP endpoints require an authenticated WP session + a REST nonce. Both
return **HTTP 401** anonymously. Acquire a session by curl (Bash) or the browser:

```bash
# 1. Log in (cookie jar)
curl -s -c cookies.txt -b cookies.txt \
  --data-urlencode "log=admin" --data-urlencode "pwd=admin" \
  --data-urlencode "wp-submit=Log In" --data-urlencode "testcookie=1" \
  --data-urlencode "redirect_to=http://localhost:10038/wp-admin/" \
  http://localhost:10038/wp-login.php -o /dev/null
# 2. REST nonce
NONCE=$(curl -s -b cookies.txt "http://localhost:10038/wp-admin/admin-ajax.php?action=rest-nonce")
# 3. Use header: -H "X-WP-Nonce: $NONCE" -b cookies.txt
```

## Seeding helpers (WP-CLI / REST — run via Bash, NOT the UI)
Deterministic state setup. NOTE: the host shell `wp` CLI is NOT wired to this Local site's DB
(socket mismatch); seed via the authenticated REST/abilities API or `wp` inside the Local shell.
Each helper that mutates state has a matching teardown (see "Teardown helpers").

```bash
# --- Set / clear API key (option-based; only when IMAGIFY_API_KEY constant is NOT defined) ---
#   via the update-settings ability (MCP execute path — see mcp-abilities.md), or:
wp option patch update imagify_settings api_key "<key>"    # inside Local site shell
wp option patch update imagify_settings api_key ""

# --- Toggle an arbitrary setting (option-based) ---
wp option patch update imagify_settings <key> <value>
#   live setting keys: optimization_level, lossless, auto_optimize, backup, resize_larger,
#   resize_larger_w, display_nextgen, display_nextgen_method, display_webp, display_webp_method,
#   cdn_url, disallowed-sizes, admin_bar_menu, partner_links, convert_to_avif, convert_to_webp,
#   optimization_format  (api_key + version are managed separately; version is read-only)

# --- An attachment for media-status / optimize-media cases ---
#   Live env already has attachments: id 6,7,8 (8 = "test_huge_image", jpeg, already optimized
#   level 2). To seed a fresh one via REST media upload:
curl -s -b cookies.txt -H "X-WP-Nonce: $NONCE" \
  -H "Content-Disposition: attachment; filename=seed.jpg" -H "Content-Type: image/jpeg" \
  --data-binary @<local.jpg> "http://localhost:10038/wp-json/wp/v2/media" \
  | python3 -c "import sys,json;print(json.load(sys.stdin)['id'])"   # -> new attachment ID

# --- Create a non-admin test user (permission cases) ---
wp user create <login> <email> --role=<role>
```

## Teardown helpers (LIFO undo)
```bash
# --- Restore an attachment to its pre-optimization state ---
#   Imagify writes _imagify_data / _imagify_status post meta and a backup file.
#   Undo via the media UI "Restore Original", or delete the meta + restore backup:
wp post meta delete <id> _imagify_data
wp post meta delete <id> _imagify_status
#   (If a fresh attachment was seeded for the case, delete it entirely:)
curl -s -b cookies.txt -H "X-WP-Nonce: $NONCE" -X DELETE \
  "http://localhost:10038/wp-json/wp/v2/media/<id>?force=true" -o /dev/null

# --- Re-apply a settings snapshot taken before a mutation ---
#   Snapshot BEFORE: GET imagify/get-settings (captures all keys). Restore AFTER via
#   update-settings with the snapshot, or:
wp option update imagify_settings '<json snapshot>' --format=json

# --- Delete a seeded test user ---
wp user delete <login> --yes --reassign=1
```

## Drift detection inputs
`source_files` (above) are the globs whose latest commit is compared against `derived_sha`.
`/testrail-setup --check` reports this spec stale if any source file changed after the stamp.
