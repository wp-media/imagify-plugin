<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Add Imagify menu in the admin bar
 *
 * @since 1.0
 */
add_action( 'admin_bar_menu', '_imagify_admin_bar', PHP_INT_MAX );
function _imagify_admin_bar( $wp_admin_bar )
{
	if ( ! current_user_can( 'upload_files' ) ) {
		return;
	}
	
	// Parent
    $wp_admin_bar->add_menu( array(
	    'id'    => 'imagify',
	    'title' => 'Imagify',
	    'href'  => get_imagify_admin_url(),
	));
	
	$cap = ( imagify_is_active_for_network() ) ? 'manage_network_options' : 'manage_options';
	/** This filter is documented in inc/admin/options.php */
	if (  current_user_can( apply_filters( 'imagify_capacity', $cap ) ) )  {
		// Settings
		$wp_admin_bar->add_menu(array(
			'parent' => 'imagify',
			'id' 	 => 'imagify-settings',
			'title'  => __( 'Settings' ),
		    'href'   => get_imagify_admin_url(),
		));	
	}
	
	// Bulk Optimization
	if ( imagify_valid_key() && ! is_network_admin() && current_user_can( 'upload_files' ) ) {
		$wp_admin_bar->add_menu(array(
			'parent' => 'imagify',
			'id' 	 => 'imagify-bulk-optimization',
			'title'  => __( 'Bulk Optimization', 'imagify' ),
		    'href'   => get_imagify_admin_url( 'bulk-optimization' ),
		));
	}

	// TO DO - Rate it & Support
}