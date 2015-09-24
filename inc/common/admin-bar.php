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
	if ( ! current_user_can( apply_filters( 'rocket_capacity', 'manage_options' ) ) )  {
		return;
	}

	// Parent
    $wp_admin_bar->add_menu( array(
	    'id'    => 'imagify',
	    'title' => 'Imagify',
	    'href'  => get_imagify_admin_url(),
	));

	// Settings
	$wp_admin_bar->add_menu(array(
		'parent' => 'imagify',
		'id' 	 => 'imagify-settings',
		'title'  => __( 'Settings', 'imagify' ),
	    'href'   => get_imagify_admin_url(),
	));

	// Bulk Optimization
	if ( imagify_valid_key() && ! is_network_admin() ) {
		$wp_admin_bar->add_menu(array(
			'parent' => 'imagify',
			'id' 	 => 'imagify-bulk-optimization',
			'title'  => __( 'Bulk Optimization', 'imagify' ),
		    'href'   => get_imagify_admin_url( 'bulk-optimization' ),
		));
	}

	// TO DO - Rate it & Support
}