<?php
namespace Imagify\Bulk;

use Exception;
use Imagify\Traits\InstanceGetterTrait;
use Imagify\Optimization\Process\ProcessInterface;
use WP_Error;

/**
 * Bulk optimization
 */
final class Bulk {
	use InstanceGetterTrait;

	/**
	 * Class init: launch hooks.
	 *
	 * @since 2.1
	 */
	public function init() {
		add_action( 'imagify_optimize_media', [ $this, 'optimize_media' ], 10, 3 );
		add_action( 'imagify_convert_next_gen', [ $this, 'generate_nextgen_versions' ], 10, 2 ); // @phpstan-ignore-line
		add_action( 'wp_ajax_imagify_bulk_optimize', [ $this, 'bulk_optimize_callback' ] );
		add_action( 'wp_ajax_imagify_missing_nextgen_generation', [ $this, 'missing_nextgen_callback' ] );
		add_action( 'wp_ajax_imagify_get_folder_type_data', [ $this, 'get_folder_type_data_callback' ] );
		add_action( 'wp_ajax_imagify_bulk_info_seen', [ $this, 'bulk_info_seen_callback' ] );
		add_action( 'wp_ajax_imagify_bulk_get_stats', [ $this, 'bulk_get_stats_callback' ] );
		add_action( 'imagify_after_optimize', [ $this, 'check_optimization_status' ], 10, 2 );
		add_action( 'imagify_deactivation', [ $this, 'delete_transients_data' ] );
		add_action( 'update_option_imagify_settings', [ $this, 'maybe_generate_missing_nextgen' ], 10, 2 );
	}

	/**
	 * Delete transients data on deactivation
	 *
	 * @return void
	 */
	public function delete_transients_data() {
		delete_transient( 'imagify_custom-folders_optimize_running' );
		delete_transient( 'imagify_wp_optimize_running' );
		delete_transient( 'imagify_bulk_optimization_complete' );
		delete_transient( 'imagify_missing_next_gen_total' );
	}

	/**
	 * Checks bulk optimization status after each optimization task
	 *
	 * @param ProcessInterface $process The optimization process.
	 * @param array            $item    The item being processed.
	 *
	 * @return void
	 */
	public function check_optimization_status( $process, $item ) {
		$custom_folders = get_transient( 'imagify_custom-folders_optimize_running' );
		$library_wp     = get_transient( 'imagify_wp_optimize_running' );

		if (
			! $custom_folders
			&&
			! $library_wp
		) {
			return;
		}

		$data     = $process->get_data();

		if ( ! $data ) {
			return;
		}

		$progress = get_transient( 'imagify_bulk_optimization_result' );

		if ( $data->is_optimized() ) {
			$size_data = $data->get_size_data();

			if ( false === $progress ) {
				$progress = [
					'total'          => 0,
					'original_size'  => 0,
					'optimized_size' => 0,
				];
			}

			$progress['total']++;

			$progress['original_size']  += $size_data['original_size'];
			$progress['optimized_size'] += $size_data['optimized_size'];

			set_transient( 'imagify_bulk_optimization_result', $progress, DAY_IN_SECONDS );
		}

		$remaining = 0;

		if ( false !== $custom_folders ) {
			if ( false !== strpos( $item['process_class'], 'CustomFolders' ) ) {
				$custom_folders['remaining']--;

				set_transient( 'imagify_custom-folders_optimize_running', $custom_folders, DAY_IN_SECONDS );

				$remaining += $custom_folders['remaining'];
			}
		}

		if ( false !== $library_wp ) {
			if ( false !== strpos( $item['process_class'], 'WP' ) ) {
				$library_wp['remaining']--;

				set_transient( 'imagify_wp_optimize_running', $library_wp, DAY_IN_SECONDS );

				$remaining += $library_wp['remaining'];
			}
		}

		if ( 0 >= $remaining ) {
			delete_transient( 'imagify_custom-folders_optimize_running' );
			delete_transient( 'imagify_wp_optimize_running' );
			set_transient( 'imagify_bulk_optimization_complete', 1, DAY_IN_SECONDS );
		}
	}

	/**
	 * Decrease optimization running counter for the given context
	 *
	 * @param string $context Context to update.
	 *
	 * @return void
	 */
	private function decrease_counter( string $context ) {
		$counter = get_transient( "imagify_{$context}_optimize_running" );

		if ( false === $counter ) {
			return;
		}

		$counter['total']     = $counter['total'] - 1;
		$counter['remaining'] = $counter['remaining'] - 1;

		if (
			0 === $counter['total']
			&&
			0 >= $counter['remaining']
		) {
			delete_transient( "imagify_{$context}_optimize_running" );
		}

		set_transient( "imagify_{$context}_optimize_running", $counter, DAY_IN_SECONDS );
	}

