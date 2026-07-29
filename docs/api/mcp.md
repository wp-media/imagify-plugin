# MCP Module — Model Context Protocol Foundation

## Overview

The `Imagify\MCP` module integrates Imagify with the WordPress MCP (Model Context Protocol) adapter (`wordpress/mcp-adapter`). It exposes an MCP server endpoint that AI agents can use to discover and invoke Imagify abilities.

The adapter's three built-in tools (`discover-abilities`, `get-ability-info`, `execute-ability`) are always present. Concrete Imagify abilities are defined under `classes/Abilities/`.

## Requirements

- PHP >= 7.4
- WordPress >= 6.9 (Abilities API). On WP < 6.9 the module boots but all callbacks no-op silently.
- The `wordpress/mcp-adapter` package (`^0.5.0`), installed via Composer.

## Boot flow

The adapter is booted inside `imagify_init()` in `inc/main.php`, **after** `$plugin->init($providers)` completes, guarded by:

```php
class_exists( \WP\MCP\Core\McpAdapter::class )
&& function_exists( 'wp_register_ability' )
&& function_exists( 'wp_get_ability' )
&& function_exists( 'wp_get_abilities' )
&& function_exists( 'wp_register_ability_category' )
```

`McpAdapter::instance()` defers its real work to `rest_api_init` (priority 15), so Imagify's subscribers (attached on `plugins_loaded` via `EventManager`) are always listening before the adapter fires `mcp_adapter_init` / `wp_abilities_api_*` actions.

## REST endpoint

| Key | Value |
|-----|-------|
| Path | `/wp-json/mcp/mcp-adapter-default-server` |
| Method | GET / POST (JSON-RPC) |
| Registered by | `wordpress/mcp-adapter` `DefaultServerFactory::create()` on `mcp_adapter_init` |

The endpoint returns HTTP 200 with the adapter's default three-tool set plus all registered Imagify abilities.

## OAuth (Claude Desktop / MCP clients)

Imagify bundles the shared `wp-media/mcp-oauth` library (`^1.0.2`) to expose an OAuth 2.1 + PKCE authenticated MCP server for clients such as Claude Desktop. Imagify contains **no custom OAuth code** — the library owns the entire flow (authorize / consent / token / revoke endpoints, `.well-known` discovery documents, and the isolated server registration).

The library is booted in `imagify_init()` in `inc/main.php`, guarded by `class_exists( \WPMedia\MCP\OAuth\Bootstrap::class )`, inside the same `$can_boot_mcp_adapter` guard as the `wordpress/mcp-adapter` boot it depends on:

```php
if ( $can_boot_mcp_adapter ) {
    \WP\MCP\Core\McpAdapter::instance();

    if ( class_exists( \WPMedia\MCP\OAuth\Bootstrap::class ) ) {
        \WPMedia\MCP\OAuth\Bootstrap::instance();
    }
}
```

The server is enabled by default from `wp-media/mcp-oauth` v1.0.2 onward, so no `wpmedia_mcp_oauth_server_enabled` filter is needed. It can still be disabled by filtering that value to `false`.

| Key | Value |
|-----|-------|
| OAuth server path | `/wp-json/mcp/mcp-oauth-server` |
| Discovery | `/.well-known/oauth-authorization-server`, `/.well-known/oauth-protected-resource` |
| Tools exposed | `mcp-adapter/discover-abilities`, `mcp-adapter/get-ability-info`, `mcp-adapter/execute-ability` |
| Auth | OAuth 2.1 + PKCE, JWT bearer tokens |

The OAuth server's fixed tool list is only the three generic mcp-adapter tools, but `discover-abilities` and `execute-ability` read from the **global** WordPress Abilities registry (`wp_get_abilities()` / `wp_get_ability()`), not a fixed category — so all `imagify/*` abilities registered by `AbilitiesSubscriber` are fully discoverable and callable through the OAuth server, with no extra wiring needed. `ConfigSubscriber`'s `mcp_adapter_default_server_config` filter is unaffected — it only customizes the unauthenticated default server, not the OAuth server created by the library.

## Classes

