<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || die( 'Cheatin&#8217; uh?' );

global $wpdb;

// Delete Imagify options.
delete_site_option( 'imagify_settings' );

// Delete all transients.
delete_site_transient( 'imagify_check_licence_1' );
delete_transient( 'imagify_bulk_optimization_level' );
delete_transient( 'imagify_large_library' );
delete_transient( 'imagify_max_image_size' );

// Delete transients.
$transients = implode( '" OR option_name LIKE "', array(
	'_transient_%imagify-async-in-progress-%',
	'_transient_%imagify-ngg-async-in-progress-%',
	'_transient_%imagify_rpc_%',
) );
$wpdb->query( 'DELETE from ' . $wpdb->options . ' WHERE option_name LIKE "' . $transients . '"' ); // WPCS: unprepared SQL ok.

// Clear scheduled hooks.
wp_clear_scheduled_hook( 'imagify_rating_event' );
wp_clear_scheduled_hook( 'imagify_update_library_size_calculations_event' );

// Delete all user meta related to Imagify.
delete_metadata( 'user', '', '_imagify_ignore_notices', '', true );

// Drop the tables.
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'ngg_imagify_data' );