	/**
	 * Process a media with the requested imagify bulk action.
	 *
	 * @since 2.1
	 *
	 * @param int    $media_id Media ID.
	 * @param string $context Current context.
	 * @param int    $optimization_level Optimization level.
	 */
	public function optimize_media( int $media_id, string $context, int $optimization_level ) {
		if ( ! $media_id || ! $context ) {
			$this->decrease_counter( $context );

			return;
		}

		$this->force_optimize( $media_id, $context, $optimization_level );
	}

	/**
	 * Runs the bulk optimization
	 *
	 * @param string $context Current context (WP/Custom folders).
	 * @param int    $optimization_level Optimization level.
	 *
	 * @return array
	 */
	public function run_optimize( string $context, int $optimization_level ) {
		if ( ! $this->can_optimize() ) {
			return [
				'success' => false,
				'message' => 'over-quota',
			];
		}

		$media_ids = $this->get_bulk_instance( $context )->get_unoptimized_media_ids( $optimization_level );

		if ( empty( $media_ids ) ) {
			return [
				'success' => false,
				'message' => 'no-images',
			];
		}

		foreach ( $media_ids as $media_id ) {
			try {
				as_enqueue_async_action(
					'imagify_optimize_media',
					[
						'id'      => $media_id,
						'context' => $context,
						'level'   => $optimization_level,
					],
					"imagify-{$context}-optimize-media"
				);
			} catch ( Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// nothing to do.
			}
		}

		$data = [
			'total'     => count( $media_ids ),
			'remaining' => count( $media_ids ),
		];

		set_transient( "imagify_{$context}_optimize_running", $data, DAY_IN_SECONDS );

		return [
			'success' => true,
			'message' => 'success',
		];
	}

	/**
	 * Runs the next-gen generation
	 *
	 * @param array $contexts An array of contexts (WP/Custom folders).
	 * @param array $formats An array of format to generate.
	 *
	 * @return array
	 */
	public function run_generate_nextgen( array $contexts, array $formats ) {
		if ( ! $this->can_optimize() ) {
			return [
				'success' => false,
				'message' => 'over-quota',
			];
		}

		delete_transient( 'imagify_stat_without_next_gen' );

		$medias = [];

		foreach ( $contexts as $context ) {
			foreach ( $formats as $format ) {
				$media = $this->get_bulk_instance( $context )->get_optimized_media_ids_without_format( $format );
				if ( ! $media['ids'] && $media['errors']['no_backup'] ) {
					// No backup, no next-gen.
					return [
						'success' => false,
						'message' => 'no-backup',
					];
				} elseif ( ! $media['ids'] && $media['errors']['no_file_path'] ) {
					// Error.
					return [
						'success' => false,
						'message' => __( 'The path to the selected files could not be retrieved.', 'imagify' ),
					];
				}

				$medias[ $context ] = $media['ids'];
			}
		}

		if ( empty( $medias ) ) {
			return [
				'success' => false,
				'message' => 'no-images',
			];
		}

		$total = 0;

		foreach ( $medias as $context => $media_ids ) {
			$total += count( $media_ids );

			foreach ( $media_ids as $media_id ) {
				try {
					as_enqueue_async_action(
						'imagify_convert_next_gen',
						[
							'id'      => $media_id,
							'context' => $context,
						],
						"imagify-{$context}-convert-nextgen"
					);
				} catch ( Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					// nothing to do.
				}
			}
		}

		set_transient( 'imagify_missing_next_gen_total', $total, HOUR_IN_SECONDS );

		return [
			'success' => true,
			'message' => $total,
		];
	}

	/**
	 * Get the Bulk class name depending on a context.
	 *
	 * @since 2.1
	 *
	 * @param  string $context The context name. Default values are 'wp' and 'custom-folders'.
	 * @return string          The Bulk class name.
	 */
	private function get_bulk_class_name( string $context ): string {
		switch ( $context ) {
			case 'wp':
				$class_name = WP::class;
				break;

			case 'custom-folders':
				$class_name = CustomFolders::class;
				break;

			default:
				$class_name = Noop::class;
		}

		/**
		 * Filter the name of the class to use for bulk process.
		 *
		 * @since 1.9
		 *
		 * @param string $class_name The class name.
		 * @param string $context    The context name.
		 */
		$class_name = apply_filters( 'imagify_bulk_class_name', $class_name, $context );

		return '\\' . ltrim( $class_name, '\\' );
	}

