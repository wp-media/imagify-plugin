<?php
declare(strict_types=1);

namespace Imagify\Tools;

/**
 * Resets Imagify internal state (bulk transients, process locks, scheduled jobs).
 *
 * This service clears only optimization/job state. It intentionally does NOT
 * touch user-data caches, settings, API key, or DB tables.
 */
class ResetInternalState {

	/**
	 * Performs the full internal-state reset.
	 *
	 * Actions performed in order:
	 *   1. Delete each bulk-running-state transient by name.
	 *   2. Delete all process-lock / RPC transients via LIKE patterns (options table).
	 *   3. On multisite: also delete site-transient process locks (sitemeta table).
	 *   4. Unschedule all ActionScheduler actions for each registered hook.
	 *
	 * All DB queries use $wpdb->prepare() + $wpdb->esc_like() — no raw interpolation.
	 *
	 * @return void
	 */
	public function reset(): void {
		global $wpdb;

		// 1. Delete named bulk transients.
		foreach ( InternalStateList::get_bulk_transients() as $transient ) {
			delete_transient( $transient );
		}

		// 2. Delete process-lock and legacy RPC transients via LIKE patterns in wp_options.
		foreach ( InternalStateList::get_locked_transient_patterns() as $raw_pattern ) {
			// Build a safe LIKE pattern: esc_like() each literal segment, preserve % wildcards.
			$like_pattern = implode( '%', array_map( [ $wpdb, 'esc_like' ], explode( '%', $raw_pattern ) ) );
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
					$like_pattern
				)
			);
		}

		// 3. On multisite, also clean up sitemeta process locks.
		if ( is_multisite() ) {
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
					$wpdb->esc_like( '_site_transient_imagify_' ) . '%_process_lock%'
				)
			);
		}

		// 4. Unschedule ActionScheduler jobs.
		foreach ( InternalStateList::get_scheduler_hooks() as $hook ) {
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( $hook );
			}
		}

		do_action( 'imagify_after_reset_internal_state' );
	}
}
