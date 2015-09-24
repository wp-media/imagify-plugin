<?php
defined( 'ABSPATH' ) or	die( 'Cheatin&#8217; uh?' );

/*
 * Tell WP what to do when admin is loaded aka upgrader
 *
 * @since 1.0
 */
add_action( 'admin_init', '_imagify_upgrader' );
function _imagify_upgrader() {
	$current_version = get_imagify_option( 'version' );

	// You can hook the upgrader to trigger any action when Imagify is upgraded
	// first install
	if ( ! $current_version ) {
		do_action( 'imagify_first_install' );
	}
	// already installed but got updated
	elseif ( IMAGIFY_VERSION != $current_version ) {
		do_action( 'imagify_upgrade', IMAGIFY_VERSION, $current_version );
	}

	// If any upgrade has been done, we flush and update version #
	if ( did_action( 'imagify_first_install' ) || did_action( 'imagify_upgrade' ) ) {
		$options            = get_site_option( IMAGIFY_SETTINGS_SLUG ); // do not use get_imagify_option() here
		$options['version'] = IMAGIFY_VERSION;

		update_site_option( IMAGIFY_SETTINGS_SLUG, $options );
	}
}

/**
 * Keeps this function up to date at each version
 *
 * @since 1.0
 */
add_action( 'imagify_first_install', '_imagify_first_install' );
function _imagify_first_install() {	
	// Set a transient to know when we will have to display a notice to ask the user to rate the plugin.
	set_site_transient( 'imagify_seen_rating_notice', true, DAY_IN_SECONDS * 7 );
	
	// Create Options
	add_site_option( IMAGIFY_SETTINGS_SLUG,
		array(
			'api_key'            => '',
			'optimization_level' => 1,
			'auto_optimize'      => 1,
			'backup'             => 1,
			'disallowed-sizes'	 => array()
		)
	);
}

/**
 * What to do when Imagify is updated, depending on versions
 *
 * @since 1.0
 */
add_action( 'imagify_upgrade', '_imagify_new_upgrade', 10, 2 );
function _imagify_new_upgrade( $imagify_version, $current_version )
{
	// Not yet
}