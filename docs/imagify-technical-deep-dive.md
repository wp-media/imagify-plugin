# Imagify — Engineering Deep Dive

> Version 2.2.8 · PHP 7.3+ · WordPress 5.3+ · PSR-4 · League Container · ActionScheduler
>
> Complete process-level reference for plugin engineers. Class hierarchies, call flows, DB schemas, hook signatures, API structures, and concurrency details.

---

## Table of Contents

1. [Architecture & Bootstrapping](#1-architecture--bootstrapping)
2. [Namespace & PSR-4 Structure](#2-namespace--psr-4-structure)
3. [Optimization Process — Class Hierarchy & Call Flow](#3-optimization-process--class-hierarchy--call-flow)
4. [Optimization\File — Method Signatures](#4-optimizationfile--method-signatures)
5. [API Client — Endpoints & Request/Response](#5-api-client--endpoints--requestresponse)
6. [WordPress Postmeta Keys & Data Structures](#6-wordpress-postmeta-keys--data-structures)
7. [Settings — Option Keys, Types & Defaults](#7-settings--option-keys-types--defaults)
8. [Database Schemas](#8-database-schemas)
9. [Bulk Optimization — ActionScheduler Integration](#9-bulk-optimization--actionscheduler-integration)
10. [Concurrency & Locking Mechanisms](#10-concurrency--locking-mechanisms)
11. [Picture\Display — Output Buffer HTML Rewrite](#11-picturedisplay--output-buffer-html-rewrite)
12. [AJAX & Admin-Post — Full Security Table](#12-ajax--admin-post--full-security-table)
13. [WP-CLI Commands — Full Signatures](#13-wp-cli-commands--full-signatures)
14. [Developer Hooks — Exact Signatures & Parameter Types](#14-developer-hooks--exact-signatures--parameter-types)
15. [Scheduled Tasks — Cron & ActionScheduler](#15-scheduled-tasks--cron--actionscheduler)
16. [Multisite Handling](#16-multisite-handling)
17. [NextGEN Gallery Integration](#17-nextgen-gallery-integration)
18. [Third-Party Integrations](#18-third-party-integrations)
19. [Quota & Account Management](#19-quota--account-management)
20. [Roles & Capabilities](#20-roles--capabilities)
21. [Troubleshooting Tools — InternalStateList & Reset](#21-troubleshooting-tools--internalstatelist--reset)
22. [Error Handling Paths](#22-error-handling-paths)
23. [Filesystem Operations & Paths](#23-filesystem-operations--paths)

---

## 1. Architecture & Bootstrapping

*Entry point, constants, DI container, service provider chain, and init sequence.*

### Entry Point — `imagify.php`

WordPress loads `imagify.php` during the `plugins_loaded` phase. It defines all constants and registers activation/deactivation hooks before delegating to `inc/main.php`.

```php
// imagify.php — constants defined at plugin load time
define( 'IMAGIFY_VERSION',        '2.2.8' );
define( 'IMAGIFY_SLUG',           'imagify' );
define( 'IMAGIFY_FILE',           __FILE__ );
define( 'IMAGIFY_PATH',           realpath( plugin_dir_path( IMAGIFY_FILE ) ) . '/' );
define( 'IMAGIFY_URL',            plugin_dir_url( IMAGIFY_FILE ) );
define( 'IMAGIFY_ASSETS_IMG_URL', IMAGIFY_URL . 'assets/images/' );
define( 'IMAGIFY_MAX_BYTES',      5242880 );   // 5 MB hard limit per image
define( 'IMAGIFY_INT_MAX',        PHP_INT_MAX - 30 );
define( 'IMAGIFY_SITE_DOMAIN',    'https://imagify.io' );
define( 'IMAGIFY_APP_DOMAIN',     'https://app.imagify.io' );
define( 'IMAGIFY_APP_API_URL',    IMAGIFY_APP_DOMAIN . '/api/' );
```

### Bootstrap Sequence

`plugins_loaded` → `imagify_init()` → `vendor/autoload.php` → `new Plugin(Container, args)` → `Plugin::init($providers)` → `do_action('imagify_loaded')`

`imagify_init()` lives in `inc/main.php`. It skips execution if `DOING_AUTOSAVE` is defined. The `Plugin` class (`classes/Plugin.php`) receives a **League\Container** instance and the plugin path, then orchestrates the full init sequence:

```php
// classes/Plugin.php — init sequence (abridged)
public function init( array $providers ): void {
    // 1. Register shared services
    $this->container->addShared( 'event_manager', fn() => new EventManager() );
    $this->container->addShared( 'filesystem',    fn() => new Imagify_Filesystem() );

    // 2. Include procedural files (functions/, common/, 3rd-party/)
    $this->include_files();

    // 3. Init legacy singletons
    Imagify_Auto_Optimization::get_instance()->init();
    Imagify_Options::get_instance()->init();
    Imagify_Data::get_instance()->init();
    Imagify_Folders_DB::get_instance()->init();
    Imagify_Files_DB::get_instance()->init();
    Imagify_Cron_Library_Size::get_instance()->init();
    Imagify_Cron_Rating::get_instance()->init();
    Imagify_Cron_Sync_Files::get_instance()->init();
    Imagify\Auth\Basic::get_instance()->init();
    Imagify\Job\MediaOptimization::get_instance()->init();
    Bulk::get_instance()->init();

    // 4. Admin-only classes
    if ( is_admin() ) { ... }

    // 5. Register PSR-4 service providers + subscribers
    foreach ( $providers as $service_provider ) {
        $this->container->addServiceProvider( new $service_provider() );
        $this->load_subscribers( $provider_instance );
    }

    do_action( 'imagify_loaded', $this );
}
```

### Activation / Deactivation Hooks

| Hook | Handler | What it does |
|------|---------|-------------|
| `register_activation_hook` | `imagify_set_activation()` | Sets transient `imagify_activation` with current user ID (TTL 30s). On network: `set_site_transient`. |
| `register_deactivation_hook` | `imagify_deactivation()` | Deletes `imagify_check_api_version` and `imagify_check_licence_1` site transients; fires `imagify_deactivation` action. |
| `init` (Plugin) | `Plugin::maybe_activate()` | Reads activation transient; fires `imagify_activation` action with user ID, then deletes transient. |

### Service Providers (`config/providers.php`)

| Provider | Description |
|----------|-------------|
| `Imagify\User\ServiceProvider` | Registers `User` singleton; binds account/quota service. |
| `Imagify\Admin\ServiceProvider` | AdminBar, PluginFamily, AdminSubscriber. |
| `Imagify\Avif\ServiceProvider` | AVIF rewrite-rule writers for Apache/Nginx/IIS. |
| `Imagify\CDN\ServiceProvider` | CDN push integration. |
| `Imagify\Picture\ServiceProvider` | Registers `Picture\Display` subscriber for `<picture>` tag rewriting. |
| `Imagify\Stats\ServiceProvider` | Stat counters (e.g. `OptimizedMediaWithoutNextGen`). |
| `Imagify\Webp\ServiceProvider` | WebP rewrite-rule writers. |
| `Imagify\ThirdParty\ServiceProvider` | GravityForms, Extendify; loads all `inc/3rd-party/` integrations. |
| `Imagify\Media\ServiceProvider` | Media subscribers, upload handler. |
| `Imagify\Tools\ServiceProvider` | Reset internal state tool, troubleshooting subscriber. |

---

## 2. Namespace & PSR-4 Structure

*Composer autoload map, directory layout, and naming conventions.*

### PSR-4 Autoload Map (`composer.json`)

| Namespace Prefix | Directory | Notes |
|-----------------|-----------|-------|
| `Imagify\` | `classes/` | Primary PSR-4 root for all modern classes |
| `Imagify\Deprecated\Traits\` | `inc/deprecated/Traits/` | Backward compat trait shims |
| `Imagify\ThirdParty\AS3CF\` | `inc/3rd-party/amazon-s3-and-cloudfront/classes/` | S3 Offload integration |
| `Imagify\ThirdParty\EnableMediaReplace\` | `inc/3rd-party/enable-media-replace/classes/` | Enable Media Replace compat |
| `Imagify\ThirdParty\FormidablePro\` | `inc/3rd-party/formidable-pro/classes/` | Formidable Forms compat |
| `Imagify\ThirdParty\NGG\` | `inc/3rd-party/nextgen-gallery/classes/` | NextGEN Gallery integration |
| `Imagify\ThirdParty\RegenerateThumbnails\` | `inc/3rd-party/regenerate-thumbnails/classes/` | Regenerate Thumbnails compat |
| `Imagify\ThirdParty\WPRocket\` | `inc/3rd-party/wp-rocket/classes/` | WP Rocket compat |

### Classmap (Legacy, Non-PSR-4)

`inc/classes/` and `inc/deprecated/classes/` are loaded via Composer classmap. The convention is `class-imagify-{name}.php` → `Imagify_{Name}`. Two files are explicitly excluded: `class-imagify-plugin.php` and `class-imagify-requirements-check.php` (loaded manually before autoloader is available).

### Key `classes/` Sub-namespaces

| Namespace | Classes |
|-----------|---------|
| `Imagify\Bulk\` | `Bulk`, `BulkInterface`, `AbstractBulk`, `WP`, `CustomFolders`, `Noop` |
| `Imagify\CLI\` | `AbstractCommand`, `BulkOptimizeCommand`, `RestoreCommand`, `GenerateMissingNextgenCommand` |
| `Imagify\Context\` | `ContextInterface`, `AbstractContext`, `WP`, `CustomFolders`, `Noop` |
| `Imagify\Media\` | `MediaInterface`, `AbstractMedia`, `WP`, `CustomFolders`, `Noop` |
| `Imagify\Optimization\` | `File`, `Process\{AbstractProcess, WP, CustomFolders, Noop}`, `Data\{AbstractData, WP, CustomFolders, Noop}` |
| `Imagify\Picture\` | `Display` (output buffer rewriter) |
| `Imagify\Job\` | `MediaOptimization` (background queue worker) |
| `Imagify\Tools\` | `InternalStateList`, `ResetInternalState`, `Subscriber` |
| `Imagify\Traits\` | `InstanceGetterTrait` (lightweight singleton), `MediaRowTrait` |

> **InstanceGetterTrait** provides `static::get_instance(): static` — a static singleton factory used by both PSR-4 classes and legacy `Imagify_*` classes. It stores the instance in `static::$_instance`.

---

## 3. Optimization Process — Class Hierarchy & Call Flow

*Full inheritance chain from context factory to per-file API call.*

### Class Inheritance Chain

```
ProcessInterface                          // classes/Optimization/Process/ProcessInterface.php
  └── AbstractProcess                     // classes/Optimization/Process/AbstractProcess.php (~2100 lines)
        ├── Process\WP                    // WP Media Library context
        ├── Process\CustomFolders         // Custom Folders context
        └── Process\Noop                  // No-op fallback

DataInterface                             // classes/Optimization/Data/DataInterface.php
  └── AbstractData
        ├── Data\WP                       // stores _imagify_data postmeta
        ├── Data\CustomFolders            // stores in imagify_files table
        └── Data\Noop

MediaInterface                            // classes/Media/MediaInterface.php
  └── AbstractMedia
        ├── Media\WP
        ├── Media\CustomFolders
        └── Media\Noop

ContextInterface                          // classes/Context/ContextInterface.php
  └── AbstractContext
        ├── Context\WP
        ├── Context\CustomFolders
        └── Context\Noop
```

### Context Factory Functions

```php
// inc/functions/common.php
imagify_get_context( string $context ): ContextInterface
imagify_get_optimization_process( int $media_id, string $context ): ProcessInterface

// Context values: 'wp' | 'custom-folders' | 'ngg' (when NGG active)
// Filterable via: imagify_context_class_name, imagify_process_class_name
```

### AbstractProcess — Key Method Signatures

| Method | Signature | Description |
|--------|-----------|-------------|
| `__construct` | `(int\|WP_Post\|MediaInterface $id)` | Accepts attachment ID, WP_Post, or MediaInterface object |
| `optimize` | `(?int $optimization_level, array $args = []): bool\|WP_Error` | Main entry for single-media optimization; acquires lock, iterates sizes |
| `reoptimize` | `(?int $optimization_level, array $args = []): bool\|WP_Error` | Restore then re-optimize at new level |
| `optimize_sizes` | `(array $sizes, ?int $level, array $args = []): bool\|WP_Error` | Push sizes to background job queue |
| `optimize_size` | `(string $size, ?int $level): bool\|WP_Error` | Optimize a single named size (e.g. `'full'`, `'thumbnail'`) |
| `optimize_missing_thumbnails` | `(): bool\|WP_Error` | Find and optimize sizes missing from postmeta |
| `restore` | `(): bool\|WP_Error` | Restore all sizes from backup; acquires restoring lock |
| `delete_backup` | `(): bool\|WP_Error` | Remove backup files for this media |
| `generate_nextgen_versions` | `(): bool\|WP_Error` | Generate WebP/AVIF variants for all optimized sizes |
| `delete_nextgen_files` | `(bool $keep_full = false, bool $all_next_gen = false): void` | Remove WebP/AVIF sidecar files |
| `lock` | `(string $action = 'optimizing'): void` | Set transient lock for 10 minutes |
| `unlock` | `(): void` | Delete lock transient |
| `is_locked` | `(): string\|false` | Returns lock action string or false |
| `update_size_optimization_data` | `(object $response, string $size, int $level): void` | Persist API response data for a size |

### Optimization Call Flow — Single Media

`AbstractProcess::optimize()` → `lock('optimizing')` → `get_sizes_to_optimize()` → `optimize_sizes($sizes, $level)` → `MediaOptimization::push_to_queue()` → `optimize_size($size)` → `File::optimize($args)` → `upload_imagify_image()` → `Imagify API POST /upload/` → `download_url(response->image)` → `filesystem->move()` → `update_size_optimization_data()` → `unlock()`

### Per-Size Data Structure Stored

```php
// Stored in _imagify_data['sizes'][$size_name] (WP context)
// On success:
[
    'success'        => true,
    'original_size'  => int,   // bytes before optimization
    'optimized_size' => int,   // bytes after optimization
    'percent'        => float, // savings percentage (2 decimal places)
]

// On error:
[
    'success' => false,
    'error'   => string,  // human-readable error message
]
```

---

## 4. Optimization\File — Method Signatures

*Low-level file operations: validation, resize, backup, API call, next-gen path generation.*

Class: `Imagify\Optimization\File` — `classes/Optimization/File.php` (931 lines).
Injected with `Imagify_Filesystem::get_instance()`. Does not extend anything — purely compositional.

### Constructor & Properties

```php
class File {
    protected string             $path;       // absolute path to file
    protected ?bool              $is_image;   // cached result of is_image()
    protected ?object            $file_type;  // {ext, type} from wp_check_filetype()
    protected Imagify_Filesystem $filesystem;
    protected mixed              $editor;    // WP_Image_Editor_Imagick|WP_Image_Editor_GD|WP_Error
    protected array              $options;   // cached get_imagify_option() calls

    public function __construct( string $file_path ) {...}
}
```

### Public Methods

| Method | Parameters → Return | Notes |
|--------|-------------------|-------|
| `is_valid()` | `→ bool` | Returns true if `$path` is non-empty |
| `can_be_processed()` | `→ true\|WP_Error` | Checks: path not empty, filesystem no errors, file exists, is a file, file writable, parent dir writable |
| `optimize(array $args)` | `→ stdClass\|WP_Error` | Calls backup(), then `upload_imagify_image()`, downloads result, moves to destination |
| `resize(array $dimensions, int $max_width)` | `→ string\|WP_Error` | Resizes via WP_Image_Editor; corrects EXIF orientation (cases 2–8); returns temp path |
| `create_thumbnail(array $destination)` | `→ bool\|array\|WP_Error` | Calls `$editor->multi_resize()`; moves to destination path |
| `backup(?string $backup_path, ?string $backup_source)` | `→ true\|false\|WP_Error` | Copies file to backup_path; also copies `-scaled` variant if exists |
| `is_exceeded()` | `→ bool` | Returns true if file size > `IMAGIFY_MAX_BYTES` (5 MB) |
| `is_supported(array $allowed_mime_types)` | `→ bool` | Checks MIME type against allow-list |
| `is_image()` | `→ bool` | MIME type starts with `image/` |
| `is_pdf()` | `→ bool` | MIME type is `application/pdf` |
| `is_webp()` | `→ bool` | Regex: `@(?!^|/|\)\.webp$@i` — rejects bare `.webp` |
| `is_avif()` | `→ bool` | Same pattern for `.avif` |
| `get_path()` | `→ string` | Current absolute path (may change post-conversion) |
| `get_path_to_webp()` | `→ string\|false` | Appends `.webp` to path; false if not an image or already WebP |
| `get_path_to_nextgen(string $format)` | `→ string\|false` | Appends `.webp` or `.avif`; false if already next-gen |
| `get_mime_type()` | `→ string` | From cached `wp_check_filetype()` |
| `get_extension()` | `→ string\|false` | File extension without dot |
| `get_dimensions()` | `→ array{width:int, height:int}` | Returns `[0,0]` if not image |

### `optimize()` — Args Array

```php
optimize( [
    'backup'             => true,    // false = skip backup regardless of user setting
    'backup_path'        => null,    // string — explicit backup destination path
    'backup_source'      => null,    // string — source to backup (WP 5.3+ original)
    'optimization_level' => 0,       // 0=normal/lossless, 1=aggressive, 2=ultra
    'convert'            => '',      // 'webp' | 'avif' | '' for original format
    'context'            => 'wp',   // sent to API for logging
    'original_size'      => 0,       // bytes, sent to API
] );
```

---

## 5. API Client — Endpoints & Request/Response

*HTTP transport, authentication, all endpoints, response schema, error handling.*

Class: `Imagify` (legacy classmap) — `inc/classes/class-imagify.php`. Singleton via `InstanceGetterTrait`.
Base URL: `IMAGIFY_APP_API_URL` = `https://app.imagify.io/api/`

### Authentication

```php
// Headers set in __construct() using stored API key
$this->all_headers['Accept']        = 'Accept: application/json';
$this->all_headers['Content-Type']  = 'Content-Type: application/json';
$this->all_headers['Authorization'] = 'Authorization: token ' . $this->api_key;

// upload_image() sends only Authorization header (multipart/form-data via cURL)
// All other endpoints send all three headers
```

### Transport Strategy

The private `http_call()` method auto-selects transport: if `$args['post_data']['image']` is set, it routes to `curl_http_call()` (direct cURL for multipart file uploads); otherwise uses WordPress `wp_remote_request()`. A `pre_imagify_request` filter allows short-circuiting the cURL path.

### All Endpoints

| Method | Endpoint | HTTP | Body / Response |
|--------|----------|------|----------------|
| `get_user()` | `users/me/` | GET | JSON: `{id, email, plan_id, plan_label, quota, extra_quota, extra_quota_consumed, consumed_current_month_quota, next_date_update, is_active, is_monthly}` |
| `create_user($data)` | `users/` | POST | JSON body; no auth header |
| `update_user($data)` | `users/me/` | PUT | JSON body with all headers |
| `get_status($data)` | `status/{$data}/` | GET | Cached in static array per type |
| `get_api_version()` | `version/` | GET | 5s timeout; cached in site transient |
| `get_public_info()` | `public-info` | GET | Marketing/public plan info |
| `upload_image($data)` | `upload/` | POST (cURL multipart) | `$data = ['image' => $path, 'data' => json_encode($opts)]`. Response: `{image: $url, ...}` |
| `fetch_image($data)` | `fetch/` | POST (JSON) | Optimize image from URL; same response shape as upload |
| `get_plans_prices()` | `pricing/plan/` | GET | Plan pricing objects |
| `get_all_prices()` | `pricing/all/` | GET | All pricing including packs |
| `check_coupon_code($coupon)` | `coupons/{$coupon}/` | GET | Coupon validity response |
| `check_discount()` | `pricing/discount/` | GET | Active discount info |

### Upload Request Body (multipart via cURL)

```php
// $data array passed to upload_image()
[
    'image' => '/absolute/path/to/image.jpg',  // CURLFile in cURL transport
    'data'  => json_encode([
        'normal'        => true/false,  // level === 0
        'aggressive'    => true/false,  // level === 1
        'ultra'         => true/false,  // level === 2
        'keep_exif'     => true,
        'original_size' => int,
        'context'       => string,     // 'wp' | 'custom-folders' | 'ngg'
        'convert'       => string,     // 'webp' | 'avif' — only when converting
    ]),
]
```

### API Response Shape (upload/fetch)

```json
// Success — stdClass
{
    "image": "https://app.imagify.io/...temp_url...",
    "original_size": 123456,
    "new_size": 98765,
    "percent": 19.87
}

// Error — WP_Error with code 'error {http_code}'
// HTTP 401 → invalid API key
// HTTP 413 → file too large
// HTTP 4xx/5xx → $response->detail or $response->image error array
```

### HTTP Response Handling

```php
private function handle_response( string $response, int $http_code, string $error = '' ) {
    $response = json_decode( $response );          // stdClass or null
    if ( 200 !== $http_code && !empty( $response->code ) ) {
        // $response->detail → WP_Error message
        // $response->image  → array of field errors
        return new WP_Error( 'error ' . $http_code, ... );
    }
    if ( ! is_object( $response ) ) {
        return new WP_Error( 'not_valid_json', ... );
    }
    return $response;
}
```

> **Timeout defaults:** `get_user()` and `get_status()` use 10s. `get_api_version()` uses 5s. All other calls default to 45s. Filterable via `imagify_api_http_request_timeout`.

---

## 6. WordPress Postmeta Keys & Data Structures

*Every key written to `wp_postmeta` by Imagify, with types and full schemas.*

### Primary Metadata Keys (WP Media Library)

All stored on the attachment post (`post_type = attachment`). Managed by `Imagify\Optimization\Data\WP`.

| Meta Key | Type | Values / Schema |
|----------|------|----------------|
| `_imagify_data` | Serialized array | `['sizes' => [...], 'stats' => [...], 'message' => string]` |
| `_imagify_status` | string | `'success'` \| `'already_optimized'` \| error string \| `''` (not optimized) |
| `_imagify_optimization_level` | int (stored as string) | `0` = normal/lossless, `1` = aggressive, `2` = ultra/smart |

### `_imagify_data` Full Schema

```php
// _imagify_data serialized array structure
[
    'sizes'  => [
        'full'      => [ 'success' => true, 'original_size' => int, 'optimized_size' => int, 'percent' => float ],
        'thumbnail' => [ 'success' => true, ... ],
        'medium'    => [ 'success' => false, 'error' => string ],
        // ... one entry per registered image size + any custom sizes
    ],
    'stats'  => [
        'original_size'  => int,    // sum across all successful sizes
        'optimized_size' => int,    // sum across all successful sizes
        'percent'        => float,  // aggregate % (2 decimal places)
    ],
    'message' => string,           // optional message from API (e.g. already optimized)
]
```

> `_imagify_status` and `_imagify_optimization_level` are written only when the `'full'` size is updated. They act as top-level fast-access keys mirroring `_imagify_data['sizes']['full']`.

### Standard WordPress Keys (also modified by Imagify)

| Meta Key | Modified When |
|----------|-------------|
| `_wp_attachment_metadata` | After resize (adds/removes sizes array entries); after thumbnail generation (updates width/height); after WP 5.3 original file handling |
| `_wp_attached_file` | Not directly modified; read to resolve absolute paths |

---

## 7. Settings — Option Keys, Types & Defaults

*All values stored under a single serialized option. Class: `Imagify_Options` (`inc/classes/class-imagify-options.php`).*

Option name: `imagify_settings` (single site) or `imagify_settings` stored via `get_site_option` (network). Set via `get_imagify_option($key)` / `update_imagify_option($key, $value)`.

| Key | Type | Default | Reset Value | Description |
|-----|------|---------|-------------|-------------|
| `api_key` | string | `''` | — | Imagify API key. Overridable via PHP constant `IMAGIFY_API_KEY`. |
| `optimization_level` | int | `2` | `2` | 0=lossless, 1=aggressive, 2=ultra |
| `lossless` | int (bool) | `0` | — | Force level 0 for all optimizations |
| `auto_optimize` | int (bool) | `0` | `1` | Auto-optimize on upload |
| `backup` | int (bool) | `0` | `1` | Keep backup of originals |
| `resize_larger` | int (bool) | `0` | `1` (if WP 5.3+) | Resize images larger than threshold |
| `resize_larger_w` | int | `0` | From `big_image_size_threshold` filter (default 2560) | Max width in pixels for resize |
| `display_nextgen` | int (bool) | `0` | — | Enable next-gen format delivery |
| `display_nextgen_method` | string | `'picture'` | — | `'picture'` = HTML rewrite; `'rewrite'` = server-side rules |
| `display_webp` | int (bool) | `0` | — | Legacy WebP delivery toggle |
| `display_webp_method` | string | `'picture'` | — | Legacy WebP method selector |
| `cdn_url` | string | `''` | — | CDN base URL for URL→path resolution |
| `disallowed-sizes` | array | `[]` | — | Size names excluded from optimization |
| `admin_bar_menu` | int (bool) | `1` | `1` | Show Imagify in admin bar |
| `partner_links` | int (bool) | `0` | `1` | Show partner links in plugin UI |
| `convert_to_avif` | int (bool) | `0` | — | Generate AVIF sidecar files |
| `convert_to_webp` | int (bool) | `0` | — | Generate WebP sidecar files |
| `optimization_format` | string | `'webp'` | — | `'webp'` \| `'avif'` \| `'off'` |

> The `reset_values` array in `Imagify_Options` contains only keys that differ from defaults; it is applied on first install or explicit reset. The option is a single serialized blob — never stored as individual keys.

---

## 8. Database Schemas

*Complete DDL for all three custom tables: `imagify_folders`, `imagify_files`, and `ngg_imagify_data`.*

### `imagify_folders`

Class: `Imagify_Folders_DB` (`inc/classes/class-imagify-folders-db.php`). Global table in multisite (`$wpdb->base_prefix`). Table version: `100`.

```sql
CREATE TABLE `{prefix}imagify_folders` (
    `folder_id`  bigint(20) unsigned NOT NULL auto_increment,
    `path`       varchar(191)        NOT NULL default '',
    `active`     tinyint(1) unsigned NOT NULL default 0,
    PRIMARY KEY  (folder_id),
    UNIQUE KEY   path (path),
    KEY          active (active)
);
```

| Column | Type | Description |
|--------|------|-------------|
| `folder_id` | bigint unsigned PK | Auto-increment primary key |
| `path` | varchar(191) UNIQUE | Absolute path with placeholder: `{{ROOT}}/wp-content/uploads/gallery/`. Uses `{{ROOT}}` and `{{ABSPATH}}` tokens for portability. |
| `active` | tinyint(1) | `1` = selected in settings; `0` = deactivated. Indexed for fast active-folder queries. |

### `imagify_files`

Class: `Imagify_Files_DB` (`inc/classes/class-imagify-files-db.php`). Global table in multisite. Table version: `102`.

```sql
CREATE TABLE `{prefix}imagify_files` (
    `file_id`            bigint(20) unsigned  NOT NULL auto_increment,
    `folder_id`          bigint(20) unsigned  NOT NULL default 0,
    `file_date`          datetime             NOT NULL default '0000-00-00 00:00:00',
    `path`               varchar(191)         NOT NULL default '',
    `hash`               varchar(32)          NOT NULL default '',   -- MD5 of file
    `mime_type`          varchar(100)         NOT NULL default '',
    `modified`           tinyint(1) unsigned  NOT NULL default 0,
    `width`              smallint(2) unsigned NOT NULL default 0,
    `height`             smallint(2) unsigned NOT NULL default 0,
    `original_size`      int(4) unsigned      NOT NULL default 0,
    `optimized_size`     int(4) unsigned      default NULL,
    `percent`            smallint(2) unsigned default NULL,
    `optimization_level` tinyint(1) unsigned  default NULL,
    `status`             varchar(20)          default NULL,
    `error`              varchar(255)         default NULL,
    `data`               longtext             default NULL,   -- serialized
    PRIMARY KEY  (file_id),
    UNIQUE KEY   path (path),
    KEY          folder_id (folder_id),
    KEY          optimization_level (optimization_level),
    KEY          status (status),
    KEY          modified (modified)
);
```

| Column | Notes |
|--------|-------|
| `folder_id` | FK reference to `imagify_folders.folder_id` (not enforced at DB level) |
| `path` | Absolute path using same `{{ROOT}}` tokens as folders table |
| `hash` | MD5 hash of file contents — used by `refresh_file()` to detect modifications |
| `modified` | `1` when file has changed since last optimization (hash mismatch) |
| `status` | `'success'` \| `'already_optimized'` \| `'error'` \| NULL (not yet processed) |
| `data` | Serialized array — same shape as `_imagify_data` (sizes + stats) |

### `ngg_imagify_data` (NextGEN Gallery)

Class: `Imagify\ThirdParty\NGG\DB` (`inc/3rd-party/nextgen-gallery/classes/DB.php`). Per-site table (`$wpdb->prefix`). Table version: `100`.

```sql
CREATE TABLE `{prefix}ngg_imagify_data` (
    `data_id`            bigint(20) unsigned NOT NULL auto_increment,
    `pid`                bigint(20) unsigned NOT NULL default 0,
    `optimization_level` varchar(1)          NOT NULL default '',
    `status`             varchar(30)         NOT NULL default '',
    `data`               longtext            default NULL,
    PRIMARY KEY  (data_id),
    KEY          pid (pid)
);
```

`pid` is the NextGEN picture ID. `data` is serialized the same way as `_imagify_data`.

### Abstract DB Base Class

All three DB classes extend `Imagify_Abstract_DB` which implements `Imagify\DB\DBInterface`. It provides:

**Table Management**
- `maybe_upgrade_table()` — create/upgrade on plugin init
- `create_table()` — issues `dbDelta()`
- `can_operate(): bool` — true when table is ready
- Version stored in option: `{option_prefix}_db_version`

**CRUD Methods**
- `get($id)`, `get_by($col, $val)`, `get_in($col, $vals)`
- `get_var($col, $where)`, `get_column_in($col, $ids)`
- `insert($data)`, `update($data, $where)`, `delete($id)`
- Auto-serialize array columns before insert/update via `serialize_columns()`
- Auto-cast results via `cast_row()` based on column type map

---

## 9. Bulk Optimization — ActionScheduler Integration

*How bulk jobs are enqueued, tracked, and completed via ActionScheduler async actions.*

Class: `Imagify\Bulk\Bulk` (`classes/Bulk/Bulk.php`). Singleton. Registered hooks in `init()`.

### Bulk Run Flow

`AJAX: imagify_bulk_optimize` → `bulk_optimize_callback()` → `run_optimize($context, $level)` → `get_unoptimized_media_ids()` → `as_enqueue_async_action() ×N` → `set_transient 'running'` → `ActionScheduler fires 'imagify_optimize_media'` → `optimize_media($id, $ctx, $lvl)` → `check_optimization_status()`

### ActionScheduler Job Enqueue

```php
// Bulk::run_optimize() — one as_enqueue_async_action() per media
as_enqueue_async_action(
    'imagify_optimize_media',
    [
        'id'      => (int) $media_id,
        'context' => (string) $context,      // 'wp' | 'custom-folders'
        'level'   => (int) $optimization_level,
    ],
    "imagify-{$context}-optimize-media"   // group name — allows cancellation per context
);

// Next-gen generation uses a separate hook
as_enqueue_async_action(
    'imagify_convert_next_gen',
    [ 'id' => $media_id, 'context' => $context ],
    "imagify-{$context}-convert-nextgen"
);
```

### Progress Tracking Transients

| Transient | Set When | Shape | TTL |
|-----------|---------|-------|-----|
| `imagify_wp_optimize_running` | Start of WP library bulk run | `['total' => int, 'remaining' => int]` | DAY_IN_SECONDS |
| `imagify_custom-folders_optimize_running` | Start of custom-folders bulk run | `['total' => int, 'remaining' => int]` | DAY_IN_SECONDS |
| `imagify_bulk_optimization_result` | After each successful optimization | `['total' => int, 'original_size' => int, 'optimized_size' => int]` | DAY_IN_SECONDS |
| `imagify_bulk_optimization_complete` | When remaining reaches 0 | `1` | DAY_IN_SECONDS |
| `imagify_missing_next_gen_total` | Start of next-gen generation run | `int` (total count) | HOUR_IN_SECONDS |
| `imagify_bulk_optimization_infos` | User dismisses info popup | `1` | WEEK_IN_SECONDS |

### ActionScheduler Job Lifecycle

ActionScheduler is bundled at `inc/Dependencies/ActionScheduler/action-scheduler.php`. Jobs go through: `pending` → `in-progress` → `complete`

On failure: **failed**. On cancel: **canceled**. The `check_optimization_status()` hook fires on `imagify_after_optimize` and decrements the running counter, deleting the transient and setting the complete transient when all jobs finish.

### Multisite Context Routing

```php
// Bulk::get_contexts() — determines which contexts appear on bulk page
if ( ! is_network_admin() ) {
    $types['library|wp'] = 1;                // library only in site admin
}
if ( imagify_is_active_for_network() && is_network_admin() ) {
    $types['custom-folders|custom-folders'] = 1;  // custom folders in network admin
} elseif ( ! imagify_is_active_for_network() ) {
    $types['custom-folders|custom-folders'] = 1;  // custom folders in site admin
}
```

---

## 10. Concurrency & Locking Mechanisms

*Transient-based per-media locks that prevent duplicate concurrent optimization or restore jobs.*

### Per-Media Process Lock

Defined in `AbstractProcess`. Each lock is a transient named after the context and media ID.

```php
// Transient name pattern (LOCK_NAME constant)
const LOCK_NAME = 'imagify_%1$s_%2$s_process_locked';
// Example: 'imagify_wp_42_process_locked'
// Example: 'imagify_custom-folders_7_process_locked'

// Network-aware: uses set_site_transient when context is_network_wide()
public function lock( string $action = 'optimizing' ): void {
    $name     = $this->get_lock_name();           // sprintf(LOCK_NAME, ctx, id)
    $callback = $media->get_context_instance()->is_network_wide()
        ? 'set_site_transient' : 'set_transient';
    call_user_func( $callback, $name, $action, 10 * MINUTE_IN_SECONDS );
}

public function is_locked(): string|false {
    // Returns 'optimizing' | 'restoring' | false
    $callback = ...'get_site_transient' or 'get_transient'...;
    $action   = call_user_func( $callback, $name );
    return $this->validate_lock_action( $action );  // normalizes 'restore' → 'restoring'
}

public function unlock(): void {
    $callback = ...'delete_site_transient' or 'delete_transient'...;
    call_user_func( $callback, $name );
}
```

### Lock Actions

| Value | Set By | Cleared By |
|-------|--------|-----------|
| `'optimizing'` | `optimize()` before iterating sizes | `optimize()` after all sizes complete |
| `'restoring'` | `restore()` at start | `restore()` at end (success or error) |

### Transient Name Summary for Cleanup

```
'_transient_%imagify-auto-optimize-%'      // Legacy (deprecated)
'_transient_%imagify_rpc_%'                // Legacy (deprecated)
'_transient_imagify_%_process_locked'      // Active single-site locks
'_site_transient_imagify_%_process_lock%'  // Active network-wide locks
```

> **TTL is 10 minutes.** If a PHP process dies mid-optimization, the lock expires automatically. The `Reset Internal State` admin tool can force-clear all locks immediately via direct SQL DELETE.

---

## 11. Picture\Display — Output Buffer HTML Rewrite

*How `<img>` tags are rewritten to `<picture>` tags at the HTTP response level.*

Class: `Imagify\Picture\Display` (`classes/Picture/Display.php`). Implements `SubscriberInterface`.

### Subscribed Events

```php
public static function get_subscribed_events(): array {
    return [
        'template_redirect'            => 'start_content_process',
        'imagify_process_webp_content' => 'process_content',
    ];
}
```

### Full Rewrite Pipeline

`template_redirect` → `start_content_process()` → `ob_start([this, 'maybe_process_buffer'])` → `PHP renders full page HTML` → `maybe_process_buffer($buffer)` → `is_html() check (must contain </html> and be >255 chars)` → `process_content($buffer)` → `remove_picture_tags() — strip existing <picture> wrappers` → `get_images() — regex extract all <img> tags` → `process_image() per tag` → `filesystem->exists() check for .webp/.avif sidecars` → `build_picture_tag() → str_replace() in buffer`

### Guards in `start_content_process()`

- `get_imagify_option('display_nextgen')` must be truthy
- `get_imagify_option('display_nextgen_method')` must equal `'picture'` (`Display::OPTION_VALUE`)
- Filter `imagify_allow_picture_tags_for_nextgen` must return true

### Lazy-Load Support

The parser checks these src attributes in priority order: `data-lazy-src` → `data-src` → `src`. Likewise for srcset: `data-lazy-srcset` → `data-srcset` → `srcset`. The generated `<source>` tag mirrors whichever attribute was active.

### Generated HTML Structure

```html
<!-- Input -->
<img src="/uploads/photo.jpg" srcset="/uploads/photo-300.jpg 300w" sizes="..." alt="...">

<!-- Output (when both AVIF and WebP exist) -->
<picture>
  <source type="image/avif" srcset="/uploads/photo.jpg.avif, /uploads/photo-300.jpg.avif 300w" sizes="...">
  <source type="image/webp" srcset="/uploads/photo.jpg.webp, /uploads/photo-300.jpg.webp 300w" sizes="...">
  <img src="/uploads/photo.jpg" srcset="/uploads/photo-300.jpg 300w" sizes="..." alt="...">
</picture>
```

### URL → Path Resolution

`url_to_path()` converts image URLs to filesystem paths for existence checks. It handles: uploads URL, site root URL, CDN URL (via `imagify_cdn_source_url` filter), and protocol-relative URLs. Static caches are maintained per request.

### Filters on the Rewrite Path

| Filter | Signature | Purpose |
|--------|-----------|---------|
| `imagify_allow_picture_tags_for_nextgen` | `(bool $allow): bool` | Global on/off switch for the rewriter |
| `imagify_webp_picture_images_to_display` | `(array $images, string $content): array` | Filter/add/remove images before rewriting |
| `imagify_webp_picture_process_image` | `(array $data, string $img_tag): array\|false` | Per-image data manipulation (used by S3 Offload integration) |
| `imagify_picture_attributes` | `(array $attributes, array $data): array` | Attributes on the `<picture>` element |
| `imagify_picture_source_attributes` | `(array $attributes, array $data): array` | Attributes on each `<source>` element |
| `imagify_picture_img_attributes` | `(array $attributes, array $data): array` | Attributes on the fallback `<img>` |
| `imagify_additional_source_tags` | `(string $html, array $data): string` | Inject extra `<source>` elements before the generated ones |
| `imagify_buffer` | `(string $buffer): string` | Final buffer after all replacements |
| `imagify_cdn_source_url` | `(string $url): string` | CDN base URL for URL-to-path mapping |

---

## 12. AJAX & Admin-Post — Full Security Table

*Every `wp_ajax_*` and `admin_post_*` action with its nonce name and capability requirement.*

Security is enforced by `imagify_check_nonce($action, $query_arg)` which wraps `check_ajax_referer()` and calls `imagify_die()` on failure. Capability checks use `imagify_get_context($ctx)->current_user_can($capability, $media_id)`.

### `wp_ajax_*` + `admin_post_*` (both)

| Action | Nonce Name | Capability | Description |
|--------|-----------|-----------|-------------|
| `imagify_manual_optimize` | `imagify-optimize-{id}-{ctx}` | `manual-optimize` | Optimize single attachment |
| `imagify_manual_reoptimize` | `imagify-manual-reoptimize-{id}-{ctx}` | `manual-optimize` | Re-optimize at different level |
| `imagify_optimize_missing_sizes` | `imagify-optimize-missing-sizes-{id}-{ctx}` | `manual-optimize` | Generate missing thumbnail sizes |
| `imagify_generate_nextgen_versions` | `imagify-generate-nextgen-versions-{id}-{ctx}` | `manual-optimize` | Generate WebP/AVIF for one attachment |
| `imagify_delete_nextgen_versions` | `imagify-delete-nextgen-versions-{id}-{ctx}` | `manual-restore` | Remove WebP/AVIF sidecar files |
| `imagify_restore` | `imagify-restore-{id}-{ctx}` | `manual-restore` | Restore attachment from backup |
| `imagify_optimize_file` | `imagify_optimize_file` | `manual-optimize` (custom-folders ctx) | Optimize custom folder file |
| `imagify_reoptimize_file` | `imagify_reoptimize_file` | `manual-optimize` (custom-folders ctx) | Re-optimize custom folder file |
| `imagify_restore_file` | `imagify_restore_file` | `manual-restore` (custom-folders ctx) | Restore custom folder file from backup |
| `imagify_refresh_file_modified` | `imagify_refresh_file_modified` | `manual-optimize` (custom-folders ctx) | Refresh file hash/modified status |

### `wp_ajax_*` Only

| Action | Nonce Name | Capability / Check | Description |
|--------|-----------|-------------------|-------------|
| `imagify_bulk_optimize` | `imagify-bulk-optimize` | `bulk-optimize` | Launch ActionScheduler bulk job |
| `imagify_missing_nextgen_generation` | `imagify-bulk-optimize` | `bulk-optimize` per context | Generate all missing next-gen files |
| `imagify_get_folder_type_data` | `imagify-bulk-optimize` | `bulk-optimize` | Stats for one folder type on bulk page |
| `imagify_bulk_info_seen` | `imagify-bulk-optimize` | `bulk-optimize` | Set `imagify_bulk_optimization_infos` transient |
| `imagify_bulk_get_stats` | `imagify-bulk-optimize` | `bulk-optimize` per folder type | Aggregate bulk page statistics |
| `imagify_reset_internal_state` | `imagify_reset_internal_state` | `manage` (wp ctx) | Clear all locks, transients, AS jobs |
| `imagify_check_backup_dir_is_writable` | `imagify_check_backup_dir_is_writable` | `manage` (wp ctx) | Test backup directory writability |
| `imagify_get_files_tree` | `get-files-tree` | `manage` (custom-folders ctx) | Filesystem tree for folder picker |
| `imagify_signup` | `imagify-signup` | `manage` (wp ctx) | Create Imagify account |
| `imagify_check_api_key_validity` | `imagify-check-api-key` | `manage` | Validate API key against API |
| `imagify_get_prices` | `imagify_get_pricing_{user_id}` | `manage` | Fetch plan prices |
| `imagify_check_coupon` | `imagify_get_pricing_{user_id}` | `manage` | Validate coupon code |
| `imagify_get_discount` | `imagify_get_pricing_{user_id}` | `manage` | Check active discount |
| `imagify_get_images_counts` | `imagify_get_pricing_{user_id}` | `manage` | Count images per status |
| `imagify_update_estimate_sizes` | `update_estimate_sizes` | `manage` | Recalculate size estimates |
| `imagify_get_user_data` | `imagify_get_user_data` | `manage` | Fetch fresh account data from API |
| `imagify_delete_user_data_cache` | `imagify_delete_user_data_cache` | `manage` | Purge cached user data transient |
| `nopriv_imagify_rpc` | `imagify_rpc_{rpc_id}` | None (nonce only) | Internal RPC dispatch |

### `admin_post_*` Only

| Action | Nonce Name | Capability |
|--------|-----------|-----------|
| `imagify_scan_custom_folders` | `imagify_scan_custom_folders` | `optimize` (custom-folders ctx) |
| `imagify_dismiss_ad` | `imagify-dismiss-ad` | `manage` (wp ctx) |
| `imagify_dismiss_notice` | `imagify-dismiss-notice` | Varies per notice |
| `imagify_deactivate_plugin` | `imagify-deactivate-plugin` | Varies per notice |
| `imagify_rollback` | `imagify_rollback` | `manage_options` |

> Nonces with `{id}` and `{ctx}` are unique per media item and context (e.g. `imagify-optimize-42-wp`). This prevents CSRF replay across different media items.

---

## 13. WP-CLI Commands — Full Signatures

See section 9 in [imagify-summary.md](imagify-summary.md) for usage details.

### `wp imagify bulk-optimize`
- **Context:** `wp`, `custom-folders` (default: `wp`)
- **Options:** `--optimization-level=<0|1|2>`
- **Execution:** Asynchronous via ActionScheduler

### `wp imagify restore`
- **Context:** `library`, `custom-folders`
- **Execution:** Synchronous (blocking)
- **Returns:** success count, error count, total

### `wp imagify generate-missing-nextgen`
- **Context:** all optimized images across contexts
- **Execution:** Asynchronous via ActionScheduler

---

## 14. Developer Hooks — Exact Signatures & Parameter Types

See section 13 in [imagify-summary.md](imagify-summary.md) for the full table. Below are additional filter signatures from the Picture\Display system.

| Filter | Full Signature | Notes |
|--------|---------------|-------|
| `imagify_allow_picture_tags_for_nextgen` | `apply_filters('imagify_allow_picture_tags_for_nextgen', bool $allow)` | Return `false` to disable `<picture>` rewriting globally |
| `imagify_picture_attributes` | `apply_filters('imagify_picture_attributes', array $attr, array $data)` | `$data` contains `src`, `srcset`, `sizes`, `img_tag` |
| `imagify_buffer` | `apply_filters('imagify_buffer', string $html)` | Full page HTML after all replacements |
| `imagify_register_context` | `apply_filters('imagify_register_context', array $contexts)` | Register a custom context; key = context slug, value = class name |
| `imagify_backup_directory` | `apply_filters('imagify_backup_directory', string $path, int $attachment_id)` | Override per-attachment backup directory |
| `imagify_api_http_request_timeout` | `apply_filters('imagify_api_http_request_timeout', int $timeout, string $endpoint)` | Override API request timeout in seconds |
| `imagify_event_recurrence` | `apply_filters('imagify_event_recurrence', string $recurrence, string $event)` | Change cron job recurrence (e.g. `'daily'`, `'hourly'`) |

---

## 15. Scheduled Tasks — Cron & ActionScheduler

See section 12 in [imagify-summary.md](imagify-summary.md) for the cron table.

### ActionScheduler Table

ActionScheduler stores its job queue in `{prefix}actionscheduler_actions`. Key columns relevant to Imagify:

| Column | Notes |
|--------|-------|
| `hook` | `'imagify_optimize_media'` or `'imagify_convert_next_gen'` |
| `group` | `'imagify-wp-optimize-media'`, `'imagify-custom-folders-optimize-media'`, etc. |
| `args` | JSON: `{"id": 42, "context": "wp", "level": 2}` |
| `status` | `'pending'` \| `'in-progress'` \| `'complete'` \| `'failed'` \| `'canceled'` |

All Imagify AS jobs are **async actions** (`as_enqueue_async_action()`), not scheduled recurring jobs.

---

## 16. Multisite Handling

- Plugin can be network-activated (`imagify_is_active_for_network()` → true).
- When network-activated, settings are stored in `wp_sitemeta` via `get_site_option`.
- `imagify_folders` and `imagify_files` tables use `$wpdb->base_prefix` (global tables shared across sites).
- `ngg_imagify_data` uses `$wpdb->prefix` (per-site).
- Locks use `set_site_transient` when `$context->is_network_wide()` is true.
- Bulk page shows `custom-folders` context in network admin; `library` context only in site admin.
- Quota is shared across all sites on the same API key.

---

## 17. NextGEN Gallery Integration

Automatically detected via `class_exists('C_Gallery_Storage')`. Activates `Imagify\Context\NGG` and `Imagify\ThirdParty\NGG\DB`.

**Compatibility check:** `imagify_ngg_has_pope_storage()` verifies NGG v4.x is present before activating deep integration.

**Data storage:** `{prefix}ngg_imagify_data` (per-site, see schema in section 8).

**Bulk context:** registered as `'ngg'`. Appears on the bulk page when NextGEN Gallery is active.

---

## 18. Third-Party Integrations

All loaded by `Imagify\ThirdParty\ServiceProvider` which scans `inc/3rd-party/` on boot.

| Plugin | Integration Path | What it does |
|--------|-----------------|-------------|
| WooCommerce | `inc/3rd-party/woocommerce/` | Fixes `wp-post-image` class on `<picture>` tags for product image switching |
| WP Rocket | `inc/3rd-party/wp-rocket/classes/` | PSR-4 compat shim |
| Gravity Forms | `Imagify\ThirdParty\ServiceProvider` | Registers Gravity Forms upload folder as custom folder context |
| Formidable Pro | `inc/3rd-party/formidable-pro/classes/` | Same approach as Gravity Forms |
| Yoast SEO | `inc/3rd-party/` | Ensures Open Graph images are optimized |
| AMP | `inc/3rd-party/` | Disables `<picture>` rewriter on AMP pages |
| Enable Media Replace | `inc/3rd-party/enable-media-replace/classes/` | Re-optimizes on media replacement |
| Regenerate Thumbnails | `inc/3rd-party/regenerate-thumbnails/classes/` | Re-triggers optimization after thumbnail regeneration |
| Amazon S3 / CloudFront | `inc/3rd-party/amazon-s3-and-cloudfront/classes/` | S3 URL resolution for `url_to_path()` |
| Cloudflare Super Page Cache | `inc/3rd-party/` | Cache purge on next-gen file generation |

---

## 19. Quota & Account Management

See section 14 in [imagify-summary.md](imagify-summary.md).

**`plan_id` mapping:**

| plan_id | Plan |
|---------|------|
| `1` | Free |
| `15` | Infinite (annual) |
| `16` | Growth (monthly) |
| `17` | Infinite (monthly) |
| `18` | Growth (annual) |

**Quota block:** `is_over_quota()` returns true when `consumed_current_month_quota >= quota` on the Free plan. Optimization is rejected at the `Imagify\Optimization\File::optimize()` level.

**Transient:** `imagify_user_cache` — TTL 5 minutes. Cleared on: API key change, account signup, `imagify_delete_user_data_cache` AJAX action.

---

## 20. Roles & Capabilities

| Capability | WP Capability | Defined In |
|-----------|--------------|-----------|
| `manage` | `manage_options` | `AbstractContext::get_capacity()` |
| `optimize` | `upload_files` | `AbstractContext::get_capacity()` |
| `manual-optimize` | `upload_files` | `AbstractContext::get_capacity()` |
| `manual-restore` | `upload_files` | `AbstractContext::get_capacity()` |
| `bulk-optimize` | `manage_options` | `AbstractContext::get_capacity()` |

Filter: `option_page_capability_imagify` — override any capability mapping.

---

## 21. Troubleshooting Tools — InternalStateList & Reset

### `Imagify\Tools\InternalStateList`

Single source of truth for all lockable/clearable state. Used by both `ResetInternalState` (admin tool) and `uninstall.php` (full cleanup).

**Methods:**
- `get_transients(): array` — list of transient keys to delete
- `get_locked_transient_patterns(): array` — LIKE patterns for SQL bulk delete of locks
- `get_action_scheduler_hooks(): array` — AS hook names to cancel all pending jobs

### AJAX Action: `imagify_reset_internal_state`

Requires `manage` capability. Steps:
1. Delete all transients listed by `get_transients()`
2. SQL `DELETE FROM wp_options WHERE option_name LIKE '_transient_%imagify...'` for each lock pattern
3. `as_unschedule_all_actions('imagify_optimize_media')`
4. `as_unschedule_all_actions('imagify_convert_next_gen')`
5. Returns JSON `{success: true}`

---

## 22. Error Handling Paths

- **`WP_Error` throughout** — all `optimize()`, `restore()`, `File::optimize()` return `WP_Error` on failure, never throw exceptions.
- **API errors** — `handle_response()` converts non-200 HTTP codes to `WP_Error` with code `'error {http_code}'` and the API's `detail` field as the message.
- **File system errors** — `can_be_processed()` returns `WP_Error` with specific codes: `'file_not_exists'`, `'file_not_writable'`, `'dir_not_writable'`.
- **Lock conflicts** — `optimize()` returns early (`false`) if `is_locked()` is truthy; does not return `WP_Error`.
- **Quota exceeded** — returns `WP_Error` with code `'over_quota'` before hitting the API.
- **Size exceeds 5 MB** — `is_exceeded()` triggers `WP_Error` with code `'file_too_big'`.

---

## 23. Filesystem Operations & Paths

All filesystem operations go through `Imagify_Filesystem` (wraps `WP_Filesystem`). Never uses `file_put_contents()` or `fopen()` directly.

**Key path helpers:**
- `imagify_get_upload_basedir()` — returns `wp_upload_dir()['basedir']` with trailing slash
- `imagify_get_upload_baseurl()` — returns `wp_upload_dir()['baseurl']`
- `imagify_get_backup_dir()` — returns `{upload_basedir}backup/imagify/`; filterable via `imagify_backup_directory`
- `imagify_get_filesystem()` — returns `Imagify_Filesystem::get_instance()`

**Next-gen sidecar paths:**
- WebP: `{original_path}.webp` (e.g. `photo.jpg.webp`)
- AVIF: `{original_path}.avif` (e.g. `photo.jpg.avif`)
- Note: sidecar files are never `.webp` or `.avif` as standalone extensions — the original extension is preserved as a prefix.

---

*Imagify v2.2.8 · wp-media/imagify-plugin · Engineering Deep Dive*
