<?php
// If uninstall not called from WordPress exit
defined( 'WP_UNINSTALL_PLUGIN' ) or die( 'Cheatin&#8217; uh?' );

// Delete Imagify options
delete_site_option( 'imagify_settings' );

// Delete all transients
delete_site_transient( 'imagify_check_licence_1' );
delete_site_transient( 'imagify_bulk_optimization_level' );
delete_site_transient( 'imagify_large_library' );

global $wpdb;
// WP transients
$wpdb->query( 'DELETE from ' . $wpdb->options . ' WHERE option_name LIKE "_transient_imagify-async-in-progress-%"' );

// NextGen Gallery transients
$wpdb->query( 'DELETE from ' . $wpdb->options . ' WHERE option_name LIKE "_transient_imagify-ngg-async-in-progress-%"' );

// Delete all user meta related to Imagify
delete_metadata( 'user', '', '_imagify_ignore_notices', '', true );

// Drop the tables
$wpdb->query( "DROP TABLE IF EXISTS $wpdb->ngg_imagify_data" );
