<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_action( 'admin_bar_menu', '_imagify_admin_bar', PHP_INT_MAX );
/**
 * Add Imagify menu in the admin bar.
 *
 * @since 1.0
 *
 * @param object $wp_admin_bar WP_Admin_Bar instance, passed by reference.
 */
function _imagify_admin_bar( $wp_admin_bar ) {
	if ( ! current_user_can( imagify_get_capacity() ) || ! get_imagify_option( 'admin_bar_menu', 0 ) ) {
		return;
	}

	// Parent.
	$wp_admin_bar->add_menu( array(
		'id'    => 'imagify',
		'title' => 'Imagify',
		'href'  => get_imagify_admin_url(),
	) );

	// Settings.
	$wp_admin_bar->add_menu(array(
		'parent' => 'imagify',
		'id'     => 'imagify-settings',
		'title'  => __( 'Settings' ),
		'href'   => get_imagify_admin_url(),
	) );

	// Bulk Optimization.
	if ( ! is_network_admin() ) {
		$wp_admin_bar->add_menu(array(
			'parent' => 'imagify',
			'id'     => 'imagify-bulk-optimization',
			'title'  => __( 'Bulk Optimization', 'imagify' ),
			'href'   => get_imagify_admin_url( 'bulk-optimization' ),
		) );
	}

	// Rate it.
	$wp_admin_bar->add_menu(array(
		'parent' => 'imagify',
		'id'     => 'imagify-rate-it',
		/* translators: %s is WordPress.org. */
		'title'  => sprintf( __( 'Rate Imagify on %s', 'imagify' ), 'WordPress.org' ),
		'href'   => 'https://wordpress.org/support/view/plugin-reviews/imagify?rate=5#postform',
	) );

	// Quota & Profile informations.
	if ( defined( 'IMAGIFY_HIDDEN_ACCOUNT' ) && IMAGIFY_HIDDEN_ACCOUNT ) {
		return;
	}

	if ( ( defined( 'IMAGIFY_API_KEY' ) && IMAGIFY_API_KEY ) || get_imagify_option( 'api_key', false ) ) {
		$wp_admin_bar->add_menu( array(
			'parent' => 'imagify',
			'id'     => 'imagify-profile',
			'title'  => wp_nonce_field( 'imagify-get-admin-bar-profile', 'imagifygetadminbarprofilenonce', false, false ) . '<div id="wp-admin-bar-imagify-profile-loading">' . __( 'Loading...', 'imagify' ) . '</div><div id="wp-admin-bar-imagify-profile-content"></div>',
		) );
	}
}

add_action( 'admin_bar_init', '_imagify_admin_bar_styles' );
/**
 * Include Admin Bar Profile informations styles in front.
 *
 * @since  1.2
 */
function _imagify_admin_bar_styles() {
	if ( is_admin() || ! get_imagify_option( 'admin_bar_menu', 0 ) ) {
		return;
	}

	$css_ext = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.css' : '.min.css';
	wp_enqueue_style( 'imagify-css-admin-bar', IMAGIFY_ASSETS_CSS_URL . 'admin-bar' . $css_ext, array(), IMAGIFY_VERSION, 'all' );
}
