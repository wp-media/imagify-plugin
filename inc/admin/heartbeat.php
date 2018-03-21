<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

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
	if ( isset( $data['imagify_heartbeat'], $data['imagify_types'] ) && 'update_bulk_data' === $data['imagify_heartbeat'] ) {
		$folder_types = is_array( $data['imagify_types'] ) ? array_flip( array_filter( $data['imagify_types'] ) ) : array();
		$response['imagify_bulk_data'] = imagify_get_bulk_stats( $folder_types, array(
			'fullset' => true,
		) );
	}

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
