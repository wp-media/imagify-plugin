<?php
namespace Imagify\Imagifybeat;

use Imagify_Requirements;
use Imagify\Traits\InstanceGetterTrait;
use Imagify\Bulk\Bulk;

/**
 * Imagifybeat actions.
 *
 * @since 1.9.3
 */
final class Actions {
	use InstanceGetterTrait;

	/**
	 * The list of action IDs.
	 * Keys are related to method names, values are Imagifybeat IDs.
	 *
	 * @var array
	 *
	 * @since 1.9.3
	 */
	private $imagifybeat_ids = [
		'requirements'                       => 'imagify_requirements',
		'bulk_optimization_stats'            => 'imagify_bulk_optimization_stats',
		'bulk_optimization_status'           => 'imagify_bulk_optimization_status',
		'options_optimization_status'        => 'imagify_options_optimization_status',
		'library_optimization_status'        => 'imagify_library_optimization_status',
		'custom_folders_optimization_status' => 'imagify_custom_folders_optimization_status',
	];

	/**
	 * Class init: launch hooks.
	 *
	 * @since 1.9.3
	 */
	public function init() {
		foreach ( $this->imagifybeat_ids as $action => $imagifybeat_id ) {
			add_filter( 'imagifybeat_received', [ $this, 'add_' . $action . '_to_response' ], 10, 2 );
		}
	}

	/** ----------------------------------------------------------------------------------------- */
	/** IMAGIFYBEAT CALLBACKS =================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Add requirements to Imagifybeat data.
	 *
	 * @since 1.9.3
	 *
	 * @param  array $response The Imagifybeat response.
	 * @param  array $data     The $_POST data sent.
	 * @return array
	 */
	public function add_requirements_to_response( $response, $data ) {
		$imagifybeat_id = $this->get_imagifybeat_id_for_callback( __FUNCTION__ );

		if ( ! $imagifybeat_id || empty( $data[ $imagifybeat_id ] ) ) {
			return $response;
		}

		// Used in the settings page and the bulk optimization page.
		if (
			! imagify_get_context( 'wp' )->current_user_can( 'manage' )
			&& ! imagify_get_context( 'wp' )->current_user_can( 'bulk-optimize' )
			&& ! imagify_get_context( 'custom-folders' )->current_user_can( 'bulk-optimize' )
		) {
			return $response;
		}

		$response[ $imagifybeat_id ] = [
			'curl_missing'          => ! Imagify_Requirements::supports_curl(),
			'editor_missing'        => ! Imagify_Requirements::supports_image_editor(),
			'external_http_blocked' => Imagify_Requirements::is_imagify_blocked(),
			'api_down'              => ! Imagify_Requirements::is_api_up(),
			'key_is_valid'          => Imagify_Requirements::is_api_key_valid(),
			'is_over_quota'         => Imagify_Requirements::is_over_quota(),
		];

		return $response;
	}

	/**
	 * Add bulk stats to Imagifybeat data.
	 *
	 * @since 1.9.3
	 *
	 * @param  array $response The Imagifybeat response.
	 * @param  array $data     The $_POST data sent.
	 * @return array
	 */
	public function add_bulk_optimization_stats_to_response( $response, $data ) {
		$imagifybeat_id = $this->get_imagifybeat_id_for_callback( __FUNCTION__ );

		if ( ! $imagifybeat_id || empty( $data[ $imagifybeat_id ] ) ) {
			return $response;
		}

		if ( ! is_array( $data[ $imagifybeat_id ] ) ) {
			return $response;
		}

		// Folder types are like '{groupID}|{context}': keep only the ones the user is allowed to bulk-optimize.
		$folder_types = array_filter(
			$data[ $imagifybeat_id ],
			function ( $folder_type ) {
				if ( ! is_string( $folder_type ) || false === strpos( $folder_type, '|' ) ) {
					return false;
				}

				$context = substr( $folder_type, strrpos( $folder_type, '|' ) + 1 );

				return imagify_get_context( $context )->current_user_can( 'bulk-optimize' );
			}
		);

		if ( ! $folder_types ) {
			return $response;
		}

		$folder_types = array_flip( $folder_types );

		$response[ $imagifybeat_id ] = imagify_get_bulk_stats(
			$folder_types,
			[
				'fullset' => true,
			]
		);

		return $response;
	}

