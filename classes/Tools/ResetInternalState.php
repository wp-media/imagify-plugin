<?php
declare(strict_types=1);

namespace Imagify\Tools;

/**
 * Service class that resets Imagify's internal optimization state.
 *
 * Clears transients and cancels stuck ActionScheduler jobs without
 * touching user settings or media optimization data.
 *
 * @since 2.3
 */
class ResetInternalState {

	/**
	 * Run the full internal-state reset.
	 *
	 * @since 2.3
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->cancel_actionscheduler_jobs();
		$this->delete_named_transients();
		$this->delete_pattern_transients();

		/**
		 * Fires after Imagify's internal state has been reset.
		 *
		 * @since 2.3
		 */
		do_action( 'imagify_after_reset_internal_state' );
	}

	/**
	 * Cancel all pending ActionScheduler bulk-optimization jobs.
	 *
	 * @since 2.3
	 *
	 * @return void
	 */
	private function cancel_actionscheduler_jobs(): void {
		if ( ! function_exists( 'as_cancel_all_pending_actions' ) ) {
			return;
		}

		as_cancel_all_pending_actions( 'imagify_optimize_media' );
		as_cancel_all_pending_actions( 'imagify_convert_next_gen' );
	}

	/**
	 * Delete well-known Imagify transients related to optimization state.
	 *
	 * User settings (imagify_settings) are intentionally excluded.
	 *
	 * @since 2.3
	 *
	 * @return void
	 */
	private function delete_named_transients(): void {
		// Bulk running state.
		delete_transient( 'imagify_custom-folders_optimize_running' );
		delete_transient( 'imagify_wp_optimize_running' );
		delete_transient( 'imagify_bulk_optimization_complete' );
		delete_transient( 'imagify_missing_next_gen_total' );
		delete_transient( 'imagify_bulk_optimization_result' );
		delete_transient( 'imagify_bulk_optimization_level' );
		delete_transient( 'imagify_stat_without_next_gen' );
		delete_transient( 'imagify_large_library' );
		delete_transient( 'imagify_max_image_size' );
		delete_transient( 'imagify_user_cache' );
		delete_transient( 'imagify_attachments_number_modal' );
		delete_transient( 'imagify_user' );

		// API / license cache.
		delete_site_transient( 'imagify_check_api_version' );
		delete_site_transient( 'imagify_check_licence_1' );
		delete_site_transient( 'imagify_user' );
	}

	/**
	 * Delete pattern-based transients (process locks, auto-optimize flags, RPC
	 * tokens and legacy WP-Background-Process batch rows) using direct DB queries.
	 *
	 * @since 2.3
	 *
	 * @return void
	 */
	private function delete_pattern_transients(): void {
		global $wpdb;

		$patterns = [
			'_transient_imagify\_%\_process\_locked',
			'_site_transient_imagify\_%\_process\_lock%',
			'_transient_%imagify-auto-optimize-%',
			'_transient_%imagify\_rpc\_%',
			'_transient_imagify\_optimize\_media\_batch%',
			'_transient_imagify\_convert\_next\_gen\_batch%',
		];

		foreach ( $patterns as $pattern ) {
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$pattern
				)
			);
		}
	}
}
