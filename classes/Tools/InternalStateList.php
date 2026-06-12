<?php
/**
 * AUTOLOADER-SAFE FILE — DO NOT ADD declare(strict_types=1) OR use IMPORTS.
 *
 * This file is require_once'd directly from uninstall.php, which runs BEFORE
 * the PSR-4 autoloader is registered. Any top-level `use` statement or
 * `declare(strict_types=1)` directive is safe on its own, but `use` imports
 * referencing unavailable classes cause a fatal PHP error at that early stage.
 *
 * Rules for this file:
 *   - NO declare(strict_types=1) at the top level.
 *   - NO use import statements of any kind.
 *   - Only a namespace declaration and a class with public static methods.
 *   - No constructor, no instance state, no dependencies.
 */

namespace Imagify\Tools;

/**
 * Single source of truth for all internal state that the reset tool must clear.
 *
 * Both ResetInternalState (the live reset service) and uninstall.php iterate
 * the arrays returned here. Add a new transient / hook / pattern in ONE place.
 */
class InternalStateList {

	/**
	 * Returns the list of bulk-optimization transient names to delete.
	 *
	 * This is a SUPERSET of Bulk::delete_transients_data():
	 *   - imagify_bulk_optimization_result and imagify_bulk_optimization_infos
	 *     are intentional additions not cleared by Bulk deactivation.
	 *   - imagify_bulk_optimization_level is a stale artifact from the old
	 *     WP_Background_Process implementation, retained here for hygiene on
	 *     upgraded sites.
	 *
	 * @return array<string>
	 */
	public static function get_bulk_transients(): array {
		return [
			'imagify_custom-folders_optimize_running',
			'imagify_wp_optimize_running',
			'imagify_bulk_optimization_complete',
			'imagify_missing_next_gen_total',
			'imagify_bulk_optimization_result',
			'imagify_bulk_optimization_infos',
			'imagify_bulk_optimization_level', // Stale artifact from old WP_Background_Process, retained for hygiene on upgraded sites.
		];
	}

	/**
	 * Returns LIKE patterns for process-lock and legacy RPC transients.
	 *
	 * Used in a raw SQL DELETE against $wpdb->options (and $wpdb->sitemeta on
	 * multisite). Patterns follow the format expected by $wpdb->prepare with %s.
	 *
	 * @return array<string>
	 */
	public static function get_locked_transient_patterns(): array {
		return [
			'\_transient\_%imagify-auto-optimize-%', // Legacy/deprecated, retained for hygiene on older installs.
			'\_transient\_%imagify\_rpc\_%',          // Legacy/deprecated.
			'\_transient\_imagify\_%\_process\_locked',
			'\_site\_transient\_imagify\_%\_process\_lock%',
		];
	}

	/**
	 * Returns the ActionScheduler hook names to unschedule.
	 *
	 * @return array<string>
	 */
	public static function get_scheduler_hooks(): array {
		return [
			'imagify_optimize_media',
			'imagify_convert_next_gen',
		];
	}
}
