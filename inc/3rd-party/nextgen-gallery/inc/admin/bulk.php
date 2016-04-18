<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Update all Imagify stats for NGG Bulk Optimization
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
add_action( 'admin_init', '_imagify_ngg_update_bulk_stats' );
function _imagify_ngg_update_bulk_stats() {	
	if ( isset( $_GET['page'] ) && 'imagify-ngg-bulk-optimization' === $_GET['page'] ) {
		add_filter( 'imagify_count_attachments' 			, 'imagify_ngg_count_attachments' );	
		add_filter( 'imagify_count_optimized_attachments' 	, 'imagify_ngg_count_optimized_attachments' );
		add_filter( 'imagify_count_error_attachments'	  	, 'imagify_ngg_count_error_attachments' );
		add_filter( 'imagify_count_unoptimized_attachments'	, 'imagify_ngg_count_unoptimized_attachments' );
		add_filter( 'imagify_percent_optimized_attachments'	, 'imagify_ngg_percent_optimized_attachments' );
		add_filter( 'imagify_count_saving_data'	  		  	, 'imagify_ngg_count_saving_data' );
	}
}