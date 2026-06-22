# MCP Module — Model Context Protocol Foundation

## Overview

The `Imagify\MCP` module integrates Imagify with the WordPress MCP (Model Context Protocol) adapter (`wordpress/mcp-adapter`). It exposes an MCP server endpoint that AI agents can use to discover and invoke Imagify abilities.

This module ships the **foundation only** (issue #1108). Zero Imagify-specific abilities are registered yet — the adapter's three built-in tools (`discover-abilities`, `get-ability-info`, `execute-ability`) are always present. Concrete abilities are added by downstream sub-issues under `classes/Abilities/`.

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

With zero Imagify abilities the endpoint returns HTTP 200 with the adapter's default three-tool set and zero Imagify-category abilities.

## Classes

| Class | Responsibility |
|-------|----------------|
| `Imagify\Abilities\AbilitiesInterface` | Contract every Imagify MCP ability must implement. |
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

## Adding a new ability (downstream sub-issues)

1. Create `classes/Abilities/<Group>/<AbilityName>.php` implementing `AbilitiesInterface`.
2. Add the ability as a shared service and pass it to `AbilitiesSubscriber` via `addArguments()` in `classes/MCP/ServiceProvider.php`:
   ```php
   $this->getContainer()->addShared( MyAbility::class );
   $this->getContainer()->addShared( AbilitiesSubscriber::class )
       ->addArguments( [ MyAbility::class ] );
   ```
3. Call `->register()` inside `AbilitiesSubscriber::register_abilities()` via the injected instance (the loop handles this automatically once the ability is passed in the constructor).

## Patch

`wordpress/mcp-adapter` contains a PHP 8.1+ deprecated static-trait-method call in `RequestRouter.php`. The patch at `patches/wordpress/mcp-adapter/fix-static-trait-call.patch` is applied automatically during `composer install` via `cweagans/composer-patches`.