	/**
	 * Get the Bulk instance depending on a context.
	 *
	 * @since 2.1
	 *
	 * @param  string $context The context name. Default values are 'wp' and 'custom-folders'.
	 *
	 * @return BulkInterface The optimization process instance.
	 */
	public function get_bulk_instance( string $context ): BulkInterface {
		$class_name = $this->get_bulk_class_name( $context );
		return new $class_name();
	}

	/**
	 * Optimize all files from a media, whatever this media’s previous optimization status (will be restored if needed).
	 * This is used by the bulk optimization page.
	 *
	 * @since 1.9
	 *
	 * @param  int    $media_id The media ID.
	 * @param  string $context  The context.
	 * @param  int    $level    The optimization level.
	 *
	 * @return bool|WP_Error True if successfully launched. A \WP_Error instance on failure.
	 */
	private function force_optimize( int $media_id, string $context, int $level ) {
		if ( ! $this->can_optimize() ) {
			$this->decrease_counter( $context );

			return false;
		}

		$process = imagify_get_optimization_process( $media_id, $context );
		$data    = $process->get_data();

		// Restore before re-optimizing.
		if ( $data->is_optimized() ) {
			$result = $process->restore();

			if ( is_wp_error( $result ) ) {
				$this->decrease_counter( $context );

				// Return an error message.
				return $result;
			}
		}

		return $process->optimize( $level );
	}

	/**
	 * Generate next-gen images if they are missing.
	 *
	 * @since 2.1
	 *
	 * @param int    $media_id Media ID.
	 * @param string $context Current context.
	 *
	 * @return bool|WP_Error    True if successfully launched. A \WP_Error instance on failure.
	 */
	public function generate_nextgen_versions( int $media_id, string $context ) {
		if ( ! $this->can_optimize() ) {
			return false;
		}

		return imagify_get_optimization_process( $media_id, $context )->generate_nextgen_versions();
	}

