<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_filter( 'heartbeat_received', '_imagify_heartbeat_received', 10, 2 );
/**
 * Prepare the data that goes back with the Heartbeat API.
 *
 * @since 1.4.5
 *
 * @param  array $response The Heartbeat response.
 * @param  array $data     The $_POST data sent.
 * @return array
 */
function _imagify_heartbeat_received( $response, $data ) {
	$heartbeat_id = 'imagify_bulk_data';

	if ( empty( $data[ $heartbeat_id ] ) ) {
		return $response;
	}

	$folder_types = array_flip( array_filter( $data[ $heartbeat_id ] ) );

	$response[ $heartbeat_id ] = imagify_get_bulk_stats( $folder_types, array(
		'fullset' => true,
	) );

	return $response;
}

add_filter( 'heartbeat_received', 'imagify_heartbeat_requirements_received', 10, 2 );
/**
 * Prepare the data that goes back with the Heartbeat API.
 *
 * @since  1.7.1
 * @author Grégory Viguier
 *
 * @param  array $response The Heartbeat response.
 * @param  array $data     The $_POST data sent.
 * @return array
 */
function imagify_heartbeat_requirements_received( $response, $data ) {
	$heartbeat_id = 'imagify_bulk_requirements';

	if ( empty( $data[ $heartbeat_id ] ) ) {
		return $response;
	}

	$response[ $heartbeat_id ] = array(
		'curl_missing'          => ! Imagify_Requirements::supports_curl(),
		'editor_missing'        => ! Imagify_Requirements::supports_image_editor(),
		'external_http_blocked' => Imagify_Requirements::is_imagify_blocked(),
		'api_down'              => Imagify_Requirements::is_imagify_blocked() || ! Imagify_Requirements::is_api_up(),
		'key_is_valid'          => ! Imagify_Requirements::is_imagify_blocked() && Imagify_Requirements::is_api_up() && Imagify_Requirements::is_api_key_valid(),
		'is_over_quota'         => ! Imagify_Requirements::is_imagify_blocked() && Imagify_Requirements::is_api_up() && Imagify_Requirements::is_api_key_valid() && Imagify_Requirements::is_over_quota(),
	);

	return $response;
}

add_filter( 'heartbeat_received', 'imagify_heartbeat_bulk_optimization_status_received', 10, 2 );
/**
 * Look for media where status has changed, compared to what Heartbeat sends.
 * This is used in the bulk optimization page.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  array $response The Heartbeat response.
 * @param  array $data     The $_POST data sent.
 * @return array
 */
