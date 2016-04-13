<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Create the Imagify table needed for NGG compatibility
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
add_action( 'admin_init' , '_imagify_create_ngg_table' );
function _imagify_create_ngg_table() {
	global $wpdb;
	
	if ( ! get_option( $wpdb->prefix . 'ngg_imagify_data_db_version' ) ) {
		$db = new Imagify_NGG_DB();
		$db->create_table();
	}
}