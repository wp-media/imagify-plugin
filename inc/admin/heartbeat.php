<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

global $pagenow;

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
	if ( empty( $data['imagify_ids']['update_bulk_data'] ) ) {
		return $response;
	}

	if ( empty( $data['imagify_types'] ) || ! is_array( $data['imagify_types'] ) ) {
		return $response;
	}

	$folder_types = array_flip( array_filter( $data['imagify_types'] ) );

	$response['imagify_bulk_data'] = imagify_get_bulk_stats( $folder_types, array(
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
	if ( empty( $data['imagify_ids']['update_bulk_requirements'] ) ) {
		return $response;
	}

	$response['imagify_bulk_requirements'] = array(
		'curl_missing'          => ! Imagify_Requirements::supports_curl(),
		'editor_missing'        => ! Imagify_Requirements::supports_image_editor(),
		'external_http_blocked' => Imagify_Requirements::is_imagify_blocked(),
		'api_down'              => Imagify_Requirements::is_imagify_blocked() || ! Imagify_Requirements::is_api_up(),
		'key_is_valid'          => ! Imagify_Requirements::is_imagify_blocked() && Imagify_Requirements::is_api_up() && Imagify_Requirements::is_api_key_valid(),
		'is_over_quota'         => ! Imagify_Requirements::is_imagify_blocked() && Imagify_Requirements::is_api_up() && Imagify_Requirements::is_api_key_valid() && Imagify_Requirements::is_over_quota(),
	);

	return $response;
}


if ( 'upload.php' === $pagenow && ! empty( $_GET['page'] ) && Imagify_Views::get_instance()->get_bulk_page_slug() === $_GET['page'] ) { // WPCS: CSRF ok.
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
