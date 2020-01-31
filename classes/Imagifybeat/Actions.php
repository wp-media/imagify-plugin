<?php
namespace Imagify\Imagifybeat;

use Imagify_Requirements;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Imagifybeat actions.
 *
 * @since  1.9.3
 * @author Grégory Viguier
 */
class Actions {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * The list of action IDs.
	 * Keys are related to method names, values are Imagifybeat IDs.
	 *
	 * @var    array
	 * @since  1.9.3
	 * @access private
	 * @author Grégory Viguier
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
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
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
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
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
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
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

		$folder_types = array_flip( array_filter( $data[ $imagifybeat_id ] ) );

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
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
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

		$statuses = [];

		foreach ( $data[ $imagifybeat_id ] as $item ) {
			if ( empty( $statuses[ $item['context'] ] ) ) {
				$statuses[ $item['context'] ] = [];
			}

			$statuses[ $item['context'] ][ '_' . $item['mediaID'] ] = 1;
		}

		$results = $this->get_modified_optimization_statuses( $statuses );

		if ( ! $results ) {
			return $response;
		}

		$response[ $imagifybeat_id ] = [];

		// Sanitize received data and grab some other info.
		foreach ( $results as $context_id => $media_atts ) {
			$process    = imagify_get_optimization_process( $media_atts['media_id'], $media_atts['context'] );
			$optim_data = $process->get_data();

			if ( $optim_data->is_optimized() ) {
				// Successfully optimized.
				$full_size_data                = $optim_data->get_size_data();
				$full_size_data                = array_merge(
					[
						'original_size'  => 0,
						'optimized_size' => 0,
						'percent'        => 0,
					],
					$full_size_data
				);
				$response[ $imagifybeat_id ][] = [
					'mediaID'                  => $media_atts['media_id'],
					'context'                  => $media_atts['context'],
					'success'                  => true,
					'status'                   => 'optimized',
					// Raw data.
					'originalOverallSize'      => $full_size_data['original_size'],
					'newOverallSize'           => $full_size_data['optimized_size'],
					'overallSaving'            => $full_size_data['original_size'] - $full_size_data['optimized_size'],
					'thumbnailsCount'          => $optim_data->get_optimized_sizes_count(),
					// Human readable data.
					'originalSizeHuman'        => imagify_size_format( $full_size_data['original_size'], 2 ),
					'newSizeHuman'             => imagify_size_format( $full_size_data['optimized_size'], 2 ),
					'overallSavingHuman'       => imagify_size_format( $full_size_data['original_size'] - $full_size_data['optimized_size'], 2 ),
					'originalOverallSizeHuman' => imagify_size_format( $full_size_data['original_size'], 2 ),
					'percentHuman'             => $full_size_data['percent'] . '%',
				];
			} elseif ( $optim_data->is_already_optimized() ) {
				// Already optimized.
				$response[ $imagifybeat_id ][] = [
					'mediaID' => $media_atts['media_id'],
					'context' => $media_atts['context'],
					'success' => true,
					'status'  => 'already-optimized',
				];
			} else {
				// Error.
				$full_size_data = $optim_data->get_size_data();
				$message        = ! empty( $full_size_data['error'] ) ? $full_size_data['error'] : '';
				$status         = 'error';

				if ( 'You\'ve consumed all your data. You have to upgrade your account to continue' === $message ) {
					$status = 'over-quota';
				}

				$response[ $imagifybeat_id ][] = [
					'mediaID' => $media_atts['media_id'],
					'context' => $media_atts['context'],
					'success' => false,
					'status'  => $status,
					'error'   => imagify_translate_api_message( $message ),
				];
			}
		}

		return $response;
	}

	/**
	 * Look for media where status has changed, compared to what Imagifybeat sends.
	 * This is used in the settings page.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
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

		$statuses = [];

		foreach ( $data[ $imagifybeat_id ] as $item ) {
			if ( empty( $statuses[ $item['context'] ] ) ) {
				$statuses[ $item['context'] ] = [];
			}

			$statuses[ $item['context'] ][ '_' . $item['mediaID'] ] = 1;
		}

		$results = $this->get_modified_optimization_statuses( $statuses );

		if ( ! $results ) {
			return $response;
		}

		$response[ $imagifybeat_id ] = [];

		foreach ( $results as $result ) {
			$response[ $imagifybeat_id ][] = [
				'mediaID' => $result['media_id'],
				'context' => $result['context'],
			];
		}

		return $response;
	}

	/**
	 * Look for media where status has changed, compared to what Imagifybeat sends.
	 * This is used in the WP Media Library.
	 *
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
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

		$response[ $imagifybeat_id ] = $this->get_modified_optimization_statuses( $data[ $imagifybeat_id ] );

		if ( ! $response[ $imagifybeat_id ] ) {
			return $response;
		}

		// Sanitize received data and grab some other info.
		foreach ( $response[ $imagifybeat_id ] as $context_id => $media_atts ) {
			$process = imagify_get_optimization_process( $media_atts['media_id'], $media_atts['context'] );

			$response[ $imagifybeat_id ][ $context_id ] = get_imagify_media_column_content( $process, false );
		}

		return $response;
	}

	/**
	 * Look for media where status has changed, compared to what Imagifybeat sends.
	 * This is used in the custom folders list (the "Other Media" page).
	 *
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
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

		$response[ $imagifybeat_id ] = $this->get_modified_optimization_statuses( $data[ $imagifybeat_id ] );

		if ( ! $response[ $imagifybeat_id ] ) {
			return $response;
		}

		$admin_ajax_post = \Imagify_Admin_Ajax_Post::get_instance();
		$list_table      = new \Imagify_Files_List_Table( [
			'screen' => 'imagify-files',
		] );

		// Sanitize received data and grab some other info.
		foreach ( $response[ $imagifybeat_id ] as $context_id => $media_atts ) {
			$process = imagify_get_optimization_process( $media_atts['media_id'], $media_atts['context'] );

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
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $data The data received.
	 * @return array
	 */
	public function get_modified_optimization_statuses( $data ) {
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
			$media_ids = array_map( function( $media_id ) {
				return (int) substr( $media_id, 1 );
			}, $media_ids );
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
	 * Get an Imagifybeat ID, given an action.
	 *
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
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
	 * @since  1.9.3
	 * @access private
	 * @author Grégory Viguier
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