| Class | Responsibility |
|-------|----------------|
| `Imagify\Abilities\AbilitiesInterface` | Contract every Imagify MCP ability must implement. |
| `Imagify\Abilities\AbstractAbility` | Base class for abilities; provides `check_permissions()`, `fire_executed()`, and `guard_credit_confirmation()` (see [Credit confirmation flow](#credit-confirmation-flow)). |
| `Imagify\Abilities\CreditConsumingAbilityInterface` | Contract for abilities that spend Imagify quota (`get_impact_estimate()`); opts an ability into `guard_credit_confirmation()`. Implemented by `OptimizeMedia`, `BulkOptimize`, `GenerateMissingNextgen`. |
| `Imagify\Abilities\BulkOptimize` | MCP ability: schedule a bulk image optimization run (`imagify/bulk-optimize`). |
| `Imagify\Abilities\GenerateMissingNextgen` | MCP ability: queue generation of missing next-gen (WebP/AVIF) versions (`imagify/generate-missing-nextgen`). |
| `Imagify\Abilities\GetAccount` | Ability `imagify/get-account` — returns account quota, plan details, and API key validity. |
| `Imagify\Abilities\GetMediaStatus` | Ability `imagify/get-media-status` — returns optimization status and metrics for a media attachment. |
| `Imagify\Abilities\GetNextgenCoverage` | Ability `imagify/get-nextgen-coverage` — returns the count of optimized images missing a next-gen version and the configured format. |
| `Imagify\Abilities\GetSettings` | Ability `imagify/get-settings` — returns all Imagify configuration options (redacting `api_key` and `version`). |
| `Imagify\Abilities\GetStats` | Ability `imagify/get-stats` — returns optimization statistics for WP media and custom folders. |
| `Imagify\Abilities\OptimizeMedia` | Ability `imagify/optimize-media` — optimizes a WP media attachment on demand. |
| `Imagify\Abilities\RestoreMedia` | MCP ability: restore an optimized media to its original state via backup (`imagify/restore-media`). |
| `Imagify\Abilities\UpdateSettings` | MCP ability: updates one or more Imagify configuration settings. |
| `Imagify\MCP\ConfigSubscriber` | Customizes the MCP server name and description via `mcp_adapter_default_server_config`. |
| `Imagify\MCP\AbilitiesSubscriber` | Registers the `imagify` ability category and all injected abilities. |
| `Imagify\MCP\ServiceProvider` | DI wiring — registered in `config/providers.php`. |

## AbilitiesInterface contract

```php
namespace Imagify\Abilities;

interface AbilitiesInterface {
    public function register(): void;
    public function check_permissions(): bool;
    public function execute();
}
```

- `register()` — calls `wp_register_ability()` (guarded by `function_exists`) wiring `execute_callback` and `permission_callback`.
- `check_permissions()` — returns `current_user_can( 'manage_options' )` (per epic #1097 spec).
- `execute()` — returns the tool-result value (array, string, or any MCP-compatible type).

## Registered abilities

### Credit confirmation flow

`imagify/optimize-media`, `imagify/bulk-optimize`, and `imagify/generate-missing-nextgen` consume
Imagify quota, so each one is gated by `Imagify\Abilities\AbstractAbility::guard_credit_confirmation()`
before its real side effect runs. Because MCP tool calls are made by an AI agent rather than a human
clicking a confirm dialog, the "explicit confirmation" requirement is implemented as a two-call
protocol instead of a client-side dialog:

1. **Preview call** — call the ability without `confirm` (or with `confirm: false`). The guard never
   invokes the ability's real logic; it returns a `confirmation_required` response describing the
   impact (unit, count, and — for `bulk-optimize`/`generate-missing-nextgen` — the total) and the
   current quota remaining.
2. **Confirmed call** — resend the same call with `confirm: true`. The guard re-checks the API key and
   quota (they may have changed since the preview) and, if both are still fine, invokes the ability's
   real logic and returns its normal result.

The guard runs this exact 4-step sequence on every call, confirmed or not:

1. `Imagify_Requirements::is_api_key_valid()` — if false, returns `status: invalid_api_key` and stops.
2. `Imagify_Requirements::is_over_quota()` — if true, returns `status: insufficient_quota` and stops.
3. `$args['confirm'] === true` (strict boolean check; `"true"` as a string does not count) — if not
   true, returns `status: confirmation_required` and stops.
4. Otherwise, runs the ability's real logic and returns its result unchanged.

A confirmed call is **never** allowed to skip the quota re-check — only the confirmation step is
skipped. This closes the race where quota is exhausted between the preview and the confirmed call.

**`confirmation_required` response shape:**

| Field | Type | Description |
|-------|------|-------------|
| `status` | `"confirmation_required"` | |
| `message` | string | Human-readable summary of what will happen and how much quota remains. |
| `impact` | object | `{ unit, count }` for `optimize-media`; `{ unit, count, total }` for `bulk-optimize` / `generate-missing-nextgen`. |
| `quota_remaining` | number | Percentage of quota still available. |
| `confirm_with` | object | `{ "confirm": true }` — tells the caller exactly which argument to resend. |

**`insufficient_quota` response shape:**

| Field | Type | Description |
|-------|------|-------------|
| `status` | `"insufficient_quota"` | |
| `message` | string | Human-readable explanation that quota is exhausted. |
| `next_date_update` | string | ISO date of the next quota reset, or `""` if unknown. |
| `upgrade_url` | string | Link to the Imagify subscription/upgrade page. |

**`invalid_api_key` response shape:**

| Field | Type | Description |
|-------|------|-------------|
| `status` | `"invalid_api_key"` | |
| `message` | string | Human-readable explanation that the API key is invalid or missing. |

Every credit-consuming ability's `input_schema` includes a `confirm` boolean property (default
`false`, not in `required`) and every credit-consuming ability's `output_schema` `status` enum
includes `confirmation_required`, `insufficient_quota`, and `invalid_api_key` alongside its normal
success/error values.

### `imagify/bulk-optimize`

**Class:** `Imagify\Abilities\BulkOptimize`  
**Capability required:** `manage_options`  
**Exposed via REST:** yes (`show_in_rest: true`)  
**MCP discoverable:** yes (`mcp.public: true`)

Schedules a bulk image optimization run for the WordPress media library or custom folders. The operation is asynchronous — the ability returns immediately after dispatching via Action Scheduler / WP-Cron.

**Input schema:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `context` | string | yes | `"wp"` for the WordPress media library or `"custom-folders"` for custom folder sources. Enum: `["wp", "custom-folders"]`. |
| `optimization_level` | integer (0–2) | no | Overrides the global setting. 0 = normal, 1 = aggressive, 2 = ultra. |
| `confirm` | boolean | no | Set to `true` to execute after reviewing the credit-consumption preview returned by a prior call without this flag. Defaults to `false`. See [Credit confirmation flow](#credit-confirmation-flow). |

**Output schema:**

| Field | Type | Description |
|-------|------|-------------|
| `status` | `"scheduled"` \| `"error"` \| `"confirmation_required"` \| `"insufficient_quota"` \| `"invalid_api_key"` | Result status. |
| `context` | string | The requested optimization context, echoed back. Present only on `scheduled`/`error`. |
| `error_message` | string \| null | Human-readable error on failure, null on success. Present only on `scheduled`/`error`. |

`get_impact_estimate()`'s `count`/`total` figures come from cheap COUNT-only queries per context:
`imagify_count_unoptimized_attachments()` / `imagify_count_attachments()` for `"wp"`,
`Imagify_Files_Stats::count_unoptimized_files()` / `count_files()` for `"custom-folders"`. Any
context other than `"custom-folders"` (including an unrecognized value or a missing `context` key)
is normalized to `"wp"` before the counts and translatable `impact.label` are computed, so the
label always matches the branch the counts came from.

### `imagify/generate-missing-nextgen`

**Class:** `Imagify\Abilities\GenerateMissingNextgen`  
**Capability required:** `manage_options`  
**Exposed via REST:** yes (`show_in_rest: true`)  
**MCP discoverable:** yes (`mcp.public: true`)

Queues generation of missing next-gen (WebP/AVIF) versions for all optimized media by delegating to `Bulk::run_generate_nextgen()`. Runs asynchronously via Action Scheduler.

`status=scheduled` is returned both when jobs were enqueued (`queued_count > 0`) and when there is nothing to generate (`queued_count=0`). The latter is a successful no-op, not an error.

**Input schema:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `confirm` | boolean | no | Set to `true` to execute after reviewing the credit-consumption preview returned by a prior call without this flag. Defaults to `false`. See [Credit confirmation flow](#credit-confirmation-flow). |

**Output schema:**

| Field | Type | Description |
|-------|------|-------------|
| `status` | `"scheduled"` \| `"error"` \| `"confirmation_required"` \| `"insufficient_quota"` \| `"invalid_api_key"` | Result status. |
| `queued_count` | integer | Number of images queued for next-gen generation. Present only on `scheduled`/`error`. |
| `error_message` | string \| null | Human-readable error on failure, null on success. Present only on `scheduled`/`error`. |

`get_impact_estimate()`'s `count` comes from the live `Imagify\Stats\OptimizedMediaWithoutNextGen`
stat service (`get_stat()`, injected via DI — not the 2-day cached `get_cached_stat()`, since a stale
count could exceed `total` and mislead the credit-spend decision); `total` sums
`imagify_count_optimized_attachments() + Imagify_Files_Stats::count_optimized_files()`. `count` is
clamped to `total` as a defensive safeguard.

### `imagify/optimize-media`

Registered by `Imagify\Abilities\OptimizeMedia`. Optimizes a specific WordPress media library attachment on demand.

| Key | Value |
|-----|-------|
| Slug | `imagify/optimize-media` |
| Class | `Imagify\Abilities\OptimizeMedia` |
| Permission | `manage_options` capability |
| Annotations | `readonly: false`, `destructive: true`, `idempotent: false` |
| MCP public | `true` |

Delegates to `Imagify\Optimization\Process\WP::optimize()` for first-time optimization or `::reoptimize()` when the media has already been processed. Because both methods queue asynchronous background jobs, the `optimized_size` and `savings_percent` fields reflect data already stored in post meta at the time of the call. Clients should poll `imagify/get-media-status` to track the final result.

**Input schema:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `media_id` | integer | yes | WordPress attachment ID. |
| `optimization_level` | integer (0–2) | no | Overrides the global setting. 0 = normal, 1 = aggressive, 2 = ultra. |
| `confirm` | boolean | no | Set to `true` to execute after reviewing the credit-consumption preview returned by a prior call without this flag. Defaults to `false`. See [Credit confirmation flow](#credit-confirmation-flow). |

**Output schema:**

| Field | Type | Description |
|-------|------|-------------|
| `status` | `"success"` \| `"error"` \| `"confirmation_required"` \| `"insufficient_quota"` \| `"invalid_api_key"` | Result status. |
| `original_size` | integer \| null | File size in bytes before optimization, or null on error. Present only on `success`/`error`. |
| `optimized_size` | integer \| null | File size in bytes after optimization (may be null if the background job is not yet complete). Present only on `success`/`error`. |
| `savings_percent` | float \| null | Percentage savings, or null on error or when sizes are unavailable. Present only on `success`/`error`. |
| `error_message` | string \| null | Human-readable error on failure, null on success. Present only on `success`/`error`. |

`get_impact_estimate()` always returns `{ unit: "image", count: 1, label: "this media" }` — optimizing
a single media always costs exactly one unit, so `impact.total` is omitted from the
`confirmation_required` response for this ability.

### `imagify/restore-media`

**Class:** `Imagify\Abilities\RestoreMedia`  
**Capability required:** `manage_options`  
**Exposed via REST:** yes (`show_in_rest: true`)  
**MCP discoverable:** yes (`mcp.public: true`)

Restores an optimized media to its original state using the stored backup file. Requires that backup was enabled (`backup: 1` in settings) at the time of optimization.

**Input schema:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `media_id` | integer | yes | WordPress attachment ID to restore. |

**Output schema:**

| Field | Type | Description |
|-------|------|-------------|
| `status` | `"success"` \| `"error"` | Result status. |
| `restored_size` | integer \| null | Restored original file size in bytes, or null on error. |
| `error_message` | string \| null | Human-readable error on failure, null on success. |

## Hooks

### Filter: `mcp_adapter_default_server_config`

Subscribed by `ConfigSubscriber::customize_mcp_server()`. Sets `server_name` and `server_description`. All other keys (`server_id`, `server_route`, `tools`) are preserved.

| Param | Type | Description |
|-------|------|-------------|
| `$config` | `array` | Default server configuration from the adapter. |

Returns the modified `$config` array.

### Action: `wp_abilities_api_categories_init`

Subscribed by `AbilitiesSubscriber::register_categories()`. Registers the `imagify` ability category with label `Imagify` and a description string. No-ops on WP < 6.9.

### Action: `wp_abilities_api_init`

Subscribed by `AbilitiesSubscriber::register_abilities()`. Loops over injected `AbilitiesInterface` instances calling `->register()`. No-ops on WP < 6.9.

## Abilities

### `imagify/update-settings`

Registered by `Imagify\Abilities\UpdateSettings`. Accepts a partial settings object and updates only the supplied keys.

| Key | Value |
|-----|-------|
| Slug | `imagify/update-settings` |
| Class | `Imagify\Abilities\UpdateSettings` |
| Permission | `manage_options` capability |
| Annotations | `readonly: false`, `destructive: false`, `idempotent: true` |
| MCP public | `true` |

**Input:** a partial associative array of Imagify setting key-value pairs. Only supplied keys are changed; others remain unchanged.

**Output on success:**
```json
{
  "updated":  ["<key>", ...],
  "settings": { "<key>": "<value>", ... }
}
```
`updated` lists only the keys whose value actually changed. `settings` contains the full post-update settings (excluding `api_key` and `version`).

**Error codes:**
- `imagify_unknown_setting` — a supplied key is not a recognized Imagify setting.
- `imagify_invalid_value` — a supplied value fails the constrained-field validation (`optimization_level`, `optimization_format`, `display_nextgen_method`, `display_webp_method`).
- `imagify_api_key_immutable` — the `api_key` key was supplied while `IMAGIFY_API_KEY` constant is defined.

**Constrained fields:**
- `optimization_level`: integer `0`, `1`, or `2`
- `optimization_format`: `"off"`, `"webp"`, or `"avif"`
- `display_nextgen_method` / `display_webp_method`: `"picture"` or `"rewrite"`

All other keys pass through to `Imagify_Options::set()`, which fires the `sanitize_option_<name>` WP filter for a final sanitization pass.

### `imagify/get-account`

Registered by `Imagify\Abilities\GetAccount`. Returns the current Imagify account status, quota consumption, plan details, and whether the configured API key is valid.

| Key | Value |
|-----|-------|
| Slug | `imagify/get-account` |
| Class | `Imagify\Abilities\GetAccount` |
| Permission | `manage_options` capability |
| Annotations | `readonly: true`, `destructive: false`, `idempotent: true` |
| MCP public | `true` |

No input parameters.

**Output schema:**

| Field | Type | Description |
|-------|------|-------------|
| `plan_label` | string | Human-readable plan name (e.g. "Free", "Basic"). Empty string when the API key is invalid. |
| `quota` | string \| number | Total monthly quota for the plan. |
| `consumed_current_month_quota` | string \| number | Quota consumed in the current billing month. |
| `extra_quota` | string \| number | Extra (paid) quota purchased above the plan limit. |
| `extra_quota_consumed` | string \| number | Extra quota already consumed this month. |
| `next_date_update` | string | ISO date of the next quota reset. Empty string when the API key is invalid. |
| `is_api_key_valid` | boolean | `true` when the configured API key is valid; `false` otherwise. |

When `is_api_key_valid` is `false`, all numeric quota fields return `0` and string fields return `""`.

### `imagify/get-media-status`

Registered by `Imagify\Abilities\GetMediaStatus`. Returns the optimization status and key metrics for a given WordPress media library attachment.

| Key | Value |
|-----|-------|
| Slug | `imagify/get-media-status` |
| Class | `Imagify\Abilities\GetMediaStatus` |
| Permission | `manage_options` capability |
| Annotations | `readonly: true`, `destructive: false`, `idempotent: true` |
| MCP public | `true` |

**Input schema:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `media_id` | integer | yes | WordPress attachment ID. |

**Output schema:**

| Field | Type | Description |
|-------|------|-------------|
| `status` | `"success"` \| `"error"` \| `"unoptimized"` | Optimization status. `"success"` covers both optimized and already-optimized states; `"unoptimized"` means no optimization data exists yet. |
| `optimization_level` | integer \| null | 0 = lossless, 1 = aggressive, 2 = ultra. `null` when not yet optimized. |
| `original_size` | integer | File size in bytes before optimization (reads from filesystem when no optimization data exists). |
| `optimized_size` | integer | File size in bytes after optimization. `0` when not yet optimized. |
| `webp_available` | boolean | `true` when a WebP version of the full-size image has been generated. |
| `avif_available` | boolean | `true` when an AVIF version of the full-size image has been generated. |
| `error_message` | string \| null | Human-readable error message when `status` is `"error"`. `null` otherwise. |

**Error behaviour:** If `media_id` is `0` or negative, or the attachment does not exist in the database, returns `status: "error"` with a descriptive `error_message`.

### `imagify/get-nextgen-coverage`

Registered by `Imagify\Abilities\GetNextgenCoverage`. Returns the number of optimized images that are missing a next-gen (WebP/AVIF) version, and the next-gen format currently configured in Imagify settings.

| Key | Value |
|-----|-------|
| Slug | `imagify/get-nextgen-coverage` |
| Class | `Imagify\Abilities\GetNextgenCoverage` |
| Permission | `manage_options` capability |
| Annotations | `readonly: true`, `destructive: false`, `idempotent: true` |
| MCP public | `true` |

No input parameters. The count is served from a cached stat (not a live query).

**Output schema:**

| Field | Type | Description |
|-------|------|-------------|
| `missing_nextgen_count` | integer | Number of optimized media items that do not have a next-gen version. |
| `nextgen_format` | string | The currently configured next-gen format (`"off"`, `"webp"`, or `"avif"`). |

### `imagify/get-settings`

Registered by `Imagify\Abilities\GetSettings`. Returns all Imagify configuration options and their current values.

| Key | Value |
|-----|-------|
| Slug | `imagify/get-settings` |
| Class | `Imagify\Abilities\GetSettings` |
| Permission | `manage_options` capability |
| Annotations | `readonly: true`, `destructive: false`, `idempotent: true` |
| MCP public | `true` |

No input parameters.

**Output:** A flat associative object containing all Imagify options. The `api_key` and `version` keys are always omitted from the response to avoid leaking credentials and internal metadata.

The output shape mirrors the input schema of `imagify/update-settings` — callers can pass the returned object back directly as input to `update-settings`.

### `imagify/get-stats`

Registered by `Imagify\Abilities\GetStats`. Returns aggregated optimization statistics for both WP media library and custom folders.

| Key | Value |
|-----|-------|
| Slug | `imagify/get-stats` |
| Class | `Imagify\Abilities\GetStats` |
| Permission | `manage_options` capability |
| Annotations | `readonly: true`, `destructive: false`, `idempotent: true` |
| MCP public | `true` |

No input parameters.

**Output schema:**

| Field | Type | Description |
|-------|------|-------------|
| `wp.count_optimized` | integer | Number of successfully optimized WP media attachments. |
| `wp.count_errors` | integer | Number of WP media attachments in error state. |
| `wp.original_size` | integer | Total original size in bytes across all WP media (before optimization). |
| `wp.optimized_size` | integer | Total optimized size in bytes across all WP media (after optimization). |
| `wp.savings_percent` | number | Overall savings percentage for WP media. |
| `custom-folders.count_optimized` | integer | Number of successfully optimized files in custom folders. |
| `custom-folders.count_errors` | integer | Number of files in custom folders in error state. |
| `custom-folders.original_size` | integer | Total original size in bytes across all custom folder files. |
| `custom-folders.optimized_size` | integer | Total optimized size in bytes across all custom folder files. |
| `custom-folders.savings_percent` | number | Overall savings percentage for custom folder files. |

The `wp` and `custom-folders` objects are always present. Fields default to `0` when no data is available.

## Adding a new ability

1. Create `classes/Abilities/<AbilityName>.php` implementing `AbilitiesInterface`. See `OptimizeMedia` as a reference implementation.
2. Add the ability as a shared service and append it to the `AbilitiesSubscriber` arguments in `classes/MCP/ServiceProvider.php`:
   ```php
   $this->getContainer()->addShared( MyAbility::class );
   $this->getContainer()->addShared( AbilitiesSubscriber::class )
       ->addArguments( [ OptimizeMedia::class, MyAbility::class ] );
   ```
3. The loop in `AbilitiesSubscriber::register_abilities()` calls `->register()` on every injected ability automatically — no manual call is needed.
4. Add the class to the `$provides` array in `ServiceProvider`.
5. Document the new ability in this file under "Registered abilities".

## Patch

`wordpress/mcp-adapter` contains a PHP 8.1+ deprecated static-trait-method call in `RequestRouter.php`. The patch at `patches/wordpress/mcp-adapter/fix-static-trait-call.patch` is applied automatically during `composer install` via `cweagans/composer-patches`.
