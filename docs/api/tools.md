# Tools Module — Internal State Reset

## Overview

The `Imagify\Tools` module provides a one-click tool to reset Imagify's internal optimization state. This is the programmatic equivalent of deactivating and reactivating the plugin — it clears stale transients, process locks, and ActionScheduler jobs that can cause the bulk optimizer to appear stuck.

Settings, the API key, and user-data caches are intentionally left untouched.

## Classes

| Class | Responsibility |
|-------|----------------|
| `Imagify\Tools\InternalStateList` | Single source of truth for the three canonical arrays (bulk transients, lock patterns, scheduler hooks). No dependencies, no constructor — safe to `require_once` from `uninstall.php`. |
| `Imagify\Tools\ResetInternalState` | Service that performs the actual cleanup. Iterates `InternalStateList` and issues the necessary `delete_transient()` calls, raw SQL DELETEs (via `$wpdb->prepare`), and `as_unschedule_all_actions()` calls. |
| `Imagify\Tools\Subscriber` | `SubscriberInterface` implementation that wires the AJAX action. |
| `Imagify\Tools\ServiceProvider` | DI wiring — registered in `config/providers.php`. |

## AJAX Action

| Key | Value |
|-----|-------|
| Action name | `imagify_reset_internal_state` |
| Hook | `wp_ajax_imagify_reset_internal_state` |
| Method | POST |
| Nonce key | `_wpnonce` |
| Nonce action | `imagify_reset_internal_state` |
| Capability | `imagify_get_context('wp')->current_user_can('manage')` |

On success the endpoint returns:

```json
{ "success": true, "data": { "message": "Imagify internal state has been reset successfully." } }
```

On capability failure `imagify_die()` is called (403). On nonce failure `imagify_check_nonce()` dies with the standard WordPress error.

**Nonce delivery:** The nonce must be sent as `_wpnonce` in the POST body. Sending it as `nonce` causes a silent 403 because `imagify_check_nonce()` delegates to `check_ajax_referer($action, false)`, which checks only `_ajax_nonce` and `_wpnonce`.

## What Gets Cleared

### Bulk running-state transients (`InternalStateList::get_bulk_transients()`)

- `imagify_custom-folders_optimize_running`
- `imagify_wp_optimize_running`
- `imagify_bulk_optimization_complete`
- `imagify_missing_next_gen_total`
- `imagify_bulk_optimization_result`
- `imagify_bulk_optimization_infos`
- `imagify_bulk_optimization_level` (legacy artifact, cleared for hygiene)

Note: this is a superset of `Bulk::delete_transients_data()`. The last two entries are not cleared by the Bulk deactivation hook.

### Process-lock LIKE patterns (`InternalStateList::get_locked_transient_patterns()`)

SQL `DELETE … WHERE option_name LIKE` patterns run against `$wpdb->options`. On multisite a second query runs against `$wpdb->sitemeta`.

- `\_transient\_%imagify-auto-optimize-%` (legacy)
- `\_transient\_%imagify\_rpc\_%` (legacy)
- `\_transient\_imagify\_%\_process\_locked`
- `\_site\_transient\_imagify\_%\_process\_lock%`

### ActionScheduler hooks (`InternalStateList::get_scheduler_hooks()`)

Unscheduled via `as_unschedule_all_actions()` (guarded by `function_exists`):

- `imagify_optimize_media`
- `imagify_convert_next_gen`

## Extending the Lists

Add new items to `InternalStateList` — the single source of truth consulted by both `ResetInternalState` (live reset) and `uninstall.php` (plugin removal).

## Multisite Notes

The `$wpdb->options` DELETE runs on every call. The `$wpdb->sitemeta` DELETE is guarded by `is_multisite()`. The reset is scoped to the current site — it does not iterate all network sites.
