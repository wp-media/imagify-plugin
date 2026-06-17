# WP-CLI Commands

Imagify registers WP-CLI subcommands under the `imagify` root command.
All commands are registered in `classes/Plugin.php` via `imagify_add_command()`.

---

## `wp imagify bulk-optimize <contexts...>`

Enqueues asynchronous ActionScheduler jobs to optimize all unoptimized images
for one or more bulk contexts.

**Class:** `Imagify\CLI\BulkOptimizeCommand`

### Arguments

| Name | Required | Repeating | Description |
|---|---|---|---|
| `contexts` | yes | yes | One or more contexts to run. Valid values: `wp`, `custom-folders`. |

### Options

| Option | Default | Description |
|---|---|---|
| `--optimization-level` | account default | `0` (normal), `1` (aggressive), `2` (ultra). |

### Behaviour

Delegates to `Imagify\Bulk\Bulk::run_optimize()`, which calls
`get_unoptimized_media_ids()` on the matching `AbstractBulk` subclass and
enqueues one `imagify_optimize_media` ActionScheduler action per ID.

A warning is emitted via `WP_CLI::warning()` if a context has no images to optimize.

---

## `wp imagify restore [<contexts>...]`

Synchronously restores all optimized images back to their original files
for one or more bulk contexts.

**Class:** `Imagify\CLI\RestoreCommand`

**Since:** 2.3 (issue #922)

### Arguments

| Name | Required | Repeating | Description |
|---|---|---|---|
| `contexts` | no | yes | One or more contexts to restore. Valid values: `library`, `custom-folders`. Defaults to both if omitted. |

`library` is an alias for the WordPress media library (`wp` context).

### Examples

```bash
# Restore all optimized images (library + custom folders).
wp imagify restore

# Restore only the WordPress media library.
wp imagify restore library

# Restore only custom folders.
wp imagify restore custom-folders

# Restore both contexts explicitly.
wp imagify restore library custom-folders
```

### Behaviour

1. For each context, calls `Imagify\Bulk\Bulk::run_restore( $context )`.
2. `run_restore()` calls `get_optimized_media_ids()` on the matching `AbstractBulk`
   subclass. Only images that have a backup file on disk are included.
3. For each eligible image, calls `imagify_get_optimization_process( $media_id, $context )->restore()` **synchronously** (no ActionScheduler queue).
4. Prints per-context counts (restored / errors / total) and a final success or warning message.

A `WP_CLI::warning()` is emitted if:
- A context has no eligible images.
- The overall restore completed but some images failed.

`WP_CLI::success()` is emitted only when all processed images were restored without errors.

### Return structure from `Bulk::run_restore()`

| Key | Type | Description |
|---|---|---|
| `success` | bool | `false` if no eligible images were found; `true` otherwise. |
| `message` | string | `no-images` or `success`. |
| `restored` | int | Number of images successfully restored. |
| `errors` | int | Number of images that failed to restore. |
| `total` | int | Total number of images processed. |

---

## Context resolution

Both bulk commands resolve the concrete `AbstractBulk` subclass through the
`imagify_bulk_class_name` filter (applied via `wpm_apply_filters_typed()`).
The default mapping is:

| Context | Class |
|---|---|
| `wp` | `Imagify\Bulk\WP` |
| `custom-folders` | `Imagify\Bulk\CustomFolders` |

---

## `get_optimized_media_ids()` — context implementations

`BulkInterface::get_optimized_media_ids(): array` returns a flat list of media IDs
(integers) that are eligible for restoration:

- **`Imagify\Bulk\WP`** — queries `wp_posts` INNER JOIN `wp_postmeta` for
  attachments with `_imagify_status` IN `('success', 'already_optimized')`, then
  filters to only those with an existing backup file (`get_imagify_attachment_backup_path()`).
- **`Imagify\Bulk\CustomFolders`** — queries `imagify_files` INNER JOIN
  `imagify_folders` for files with `status IN ('success', 'already_optimized')`,
  then filters to only those with an existing backup file
  (`Imagify_Custom_Folders::get_file_backup_path()`).
- **`Imagify\Bulk\Noop`** and **`Imagify\ThirdParty\NGG\Bulk\NGG`** — return `[]`
  (restore is not supported for these contexts).