function imagify_heartbeat_bulk_optimization_status_received( $response, $data ) {
	$heartbeat_id = 'imagify_bulk_queue';

	if ( empty( $data[ $heartbeat_id ] ) || ! is_array( $data[ $heartbeat_id ] ) ) {
		return $response;
	}

	$statuses = [];

	foreach ( $data[ $heartbeat_id ] as $item ) {
		if ( empty( $statuses[ $item['context'] ] ) ) {
			$statuses[ $item['context'] ] = [];
		}

		$statuses[ $item['context'] ][ '_' . $item['mediaID'] ] = 1;
	}

	$results = imagify_get_modified_optimization_statusses( $statuses );

	if ( ! $results ) {
		return $response;
	}

	$response[ $heartbeat_id ] = [];

	// Sanitize received data and grab some other info.
	foreach ( $results as $context_id => $media_atts ) {
		$process    = imagify_get_optimization_process( $media_atts['media_id'], $media_atts['context'] );
		$optim_data = $process->get_data();

		if ( $optim_data->is_optimized() ) {
			// Successfully optimized.
			$full_size_data              = $optim_data->get_size_data();
			$response[ $heartbeat_id ][] = [
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
			$response[ $heartbeat_id ][] = [
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

			$response[ $heartbeat_id ][] = [
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

add_filter( 'heartbeat_received', 'imagify_heartbeat_options_bulk_optimization_status_received', 10, 2 );
/**
 * Look for media where status has changed, compared to what Heartbeat sends.
 * This is used in the settings page.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  array $response The Heartbeat response.
 * @param  array $data     The $_POST data sent.
 * @return array
 */
function imagify_heartbeat_options_bulk_optimization_status_received( $response, $data ) {
	$heartbeat_id = 'imagify_options_bulk_queue';

	if ( empty( $data[ $heartbeat_id ] ) || ! is_array( $data[ $heartbeat_id ] ) ) {
		return $response;
	}

	$statuses = [];

	foreach ( $data[ $heartbeat_id ] as $item ) {
		if ( empty( $statuses[ $item['context'] ] ) ) {
			$statuses[ $item['context'] ] = [];
		}

		$statuses[ $item['context'] ][ '_' . $item['mediaID'] ] = 1;
	}

	$results = imagify_get_modified_optimization_statusses( $statuses );

	if ( ! $results ) {
		return $response;
	}

	$response[ $heartbeat_id ] = [];

	foreach ( $results as $result ) {
		$response[ $heartbeat_id ][] = [
			'mediaID' => $result['media_id'],
			'context' => $result['context'],
		];
	}

	return $response;
}

add_filter( 'heartbeat_received', 'imagify_heartbeat_optimization_status_received', 10, 2 );
/**
 * Look for media where status has changed, compared to what Heartbeat sends.
 * This is used in the WP Media Library.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  array $response The Heartbeat response.
 * @param  array $data     The $_POST data sent.
 * @return array
 */
function imagify_heartbeat_optimization_status_received( $response, $data ) {
	$heartbeat_id = get_imagify_localize_script_translations( 'media-modal' );
	$heartbeat_id = $heartbeat_id['heartbeatId'];

	if ( empty( $data[ $heartbeat_id ] ) || ! is_array( $data[ $heartbeat_id ] ) ) {
		return $response;
	}

	$response[ $heartbeat_id ] = imagify_get_modified_optimization_statusses( $data[ $heartbeat_id ] );

	if ( ! $response[ $heartbeat_id ] ) {
		return $response;
	}

	// Sanitize received data and grab some other info.
	foreach ( $response[ $heartbeat_id ] as $context_id => $media_atts ) {
		$process = imagify_get_optimization_process( $media_atts['media_id'], $media_atts['context'] );

		$response[ $heartbeat_id ][ $context_id ] = get_imagify_media_column_content( $process, false );
	}

	return $response;
}

add_filter( 'heartbeat_received', 'imagify_heartbeat_custom_folders_optimization_status_received', 10, 2 );
/**
 * Look for media where status has changed, compared to what Heartbeat sends.
 * This is used in the custom folders list (the "Other Media" page).
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  array $response The Heartbeat response.
 * @param  array $data     The $_POST data sent.
 * @return array
 */
function imagify_heartbeat_custom_folders_optimization_status_received( $response, $data ) {
	$heartbeat_id = get_imagify_localize_script_translations( 'files-list' );
	$heartbeat_id = $heartbeat_id['heartbeatId'];

	if ( empty( $data[ $heartbeat_id ] ) || ! is_array( $data[ $heartbeat_id ] ) ) {
		return $response;
	}

	$response[ $heartbeat_id ] = imagify_get_modified_optimization_statusses( $data[ $heartbeat_id ] );

	if ( ! $response[ $heartbeat_id ] ) {
		return $response;
	}

	$admin_ajax_post = Imagify_Admin_Ajax_Post::get_instance();
	$list_table      = new Imagify_Files_List_Table( [
		'screen' => 'imagify-files',
	] );

	// Sanitize received data and grab some other info.
	foreach ( $response[ $heartbeat_id ] as $context_id => $media_atts ) {
		$process = imagify_get_optimization_process( $media_atts['media_id'], $media_atts['context'] );

		$response[ $heartbeat_id ][ $context_id ] = $admin_ajax_post->get_media_columns( $process, $list_table );
	}

	return $response;
}
/**
 * Look for media where status has changed, compared to what Heartbeat sends.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  array $data The data received.
 * @return array
 */
function imagify_get_modified_optimization_statusses( $data ) {
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

		Imagify_DB::cache_process_locks( $context, $media_ids );

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


if ( Imagify_Views::get_instance()->is_bulk_page() || Imagify_Views::get_instance()->is_settings_page() ) {
	add_filter( 'heartbeat_settings', '_imagify_heartbeat_settings', IMAGIFY_INT_MAX );
}
/**
 * Update the Heartbeat API settings.
 *
 * @since 1.4.5
 *
 * @param  array $settings Heartbeat API settings.
 * @return array
 */
function _imagify_heartbeat_settings( $settings ) {
	$settings['interval'] = 30;
	return $settings;
}