	/**
	 * Look for media where status has changed, compared to what Imagifybeat sends.
	 * This is used in the bulk optimization page.
	 *
	 * @since 1.9.3
	 *
	 * @param  array $response The Imagifybeat response.
	 * @param  array $data     The $_POST data sent.
	 * @return array
	 */
	public function add_bulk_optimization_status_to_response( $response, $data ) {
		$imagifybeat_id = $this->get_imagifybeat_id_for_callback( __FUNCTION__ );

		if ( ! $imagifybeat_id || empty( $data[ $imagifybeat_id ] ) || ! is_array( $data[ $imagifybeat_id ] ) ) {
			return $response;
		}

		$bulk        = Bulk::get_instance();
		$groups_data = [];
		$total       = 0;
		$remaining   = 0;
		$percentage  = 0;

		foreach ( $data[ $imagifybeat_id ] as $group ) {
			$context = $this->get_bulk_optimizable_context( isset( $group['context'] ) ? $group['context'] : null );

			if ( ! $context ) {
				continue;
			}

			$transient = get_transient( "imagify_{$context}_optimize_running" );

			if ( false !== $transient ) {
				$total     += $transient['total'];
				$remaining += $transient['remaining'];
			}

			$groups_data[ $context ] = $bulk->get_bulk_instance( $context )->get_context_data();
		}

		if ( ! $groups_data ) {
			return $response;
		}

		if ( 0 !== $total ) {
			$percentage = ( $total - $remaining ) / $total * 100;
		}

		$response[ $imagifybeat_id ] = [
			'groups_data' => $groups_data,
			'remaining'   => $remaining,
			'percentage'  => round( $percentage ),
			'result'      => get_transient( 'imagify_bulk_optimization_result' ),
		];

		return $response;
	}

	/**
	 * Look for media where status has changed, compared to what Imagifybeat sends.
	 * This is used in the settings page.
	 *
	 * @since 1.9
	 *
	 * @param  array $response The Imagifybeat response.
	 * @param  array $data     The $_POST data sent.
	 * @return array
	 */
	public function add_options_optimization_status_to_response( $response, $data ) {
		$imagifybeat_id = $this->get_imagifybeat_id_for_callback( __FUNCTION__ );

		if ( ! $imagifybeat_id || empty( $data[ $imagifybeat_id ] ) || ! is_array( $data[ $imagifybeat_id ] ) ) {
			return $response;
		}

		// Used in the settings page.
		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			return $response;
		}

		$remaining = 0;
		$total     = get_transient( 'imagify_missing_next_gen_total' );

		if ( false === $total ) {
			return $response;
		}

		$bulk = Bulk::get_instance();

		$format = get_imagify_option( 'optimization_format' );

		if ( 'off' === $format ) {
			return $response;
		}

		foreach ( $data[ $imagifybeat_id ] as $context ) {
			if ( ! is_string( $context ) || '' === $context ) {
				continue;
			}

			$context    = imagify_sanitize_context( $context );
			$media      = $bulk->get_bulk_instance( $context )->get_optimized_media_ids_without_format( $format );
			$remaining += count( $media['ids'] );
		}

		$response[ $imagifybeat_id ] = [
			'remaining' => $remaining,
			'total'     => (int) $total,
		];

