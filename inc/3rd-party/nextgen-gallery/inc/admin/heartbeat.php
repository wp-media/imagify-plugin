<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

global $pagenow;

add_filter( 'heartbeat_received', '_imagify_ngg_heartbeat_received', 10, 2 );
/**
 * Prepare the data that goes back with the Heartbeat API.
 *
 * @since 1.5
 *
 * @param  array $response  The Heartbeat response.
 * @param  array $data      The $_POST data sent.
 * @return array
 */
function _imagify_ngg_heartbeat_received( $response, $data ) {
	if ( ! isset( $data['imagify_heartbeat'] ) || 'update_ngg_bulk_data' !== $data['imagify_heartbeat'] ) {
		return $response;
	}

	add_filter( 'imagify_count_saving_data', 'imagify_ngg_count_saving_data', 8 );
	$saving_data = imagify_count_saving_data();
	$user        = new Imagify_User();

	$response['imagify_bulk_data'] = array(
		// User account.
		'unconsumed_quota'              => is_wp_error( $user ) ? 0 : $user->get_percent_unconsumed_quota(),
		// Global chart.
		'optimized_attachments_percent' => imagify_ngg_percent_optimized_attachments(),
		'unoptimized_attachments'       => imagify_ngg_count_unoptimized_attachments(),
		'optimized_attachments'         => imagify_ngg_count_optimized_attachments(),
		'errors_attachments'            => imagify_ngg_count_error_attachments(),
		// Stats block.
		'already_optimized_attachments' => number_format_i18n( $saving_data['count'] ),
		'original_human'                => imagify_size_format( $saving_data['original_size'], 1 ),
		'optimized_human'               => imagify_size_format( $saving_data['optimized_size'], 1 ),
		'optimized_percent'             => $saving_data['percent'],
	);

	return $response;
}

/**
 * Update the Heartbeat API settings.
 *
 * @since 1.5
 */
if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && imagify_get_ngg_bulk_screen_slug() === $_GET['page'] ) { // WPCS: CSRF ok.
	add_filter( 'heartbeat_settings', '_imagify_heartbeat_settings', IMAGIFY_INT_MAX );
}
