# MCP Module — Model Context Protocol Foundation

## Overview

The `Imagify\MCP` module integrates Imagify with the WordPress MCP (Model Context Protocol) adapter (`wordpress/mcp-adapter`). It exposes an MCP server endpoint that AI agents can use to discover and invoke Imagify abilities.

This module ships the MCP foundation (issue #1108) plus a growing set of Imagify-specific abilities registered under `classes/Abilities/`. The adapter's three built-in tools (`discover-abilities`, `get-ability-info`, `execute-ability`) are always present.

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

The endpoint returns HTTP 200 with the adapter's default three-tool set plus all registered Imagify-category abilities.

## Classes

| Class | Responsibility |
|-------|----------------|
| `Imagify\Abilities\AbilitiesInterface` | Contract every Imagify MCP ability must implement. |
| `Imagify\Abilities\GenerateMissingNextgen` | Queues generation of missing next-gen (WebP/AVIF) versions for all optimized media. |
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

Subscribed by `AbilitiesSubscriber::register_abilities()`. Loops over injected `AbilitiesInterface` instances calling `->register()`. No-ops on WP < 6.9. With zero abilities (foundation) the loop body never executes.

## Registered abilities

### `imagify/generate-missing-nextgen`

Implemented by `Imagify\Abilities\GenerateMissingNextgen` (issue #1106).

Queues generation of missing next-gen (WebP/AVIF) versions for all optimized media by delegating to `Bulk::run_generate_nextgen()`. Runs asynchronously via Action Scheduler.

| Field | Value |
|-------|-------|
| Slug | `imagify/generate-missing-nextgen` |
| Category | `imagify` |
| Permission | `manage_options` capability |
| `readonly` | `false` |
| `destructive` | `true` |
| `idempotent` | `false` (re-invocation schedules duplicate jobs) |

**Output schema:**

```json
{
  "status": "scheduled" | "error",
  "queued_count": integer,
  "error_message": string | null
}
```

`status=scheduled` is returned both when jobs were enqueued (`queued_count > 0`) and when there is nothing to generate (`queued_count=0`). The latter occurs when all optimized media already have next-gen versions — this is a successful no-op, not an error.

## Adding a new ability (downstream sub-issues)

1. Create `classes/Abilities/<AbilityName>.php` implementing `AbilitiesInterface`.
2. In `classes/MCP/ServiceProvider.php`:
   - Add the ability class to `$provides`.
   - Bind it: `$this->getContainer()->addShared( MyAbility::class );`
   - Extend the existing `AbilitiesSubscriber` definition to inject it:
     ```php
     $this->getContainer()->extend( AbilitiesSubscriber::class )
         ->addArgument( MyAbility::class );
     ```
   - Do NOT call `addShared( AbilitiesSubscriber::class )` a second time — `extend()` operates on the existing binding.
3. The loop in `AbilitiesSubscriber::register_abilities()` calls `->register()` on every injected ability automatically — no manual call is needed.

## Patch

`wordpress/mcp-adapter` contains a PHP 8.1+ deprecated static-trait-method call in `RequestRouter.php`. The patch at `patches/wordpress/mcp-adapter/fix-static-trait-call.patch` is applied automatically during `composer install` via `cweagans/composer-patches`.