	/**
	 * Check if the user has a valid account and has quota. Die on failure.
	 *
	 * @since 2.1
	 */
	public function can_optimize() {
		if ( ! \Imagify_Requirements::is_api_key_valid() ) {
			return false;
		}

		if ( \Imagify_Requirements::is_over_quota() ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the submitted context.
	 *
	 * @since 1.9
	 *
	 * @param string $method The method used: 'GET' (default), or 'POST'.
	 * @param string $parameter The name of the parameter to look for.
	 *
	 * @return string
	 */
	public function get_context( $method = 'GET', $parameter = 'context' ) {
		$context = 'POST' === $method ? wp_unslash( $_POST[ $parameter ] ) : wp_unslash( $_GET[ $parameter ] ); //phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		$context = htmlspecialchars( $context );

		return imagify_sanitize_context( $context );
	}

	/**
	 * Get the submitted optimization level.
	 *
	 * @since  1.7
	 * @since  1.9 Added $method and $parameter parameters.
	 * @author Grégory Viguier
	 *
	 * @param  string $method The method used: 'GET' (default), or 'POST'.
	 * @param  string $parameter The name of the parameter to look for.
	 * @return int
	 */
	public function get_optimization_level( $method = 'GET', $parameter = 'optimization_level' ) {
		$method = 'POST' === $method ? INPUT_POST : INPUT_GET;
		$level  = filter_input( $method, $parameter );

		if ( ! is_numeric( $level ) || $level < 0 || $level > 2 ) {
			if ( get_imagify_option( 'lossless' ) ) {
				return 0;
			}

			return get_imagify_option( 'optimization_level' );
		}

		return (int) $level;
	}

	/**
	 * Launch the bulk optimization action
	 *
	 * @return void
	 */
	public function bulk_optimize_callback() {
		imagify_check_nonce( 'imagify-bulk-optimize' );

		$context = $this->get_context();
		$level   = $this->get_optimization_level();

		if ( ! imagify_get_context( $context )->current_user_can( 'bulk-optimize' ) ) {
			imagify_die();
		}

		$data = $this->run_optimize( $context, $level );

		if ( false === $data['success'] ) {
			wp_send_json_error( [ 'message' => $data['message'] ] );
		}

		wp_send_json_success( [ 'total' => $data['message'] ] );
	}

	/**
	 * Launch the missing Next-gen versions generation
	 *
	 * @return void
	 */
	public function missing_nextgen_callback() {
		imagify_check_nonce( 'imagify-bulk-optimize' );

		$contexts = $this->get_contexts();

		foreach ( $contexts as $context ) {
			if ( ! imagify_get_context( $context )->current_user_can( 'bulk-optimize' ) ) {
				imagify_die();
			}
		}

		$formats = imagify_nextgen_images_formats();

		$data = $this->run_generate_nextgen( $contexts, $formats );
		if ( false === $data['success'] ) {
			wp_send_json_error( [ 'message' => $data['message'] ] );
		}

		wp_send_json_success( [ 'total' => $data['message'] ] );
	}

	/**
	 * Get stats data for a specific folder type.
	 *
	 * @since  1.7
	 */
	public function get_folder_type_data_callback() {
		imagify_check_nonce( 'imagify-bulk-optimize' );

		$context = $this->get_context();

		if ( ! $context ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		if ( ! imagify_get_context( $context )->current_user_can( 'bulk-optimize' ) ) {
			imagify_die();
		}

		$bulk = $this->get_bulk_instance( $context );

		wp_send_json_success( $bulk->get_context_data() );
	}

	/**
	 * Set the "bulk info" popup state as "seen".
	 *
	 * @since  1.7
	 */
	public function bulk_info_seen_callback() {
		imagify_check_nonce( 'imagify-bulk-optimize' );

		$context = $this->get_context();

		if ( ! $context ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		if ( ! imagify_get_context( $context )->current_user_can( 'bulk-optimize' ) ) {
			imagify_die();
		}

		set_transient( 'imagify_bulk_optimization_infos', 1, WEEK_IN_SECONDS );

		wp_send_json_success();
	}

	/**
	 * Get generic stats to display in the bulk page.
	 *
	 * @since  1.7.1
	 */
	public function bulk_get_stats_callback() {
		imagify_check_nonce( 'imagify-bulk-optimize' );

		$folder_types = filter_input( INPUT_GET, 'types', FILTER_REQUIRE_ARRAY );
		$folder_types = is_array( $folder_types ) ? $folder_types : [];

		if ( ! $folder_types ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		foreach ( $folder_types as $folder_type_data ) {
			$context = ! empty( $folder_type_data['context'] ) ? $folder_type_data['context'] : 'noop';

			if ( ! imagify_get_context( $context )->current_user_can( 'bulk-optimize' ) ) {
				imagify_die();
			}
		}

		wp_send_json_success( imagify_get_bulk_stats( array_flip( $folder_types ) ) );
	}

	/**
	 * Update Options callback to start bulk optimization.
	 *
	 * @since 2.2
	 *
	 * @param array $old_value The old option value.
	 * @param array $value The new option value.
	 *
	 * @return void
	 */
	public function maybe_generate_missing_nextgen( $old_value, $value ) {
		if ( ! isset( $old_value['optimization_format'], $value['optimization_format'] ) ) {
			return;
		}

		if ( $old_value['optimization_format'] === $value['optimization_format'] ) {
			// Old value = new value so do nothing.
			return;
		}

		if ( 'off' === $value['optimization_format'] ) {
			// No need to generate next-gen images.
			return;
		}

		$contexts = $this->get_contexts();
		$formats  = imagify_nextgen_images_formats();

		$this->run_generate_nextgen( $contexts, $formats );
	}

	/**
	 * Get the context for the bulk optimization page.
	 *
	 * @since 2.2
	 *
	 * @return array The array of unique contexts ('wp' or 'custom-folders').
	 */
	public function get_contexts() {
		$contexts = [];
		$types = [];

		// Library: in each site.
		if ( ! is_network_admin() ) {
			$types['library|wp'] = 1;
		}

		// Custom folders: in network admin only if network activated, in each site otherwise.
		if ( imagify_can_optimize_custom_folders() && ( imagify_is_active_for_network() && is_network_admin() || ! imagify_is_active_for_network() ) ) {
			$types['custom-folders|custom-folders'] = 1;
		}

		/**
		 * Filter the types to display in the bulk optimization page.
		 *
		 * @since  1.7.1
		 *
		 * @param array $types The folder types displayed on the page. If a folder type is "library", the context should be suffixed after a pipe character. They are passed as array keys.
		 */
		$types = apply_filters( 'imagify_bulk_page_types', $types );
		$types = array_filter( (array) $types );

		if ( isset( $types['library|wp'] ) ) {
			$contexts[] = 'wp';
		}

		if ( isset( $types['custom-folders|custom-folders'] ) ) {
			$folders_instance = \Imagify_Folders_DB::get_instance();

			if ( ! $folders_instance->has_items() ) {
				// New Feature!
				if ( ! in_array( 'wp', $contexts, true ) ) {
					$contexts[] = 'wp';
				}
			} elseif ( $folders_instance->has_active_folders() ) {
				$contexts[] = 'custom-folders';
			}
		}

		return $contexts;
	}
}
