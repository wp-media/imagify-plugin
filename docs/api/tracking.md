# Tracking Module

The `Imagify\Tracking` module wires the `wp-media/wp-mixpanel` Composer package into Imagify's DI layer and fires analytics events to Mixpanel when media optimization completes.

---

## Architecture

| Class | Role |
|---|---|
| `Imagify\Tracking\BaseTracking` | Abstract base: `can_track()`, `get_default_event_properties()` |
| `Imagify\Tracking\Tracking` | Concrete: `track_media_optimized()`, `track_settings_saved()` |
| `Imagify\Tracking\Subscriber` | Subscribes to WordPress hooks and delegates to `Tracking` |
| `Imagify\Tracking\ServiceProvider` | Registers all module services into the DI container |

The module depends on two Strauss-prefixed vendor classes:

- `Imagify\Dependencies\WPMedia\Mixpanel\Optin` — manages opt-in state
- `Imagify\Dependencies\WPMedia\Mixpanel\TrackingPlugin` — sends events to Mixpanel

---

## Option key

| Option | Default | Description |
|---|---|---|
| `imagify_mixpanel_optin` | `false` | Tracks whether the user has opted in to analytics. Set to `1` to enable tracking (e.g., `wp option update imagify_mixpanel_optin 1`). The opt-in toggle UI ships in a follow-up issue. |

---

## Hooks subscribed

| Hook | Method | Priority | Args |
|---|---|---|---|
| `imagify_after_optimize` | `Subscriber::track_media_optimized` | 10 | 2 (`$process`, `$item`) |
| `update_option_imagify_settings` | `Subscriber::track_settings_saved` | 10 | 2 (`$old_value`, `$new_value`) |
| `update_site_option_imagify_settings` | `Subscriber::track_settings_saved` | 10 | 2 (`$option`, `$new_value`) |

`imagify_after_optimize` fires inside ActionScheduler (see `classes/Job/MediaOptimization.php`). Because bulk/cron runs execute without a logged-in user, the module uses `Optin::can_track()` (option-only check) and `TrackingPlugin::track_direct()` (bypasses `current_user_can`).

`update_option_imagify_settings` fires on single-site when the option value changes. `update_site_option_imagify_settings` fires on multisite. Note the argument ordering differs between them: on multisite the first argument is the option name string, not the old value — the delegating method casts both args to array to satisfy the strict-typed `Tracking` signature, and the old value is unused downstream.

---

## Event: Media Optimized

Fired after a successful full-size optimization. Guards (all must pass):

1. `can_track()` returns `true` (opt-in enabled).
2. `'full'` is present in `$item['sizes_done']`.
3. `get_size_data('full')['success'] === true`.

### Event properties

| Property | Source | Notes |
|---|---|---|
| `context` | `'wp_plugin'` | Always `wp_plugin` |
| `license_owner` | SHA-256 hash of `get_imagify_user()->email` | Empty string if not connected |
| `user_id` | `get_current_user_id()` | `0` during cron/bulk runs |
| `optimization_level` | `DataInterface::get_optimization_level()` | 0=normal, 1=aggressive, 2=ultra |
| `media_type` | `MediaInterface::get_mime_type()` | e.g. `image/jpeg` |
| `original_size` | `get_size_data('full', 'original_size')` | Bytes |
| `optimized_size` | `get_size_data('full', 'optimized_size')` | Bytes |
| `savings_percent` | Computed | `0` when `original_size` is `0` |
| `next_gen_format` | AVIF checked first, then WebP | `'avif'`, `'webp'`, or `null` |
| `trigger` | Resolved from context | `'auto'`, `'bulk'`, or `'manual'` |

`TrackingPlugin::track_direct()` automatically merges `domain`, `wp_version`, `php_version`, `plugin`, `brand`, and `application` — do NOT include these in `get_default_event_properties()`.

### Trigger resolution

1. `auto` — `$item['data']['is_new_upload']` is truthy.
2. `bulk` — transient `imagify_wp_optimize_running` or `imagify_custom-folders_optimize_running` is set.
3. `manual` — fallback.

When `auto` and bulk transients are both true, `auto` wins.

---

## Event: Settings Saved

Fired each time the Imagify settings option is actually updated (WordPress only fires `update_option_*` when the value changes). Guards:

1. `can_track()` returns `true` (opt-in enabled).

### Event properties

| Property | Source option key | Notes |
|---|---|---|
| `context` | — | Always `'wp_plugin'` (from `get_default_event_properties()`) |
| `license_owner` | — | SHA-256 hash of `get_imagify_user()->email`; empty string if not connected |
| `user_id` | — | `get_current_user_id()` |
| `optimization_level` | `optimization_level` | Cast to `int`; `null` if key absent |
| `auto_optimize_on_upload` | `auto_optimize` | `bool` via `! empty()` |
| `resize_larger_images` | `resize_larger` | `bool` via `! empty()` |
| `next_gen_images_webp` | `convert_to_webp` | `bool` via `! empty()` |
| `next_gen_images_avif` | `convert_to_avif` | `bool` via `! empty()` |
| `backup_original` | `backup` | `bool` via `! empty()` |

`TrackingPlugin::track_direct()` automatically merges `domain`, `wp_version`, `php_version`, `plugin`, `brand`, and `application` — these are not set manually.

---

## ServiceProvider bindings

Registered in `config/providers.php` as `Imagify\Tracking\ServiceProvider`.

| Binding | Arguments |
|---|---|
| `Imagify\Dependencies\WPMedia\Mixpanel\Optin` | `'imagify'`, `'manage_options'` |
| `Imagify\Dependencies\WPMedia\Mixpanel\TrackingPlugin` | token, `'imagify <VERSION>'`, `'wp media'`, `'imagify'` |
| `Imagify\Tracking\Tracking` | `Optin`, `TrackingPlugin` |
| `Imagify\Tracking\Subscriber` | `Tracking` |
