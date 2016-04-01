<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

global $pagenow;

/**
 * Prepare the data that goes back with the Heartbeat API.
 *
 * @since 1.5
 */
add_filter( 'heartbeat_received', '_imagify_ngg_heartbeat_received', 10, 2 );
function _imagify_ngg_heartbeat_received( $response, $data ) {    
    if( isset( $data['imagify_heartbeat'] ) && 'update_ngg_bulk_data' === $data['imagify_heartbeat'] ) {
        add_filter( 'imagify_count_saving_data', 'imagify_ngg_count_saving_data' );
        $saving_data = imagify_count_saving_data();
		$user        = new Imagify_User();

        $response['imagify_bulk_data'] = array(
			'already_optimized_attachments' => $saving_data['count'],
			'optimized_attachments'         => imagify_ngg_count_optimized_attachments(),
			'unoptimized_attachments'       => imagify_ngg_count_unoptimized_attachments(),
			'errors_attachments'            => imagify_ngg_count_error_attachments(),
			'optimized_attachments_percent' => imagify_ngg_percent_optimized_attachments(),
			'optimized_percent'             => $saving_data['percent'],
			'original_human'                => size_format( $saving_data['original_size'], 1 ),
			'optimized_human'               => size_format( $saving_data['optimized_size'], 1 ),
			'unconsumed_quota'              => $user->get_percent_unconsumed_quota(),  
        );
    }
    
    return $response;
}

/**
 * Update the Heartbeat API settings
 *
 * @since 1.5
 */
if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'imagify-ngg-bulk-optimization' === $_GET['page'] ) {
	add_filter( 'heartbeat_settings', '_imagify_heartbeat_settings', PHP_INT_MAX );  
}