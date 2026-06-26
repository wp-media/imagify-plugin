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

## Classes

| Class | Responsibility |
|-------|----------------|
| `Imagify\Abilities\AbilitiesInterface` | Contract every Imagify MCP ability must implement. |
| `Imagify\Abilities\OptimizeMedia` | Ability `imagify/optimize_media` — optimizes a WP media attachment on demand. |
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

### `imagify/optimize_media`

**Class:** `Imagify\Abilities\OptimizeMedia`  
**Capability required:** `manage_options`  
**Exposed via REST:** yes (`show_in_rest: true`)  
**MCP discoverable:** yes (`mcp.public: true`)

Optimizes a specific WordPress media library attachment on demand. Delegates to `Imagify\Optimization\Process\WP::optimize()` for first-time optimization or `::reoptimize()` when the media has already been processed.

Because both methods queue asynchronous background jobs, the `optimized_size` and `savings_percent` fields in the response reflect data already stored in post meta at the time of the call. Clients should poll `imagify/get-media-status` to track the final result.

**Input schema:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `media_id` | integer | yes | WordPress attachment ID. |
| `optimization_level` | integer (0–2) | no | Overrides the global setting. 0 = normal, 1 = aggressive, 2 = ultra. |

**Output schema:**

| Field | Type | Description |
|-------|------|-------------|
| `status` | `"success"` \| `"error"` | Result status. |
| `original_size` | integer \| null | File size in bytes before optimization, or null on error. |
| `optimized_size` | integer \| null | File size in bytes after optimization (may be null if job not yet complete). |
| `savings_percent` | float \| null | Percentage savings, or null on error or when sizes are unavailable. |
| `error_message` | string \| null | Human-readable error on failure, null on success. |

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