		return $response;
	}

	/**
	 * Look for media where status has changed, compared to what Imagifybeat sends.
	 * This is used in the WP Media Library.
	 *
	 * @since 1.9.3
	 *
	 * @param  array $response The Imagifybeat response.
	 * @param  array $data     The $_POST data sent.
	 * @return array
	 */
	public function add_library_optimization_status_to_response( $response, $data ) {
		$imagifybeat_id = $this->get_imagifybeat_id_for_callback( __FUNCTION__ );

		if ( ! $imagifybeat_id || empty( $data[ $imagifybeat_id ] ) || ! is_array( $data[ $imagifybeat_id ] ) ) {
			return $response;
		}

		$statuses = $this->get_allowed_processes( $this->get_modified_optimization_statuses( $data[ $imagifybeat_id ] ) );

		// Nothing is returned when there is nothing to update, so that invalid media and media the user is not allowed to optimize can't be told apart.
		if ( ! $statuses ) {
			return $response;
		}

		$response[ $imagifybeat_id ] = [];

		foreach ( $statuses as $context_id => $process ) {
			$response[ $imagifybeat_id ][ $context_id ] = get_imagify_media_column_content( $process, false );
		}

		return $response;
	}

	/**
	 * Look for media where status has changed, compared to what Imagifybeat sends.
	 * This is used in the custom folders list (the "Other Media" page).
	 *
	 * @since 1.9.3
	 *
	 * @param  array $response The Imagifybeat response.
	 * @param  array $data     The $_POST data sent.
	 * @return array
	 */
	public function add_custom_folders_optimization_status_to_response( $response, $data ) {
		$imagifybeat_id = $this->get_imagifybeat_id_for_callback( __FUNCTION__ );

		if ( ! $imagifybeat_id || empty( $data[ $imagifybeat_id ] ) || ! is_array( $data[ $imagifybeat_id ] ) ) {
			return $response;
		}

		// Used in the "Other Media" page: same capabilities as the ones required to display the page.
		$cf_context = imagify_get_context( 'custom-folders' );

		if ( ! $cf_context->current_user_can( 'optimize' ) && ! $cf_context->current_user_can( 'bulk-optimize' ) ) {
			return $response;
		}

		// This page lists custom folders files only.
		$data     = array_intersect_key( $data[ $imagifybeat_id ], [ 'custom-folders' => 1 ] );
		$statuses = $this->get_allowed_processes( $this->get_modified_optimization_statuses( $data ) );

		if ( ! $statuses ) {
			return $response;
		}

		$response[ $imagifybeat_id ] = [];

		$admin_ajax_post = \Imagify_Admin_Ajax_Post::get_instance();
		$list_table      = new \Imagify_Files_List_Table(
			[
				'screen' => 'imagify-files',
			]
		);

		foreach ( $statuses as $context_id => $process ) {
			$response[ $imagifybeat_id ][ $context_id ] = $admin_ajax_post->get_media_columns( $process, $list_table );
		}

		return $response;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Look for media where status has changed, compared to what Imagifybeat sends.
	 *
	 * @since 1.9.3
	 *
	 * @param  array $data The data received.
	 * @return array
	 */
	private function get_modified_optimization_statuses( $data ) {
		if ( ! $data ) {
			return [];
		}

		$output = [];

		// Sanitize received data and grab some other info.
		foreach ( $data as $context => $media_statuses ) {
			if ( ! $context || ! $media_statuses || ! is_array( $media_statuses ) ) {
				continue;
			}

			// Sanitize the IDs: IDs come as strings, prefixed with an undescore character (to prevent JavaScript from screwing everything).
			$media_ids = array_keys( $media_statuses );
			$media_ids = array_map(
				function ( $media_id ) {
					return substr( $media_id, 1 );
				},
				$media_ids
			);
			$media_ids = array_filter( $media_ids );

			if ( ! $media_ids ) {
				continue;
			}

			// Sanitize the context.
			$context_instance   = imagify_get_context( $context );
			$context            = $context_instance->get_name();
			$process_class_name = imagify_get_optimization_process_class_name( $context );
			$transient_name     = sprintf( $process_class_name::LOCK_NAME, $context, '%' );
			$is_network_wide    = $context_instance->is_network_wide();

			\Imagify_DB::cache_process_locks( $context, $media_ids );

			// Now that everything is cached for this context, we can get the transients without hitting the DB.
			foreach ( $media_ids as $id ) {
				$is_locked   = (bool) $media_statuses[ '_' . $id ];
				$option_name = str_replace( '%', $id, $transient_name );

				if ( $is_network_wide ) {
					$in_db = (bool) get_site_transient( $option_name );
				} else {
					$in_db = (bool) get_transient( $option_name );
				}

				if ( $is_locked === $in_db ) {
					continue;
				}

				$output[ $context . '_' . $id ] = [
					'media_id' => $id,
					'context'  => $context,
				];
			}
		}

		return $output;
	}

	/**
	 * Get the optimization processes of the media the current user is allowed to optimize.
	 *
	 * Invalid media are dropped the same way, so that they can't be told apart from media the user is not allowed to optimize.
	 *
	 * @since 2.3.5
	 *
	 * @param  array $statuses Media data, as returned by $this->get_modified_optimization_statuses().
	 * @return array           Optimization processes, keyed like $statuses.
	 */
	private function get_allowed_processes( $statuses ) {
		$processes = [];

		foreach ( $statuses as $context_id => $media_atts ) {
			$process = imagify_get_optimization_process( $media_atts['media_id'], $media_atts['context'] );

			if ( $process->current_user_can( 'manual-optimize' ) ) {
				$processes[ $context_id ] = $process;
			}
		}

		return $processes;
	}

	/**
	 * Validate a context name received from Imagifybeat, for the bulk optimization page.
	 *
	 * @since 2.3.5
	 *
	 * @param  mixed $context The context name received.
	 * @return string         The sanitized context name. An empty string if invalid or if the user is not allowed to bulk-optimize in this context.
	 */
	private function get_bulk_optimizable_context( $context ) {
		if ( ! is_string( $context ) || '' === $context ) {
			return '';
		}

		$context = imagify_sanitize_context( $context );

		if ( ! $context || ! imagify_get_context( $context )->current_user_can( 'bulk-optimize' ) ) {
			return '';
		}

		return $context;
	}

	/**
	 * Get an Imagifybeat ID, given an action.
	 *
	 * @since 1.9.3
	 *
	 * @param  string $action An action corresponding to the ID we want.
	 * @return string|bool    The ID. False on failure.
	 */
	public function get_imagifybeat_id( $action ) {
		if ( ! empty( $this->imagifybeat_ids[ $action ] ) ) {
			return $this->imagifybeat_ids[ $action ];
		}

		return false;
	}

	/**
	 * Get an Imagifybeat ID, given a callback name.
	 *
	 * @since 1.9.3
	 *
	 * @param  string $callback A method’s name.
	 * @return string|bool      The ID. False on failure.
	 */
	private function get_imagifybeat_id_for_callback( $callback ) {
		if ( preg_match( '@^add_(?<id>.+)_to_response$@', $callback, $matches ) ) {
			return $this->get_imagifybeat_id( $matches['id'] );
		}

		return false;
	}
}
