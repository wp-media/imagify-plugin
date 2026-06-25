# Imagify — Complete Technical Summary

> Version 2.2.8 · PHP 7.3+ · WordPress 5.3+
>
> WordPress image optimisation plugin — compression, next-gen formats (WebP / AVIF), and multi-context management.

---

## Table of Contents

1. [Architecture & Bootstrapping](#1-architecture--bootstrapping)
2. [Image Optimisation](#2-image-optimisation)
3. [Next-gen Formats (WebP / AVIF)](#3-next-gen-formats-webp--avif)
4. [Settings & Configuration](#4-settings--configuration)
5. [Bulk Optimisation](#5-bulk-optimisation)
6. [WordPress Media Library Integration](#6-wordpress-media-library-integration)
7. [Custom Folders](#7-custom-folders)
8. [AJAX & Admin-Post Actions](#8-ajax--admin-post-actions)
9. [WP-CLI Commands](#9-wp-cli-commands)
10. [NextGEN Gallery Integration](#10-nextgen-gallery-integration)
11. [Third-Party Integrations](#11-third-party-integrations)
12. [Scheduled Tasks (Cron)](#12-scheduled-tasks-cron)
13. [Developer Hooks & Filters](#13-developer-hooks--filters)
14. [Quota & Account Management](#14-quota--account-management)
15. [Roles & Access Control](#15-roles--access-control)
16. [Troubleshooting Tools](#16-troubleshooting-tools)

---

## 1. Architecture & Bootstrapping

*Source code organisation, service loading, and plugin lifecycle.*

### Entry Point

The plugin starts from `imagify.php`. It defines global constants, includes the Composer autoloader, then hooks the `imagify_init()` function on `plugins_loaded`.

**Key constants**
- `IMAGIFY_VERSION` — plugin version
- `IMAGIFY_PATH` / `IMAGIFY_URL`
- `IMAGIFY_MAX_BYTES` — 5 MB limit per image
- `IMAGIFY_APP_API_URL` — remote API URL
- `IMAGIFY_API_KEY` — override via PHP constant

**Autoloading & DI**
- PSR-4 via Composer (`vendor/autoload.php`)
- Dependency injection: **League Container**
- Providers declared in `config/providers.php`
- Main class: `Imagify\Plugin`
- Final hook: `do_action('imagify_loaded')`

### Registered Service Providers

`User` · `Admin` · `Avif` · `CDN` · `Picture` · `Stats` · `Webp` · `ThirdParty` · `Media` · `Tools`

### Initialisation Sequence

```
// 1. plugins_loaded
imagify_init()
  └── include vendor/autoload.php
  └── new Imagify\Plugin()
        └── load config/providers.php        // register services
        └── boot Options, Data, Auth
        └── boot Auto-Optimization
        └── (admin only) boot Settings, Views, Imagifybeat
  └── do_action('imagify_loaded')
```

---

## 2. Image Optimisation

*On-the-fly or bulk compression, quality levels, backup management, and resizing.*

### Compression Levels

| Level | Name | Description |
|-------|------|-------------|
| `0` | Normal | Light compression, maximum quality preserved. |
| `1` | Aggressive | Stronger compression, good quality/size trade-off. |
| `2` | Ultra (default) | Maximum compression — recommended for most use cases. |

> All optimisation operations go through the `Imagify\Optimization\File` class, which communicates with the remote Imagify API.

### Automatic Optimisation on Upload

When the `auto_optimize` option is enabled, the `Imagify_Auto_Optimization` class hooks into `wp_generate_attachment_metadata`. Each uploaded image is optimised immediately, along with all its thumbnails.

### Backup System

- Enabled via the `backup` option.
- The original file is copied before any compression.
- Backup directory is filterable via `imagify_backup_directory`.
- Restoring puts the original back in place and removes optimisation metadata.

### Large Image Resizing

- Option `resize_larger`: when enabled, images exceeding `resize_larger_w` pixels wide are resized.
- Default value: **2560 px** (aligned with the WP filter `big_image_size_threshold`).

### Thumbnail Management

All sizes registered in WordPress are optimised. Sizes can be excluded via the `disallowed-sizes` blacklist in the settings.

### Optimisation Statuses

`success` · `already_optimized` · `error` · `pending`

### Metadata Stored Per Attachment

```
// Attachment post meta
_imagify_status          // 'success' | 'already_optimized' | 'error' | 'pending'
_imagify_data            // JSON — full optimisation results
_imagify_level           // Level used (0, 1, 2)
_imagify_next_gen_done   // Boolean — next-gen versions generated
```

---

## 3. Next-gen Formats (WebP / AVIF)

*Generation and delivery of modern formats to reduce image weight on the browser side.*

### File Generation

After optimisation, Imagify can generate **WebP** and/or **AVIF** versions of each image. The `optimization_format` option controls this behaviour:

| Value | Behaviour |
|-------|-----------|
| `'off'` | No next-gen format. |
| `'webp'` | Generates WebP only. |
| `'avif'` | Generates WebP + AVIF (AVIF takes priority if supported). |

### Display Method: `<picture>`

Class: `Imagify\Picture\Display`. The plugin starts an output buffer on `template_redirect`, scans the generated HTML, and replaces each `<img>` with a `<picture>` block containing `<source>` elements pointing to the WebP/AVIF files.

```html
<!-- Before -->
<img src="photo.jpg" alt="...">

<!-- After transformation -->
<picture>
  <source srcset="photo.avif" type="image/avif">
  <source srcset="photo.webp" type="image/webp">
  <img src="photo.jpg" alt="...">
</picture>
```

### Display Method: Server Rewrite

Class: `Imagify\Webp\Display`. Rather than altering the HTML, rules are written directly into `.htaccess` (Apache) or `web.config` (IIS). The server automatically serves WebP if the browser accepts it (`Accept: image/webp`).

**Lazy Load compatibility:** The `data-lazy-src`, `data-src`, `data-srcset`, `data-lazy-srcset` attributes are detected and propagated into the generated `<source>` and `<img>` tags.

**Image exclusion:** An image with the CSS class `imagify-no-webp` is ignored by the `<picture>` transformation.

> **Warning:** the server rewrite method requires the web server to be Apache or IIS and that the configuration files (.htaccess) be writable.

---

## 4. Settings & Configuration

*All options stored in `imagify_settings` (wp_options / wp_sitemeta on multisite).*

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `api_key` | string | `''` | Imagify API key (or the `IMAGIFY_API_KEY` constant). |
| `optimization_level` | int | `2` | Compression level (0, 1, 2). |
| `lossless` | bool | `0` | Lossless compression. |
| `auto_optimize` | bool | `1` | Automatic optimisation on upload. |
| `backup` | bool | `1` | Back up the original before compression. |
| `resize_larger` | bool | `0` | Resize images that are too large. |
| `resize_larger_w` | int | `0` | Maximum width in pixels. |
| `optimization_format` | string | `'webp'` | `'off'`, `'webp'`, or `'avif'`. |
| `display_nextgen_method` | string | `'picture'` | `'picture'` or `'rewrite'`. |
| `cdn_url` | string | `''` | CDN URL for serving media. |
| `disallowed-sizes` | array | `[]` | Thumbnail sizes excluded from optimisation. |
| `admin_bar_menu` | bool | `1` | Show Imagify in the admin bar. |

> The `Imagify_Settings` class handles option registration (`register_setting()`) and validation. It is network-aware: on multisite, network options are stored in `wp_sitemeta`.

---

## 5. Bulk Optimisation

*Batch processing of all existing images, with an asynchronous queue.*

### Bulk Architecture

The `Imagify\Bulk\Bulk` class orchestrates bulk optimisation. It relies on **ActionScheduler** (bundled library) to create asynchronous jobs processed in the background.

| Context | Class | Description |
|---------|-------|-------------|
| WP Media | `Imagify\Bulk\WP` | Optimises all attachments in the WordPress media library that have not yet been optimised. |
| Custom Folders | `Imagify\Bulk\CustomFolders` | Optimises files indexed in the custom folders. |
| NGG | `Imagify\Bulk\NGG` | Optimises images from NextGEN Gallery galleries. |

### Execution Flow

```
// Trigger (UI or CLI)
imagify_bulk_optimize (AJAX)  // or wp imagify bulk-optimize
  └── Imagify\Bulk\Bulk::run()
        └── ActionScheduler::enqueue(imagify_optimize_media)
              └── Imagify\Job\MediaOptimization::execute()
                    └── Imagify\Optimization\File::optimize()
                          └── Remote Imagify API
```

### Bulk Next-gen Version Generation

The AJAX action `imagify_missing_nextgen_generation` and the ActionScheduler hook `imagify_convert_next_gen` allow generating WebP/AVIF versions for all already-optimised images whose next-gen files are missing.

---

## 6. WordPress Media Library Integration

*Columns, actions, and buttons directly within the WordPress media interface.*

**List view (Media Library)**
- "Imagify" column with status and weight savings.
- Inline action buttons: Optimize, Re-optimize, Restore, Generate next-gen, Delete next-gen.
- "Bulk Optimization" group action.
- Filter by status: `?imagify-status=...`.

**Attachment edit page**
- Dedicated metabox with detailed status.
- Individual optimisation buttons.
- Display of savings achieved (KB and %).
- Restore link to the original.

> The WordPress admin bar can also display a shortcut to the Bulk Optimization page, controlled by the `admin_bar_menu` option.

---

## 7. Custom Folders

*Optimisation of image files located outside the WordPress media library.*

Custom folders allow optimising images located anywhere on the server (themes, plugins, custom directories). They are indexed in two dedicated MySQL tables.

| Table | Contents |
|-------|----------|
| `imagify_folders` | Monitored directories, absolute path on the server, scan status |
| `imagify_files` | Individually indexed files, original/optimised size, status, level, generated formats, hash to detect modifications |

### Available Features
- **Scan**: automatic detection of new images in configured folders.
- **Individual or bulk optimisation** via the same ActionScheduler system.
- **Re-optimisation**: if the source file has been modified (detected by hash).
- **Restore** from backup.
- **Cron synchronisation** to detect modified/deleted files.

### Main Classes

```php
// Database access
Imagify_Files_DB        // CRUD on wp_imagify_files
Imagify_Folders_DB      // CRUD on wp_imagify_folders
Imagify_Files_Scan      // Filesystem scan

// Context
Imagify\Context\CustomFolders
```

---

## 8. AJAX & Admin-Post Actions

*All server entry points used by the administration interface.*

> The plugin does not expose a standard WordPress REST API. All actions go through `wp_ajax_*` (authenticated) or `admin_post_*`. A custom "Imagifybeat" system handles real-time updates.

### AJAX Actions (`wp_ajax_*`)

**Account & API**
- `imagify_signup`
- `imagify_check_api_key_validity`
- `imagify_get_user_data`
- `imagify_delete_user_data_cache`
- `imagify_get_prices`
- `imagify_check_coupon`

**Bulk & stats**
- `imagify_bulk_optimize`
- `imagify_missing_nextgen_generation`
- `imagify_bulk_get_stats`
- `imagify_get_folder_type_data`
- `imagify_bulk_info_seen`
- `imagify_get_images_counts`

**UI & settings**
- `imagify_check_backup_dir_is_writable`
- `imagify_get_files_tree`
- `imagify_update_estimate_sizes`

**Tools**
- `imagify_reset_internal_state`
- `imagify_rpc` (Imagifybeat)

### Admin-Post Actions (`admin_post_*`)

**WP Media Library**
- `imagify_manual_optimize`
- `imagify_manual_reoptimize`
- `imagify_optimize_missing_sizes`
- `imagify_generate_nextgen_versions`
- `imagify_delete_nextgen_versions`
- `imagify_restore`

**Custom Folders**
- `imagify_optimize_file`
- `imagify_reoptimize_file`
- `imagify_restore_file`
- `imagify_refresh_file_modified`
- `imagify_scan_custom_folders`

### Imagifybeat

Real-time update system (analogous to WordPress Heartbeat). Hook: `wp_ajax_imagifybeat`. Interface data (nonces, statuses) is refreshed periodically via AJAX polling, configurable by filter.

---

## 9. WP-CLI Commands

*Command-line interface for automating optimisation operations.*

### `wp imagify bulk-optimize`
Launches bulk optimisation for one or more contexts (`wp`, `custom-folders`).
- Option: `--optimization-level`
- Asynchronous execution via ActionScheduler

### `wp imagify restore`
Restores original images for the specified contexts (`library`, `custom-folders`).
- Synchronous (blocking) execution
- Returns: success, errors, total

### `wp imagify generate-missing-nextgen`
Generates missing WebP/AVIF versions for all already-optimised images.
- Useful after enabling a next-gen format

---

## 10. NextGEN Gallery Integration

*Full support for the NextGEN Gallery (NGG) plugin.*

The plugin automatically detects NextGEN Gallery and activates a dedicated context (`Imagify\Context\NGG`).

**Features**
- Optimisation of NGG images (levels 0/1/2)
- WebP / AVIF generation for NGG images
- Restore NGG originals
- Backup before compression

**Interface**
- "Bulk Optimization" page in the NGG menu
- "Imagify" column in "Manage Images"
- Verified compatibility: NGG v4.x (`imagify_ngg_has_pope_storage()`)

---

## 11. Third-Party Integrations

### Plugins

WooCommerce · WP Rocket · Gravity Forms · Formidable Pro · Yoast SEO · AMP · Enable Media Replace · Regenerate Thumbnails · Amazon S3 & CloudFront · Real Media Library · Extendify · Cloudflare Super Page Cache

**WooCommerce detail:** On variable product pages, WooCommerce dynamically replaces the main image. Imagify fixes the `wp-post-image` class on generated `<picture>` tags to maintain compatibility with WooCommerce's image-switching mechanism.

### Hosting Providers

WordPress.com · WP Engine · Flywheel · SiteGround · Pressable

---

## 12. Scheduled Tasks (Cron)

*Recurring jobs for maintenance and statistics.*

| Task | WP Cron Hook | Frequency | Role |
|------|-------------|-----------|------|
| `Imagify_Cron_Rating` | `imagify_rating_event` | Daily (3:00 PM) | Triggers the plugin rating request. |
| `Imagify_Cron_Library_Size` | — | Periodic | Recalculates media library statistics. |
| `Imagify_Cron_Sync_Files` | — | Periodic | Synchronises files in custom folders. |

### ActionScheduler

The ActionScheduler library is bundled in `/inc/Dependencies/ActionScheduler/`. It manages asynchronous optimisation jobs:

- `imagify_optimize_media` — Processes a single optimisation job.
- `imagify_convert_next_gen` — Generates next-gen versions.

All jobs are cleaned up when the plugin is deactivated.

---

## 13. Developer Hooks & Filters

*Extension points for customising the plugin's behaviour.*

### Main Actions

| Hook | When |
|------|------|
| `imagify_loaded` | Plugin fully loaded and ready. |
| `imagify_activation` | On plugin activation. |
| `imagify_deactivation` | On plugin deactivation. |
| `imagify_optimize_media` | ActionScheduler optimisation job. |
| `imagify_convert_next_gen` | Next-gen generation job. |
| `imagify_delete_media` | Deletion of a media item. |
| `imagify_settings_on_save` | After settings are saved. |
| `imagify_not_over_quota_anymore` | Quota dropped back below 100%. |

### Main Filters

| Filter | Usage |
|--------|-------|
| `imagify_backup_directory` | Change the backup folder. |
| `imagify_register_context` | Register a custom optimisation context. |
| `imagify_picture_attributes` | Modify attributes of the `<picture>` tag. |
| `imagify_picture_source_attributes` | Modify attributes of `<source>` tags. |
| `imagify_picture_img_attributes` | Modify attributes of the inner `<img>`. |
| `imagify_allow_picture_tags_for_nextgen` | Disable the `<picture>` transformation. |
| `imagify_buffer` | Filter the final HTML page buffer. |
| `imagify_cdn_source_url` | Override the CDN URL. |
| `imagify_event_recurrence` | Change the frequency of Imagify cron jobs. |
| `imagify_event_time` | Change the trigger time for cron jobs. |
| `imagify_bulk_stats` | Modify bulk statistics data. |
| `imagify_unoptimized_attachment_limit` | Limit query results. |

---

## 14. Quota & Account Management

*Subscription plans, quota consumption, and user data cache.*

### Available Plans

| Plan | `plan_id` | Description |
|------|----------|-------------|
| **Free** | `1` | Limited monthly quota. Blocked at 100% consumption (`is_over_quota()`). |
| **Growth** | `16` / `18` | Larger monthly quota, additional byte packs available. |
| **Infinite** | `15` / `17` | Unlimited quota — no quota blocking. |

### Data Exposed by `Imagify\User\User`

```
quota                        // Total monthly quota (MB)
consumed_current_month_quota // Quota consumed this month (MB)
extra_quota                  // Imagify byte pack (MB)
extra_quota_consumed         // Pack consumed (MB)
next_date_update             // Quota reset date
is_active                    // Account active
```

### Cache

User data is cached in a WordPress transient (`imagify_user_cache`) for **5 minutes**. The cache can be manually cleared via the AJAX action `imagify_delete_user_data_cache`.

---

## 15. Roles & Access Control

*WordPress capabilities required for each Imagify action.*

| Action | Required WP Capability | Context |
|--------|----------------------|---------|
| `manage` — Access settings | `manage_options` | All |
| `optimize` — Optimize a media item | `upload_files` | All |
| `bulk-optimize` — Launch a bulk run | `manage_options` | All |

> Capabilities are defined in `Imagify\Context\AbstractContext::get_capacity()` and can be overridden via the standard WordPress filter `option_page_capability_imagify`.

---

## 16. Troubleshooting Tools

*Built-in tools to resolve common issues without technical intervention.*

### Internal State Reset

AJAX action: `imagify_reset_internal_state` (requires the `manage` capability). This one-click tool unblocks the bulk optimiser when it gets stuck in an inconsistent state.

### What Gets Cleaned Up

**Deleted transients**
- `imagify_custom-folders_optimize_running`
- `imagify_wp_optimize_running`
- `imagify_bulk_optimization_complete`
- `imagify_bulk_optimization_result`
- `imagify_missing_next_gen_total`
- `imagify_bulk_optimization_infos`

**Deleted locks & jobs**
- Auto-optimize locks (`_transient_%imagify-auto-optimize-%`)
- RPC locks (`_transient_%imagify_rpc_%`)
- Process locks (`_transient_imagify_%_process_locked`)
- ActionScheduler jobs: `imagify_optimize_media`
- ActionScheduler jobs: `imagify_convert_next_gen`

> The `Imagify\Tools\InternalStateList` class is the single source of truth for all these items — it is also used by `uninstall.php` for a full cleanup on uninstallation.

---

*Imagify v2.2.8 · wp-media/imagify-plugin*
