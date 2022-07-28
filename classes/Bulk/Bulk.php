<?php
namespace Imagify\Bulk;

use \Imagify\Traits\InstanceGetterTrait;

class Bulk {
	use InstanceGetterTrait;

	/**
	 * Class init: launch hooks.
	 *
	 * @since 2.1
	 */
	public function init() {
		add_action( 'imagify_optimize_media', [ $this, 'optimize_media' ] );
		add_action( 'imagify_convert_webp', [ $this, 'generate_webp_versions' ] );
		add_action( 'wp_ajax_imagify_bulk_optimize', 'bulk_optimize_callback' );
		add_action( 'wp_ajax_imagify_get_folder_type_data', 'get_folder_type_data_callback' );
		add_action( 'wp_ajax_imagify_bulk_info_seen', 'bulk_info_seen_callback' );
		add_action( 'wp_ajax_imagify_bulk_get_stats', 'bulk_get_stats_callback' );
	}

	/**
	 * Process a media with the requested imagify bulk action.
	 *
	 * @since 2.1
	 */
	public function optimize_media( array $args ) {
		if ( ! $args['id'] || ! $args['context'] ) {
			return;
		}

		if ( ! imagify_get_context( $args['context'] )->current_user_can( 'bulk-optimize', $args['id'] ) ) {
			return;
		}

		$this->force_optimize( $args['id'], $args['context'], 2 );
	}

	/**
	 * Runs the bulk optimization
	 *
	 * @param array $contexts An array of contexts (WP/Custom folders).
	 *
	 * @return void
	 */
	public function run_optimize( array $contexts ) {
		if ( ! $this->can_optimize() ) {
			return;
		}

		foreach ( $contexts as $context ) {
			$media_ids = $this->get_bulk_instance( $context )->get_unoptimized_media_ids( 2 );

			foreach ( $media_ids as $media_id ) {
				as_enqueue_async_action(
					'imagify_optimize_media',
					[
						'id'      => $media_id,
						'context' => $context,
					],
					"imagify-{$context}-optimize-media"
				);
			}
		}
	}

	/**
	 * Runs the WebP generation
	 *
	 * @param array $contexts An array of contexts (WP/Custom folders).
	 *
	 * @return void
	 */
	public function run_generate_webp( array $contexts ) {
		if ( ! $this->can_optimize() ) {
			return;
		}

		foreach ( $contexts as $context ) {
			$media     = $this->get_bulk_instance( $context )->get_optimized_media_ids_without_webp();
			$media_ids = [];

			if ( ! $media['ids'] && $media['errors']['no_backup'] ) {
				// No backup, no WebP.
				return;
			} elseif ( ! $media['ids'] && $media['errors']['no_file_path'] ) {
				// Error.
				return;
			}

			$media_ids = $media['ids'];

			foreach ( $media_ids as $media_id ) {
				as_enqueue_async_action(
					'imagify_convert_webp',
					[
						'id'      => $media_id,
						'context' => $context,
					],
					"imagify-{$context}-convert-webp"
				);
			}
		}
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
		* @param int    $class_name The class name.
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
	 * @return BulkInterface   The optimization process instance.
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
	 * @return bool|WP_Error    True if successfully launched. A \WP_Error instance on failure.
	 */
	private function force_optimize( $media_id, $context, $level ) {
		$process = imagify_get_optimization_process( $media_id, $context );
		$data    = $process->get_data();

		// Restore before re-optimizing.
		if ( $data->is_optimized() ) {
			$result = $process->restore();

			if ( is_wp_error( $result ) ) {
				// Return an error message.
				return $result;
			}
		}

		return $process->optimize( $level );
	}

	/**
	 * Generate WebP images if they are missing.
	 *
	 * @since 2.1
	 *
	 * @return bool|WP_Error    True if successfully launched. A \WP_Error instance on failure.
	 */
	public function generate_webp_versions( $args ) {
		return imagify_get_optimization_process( $args['id'], $args['context'] )->generate_webp_versions();
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
	 * @param  string $method The method used: 'GET' (default), or 'POST'.
	 * @param  string $parameter The name of the parameter to look for.
	 * @return string
	 */
	public function get_context( $method = 'GET', $parameter = 'context' ) {
		$method  = 'POST' === $method ? INPUT_POST : INPUT_GET;
		$context = filter_input( $method, $parameter, FILTER_SANITIZE_STRING );

		return imagify_sanitize_context( $context );
	}

	/** ----------------------------------------------------------------------------------------- */
	/** BULK OPTIMIZATION CALLBACKS ============================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Launch the bulk optimization action
	 *
	 * @return void
	 */
	public function bulk_optimize_callback() {
		imagify_check_nonce( 'imagify-bulk-optimize' );

		$context = $this->get_context();

		if ( ! imagify_get_context( $context )->current_user_can( 'bulk-optimize' ) ) {
			imagify_die();
		}


		$this->run_optimize( $context );
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

		$folder_types = filter_input( INPUT_GET, 'types', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		$folder_types = is_array( $folder_types ) ? array_filter( $folder_types, 'is_string' ) : [];

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
}