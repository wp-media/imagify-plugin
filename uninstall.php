<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || die( 'Cheatin&#8217; uh?' );

global $wpdb;

// Delete Imagify options.
delete_site_option( 'imagify_settings' );
delete_site_option( 'imagify_old_version' );
delete_site_option( 'imagify_files_db_version' );
delete_site_option( 'imagify_folders_db_version' );
delete_site_option( 'do_imagify_rating_cron' );
delete_site_option( 'imagify_seen_rating_notice' );
delete_site_option( 'imagify_user_images_count' );
delete_option( 'imagify_data' );
delete_option( 'ngg_imagify_data_db_version' );
delete_option( $wpdb->prefix . 'ngg_imagify_data_db_version' );

// Delete all transients.
delete_site_transient( 'imagify_check_licence_1' );
delete_site_transient( 'imagify_user' );
delete_site_transient( 'imagify_themes_plugins_to_sync' );
delete_transient( 'imagify_bulk_optimization_level' );
delete_transient( 'imagify_bulk_optimization_infos' );
delete_transient( 'imagify_large_library' );
delete_transient( 'imagify_max_image_size' );
delete_transient( 'imagify_user' );

// Delete transients.
$transients = implode( '" OR option_name LIKE "', array(
	'_transient_%imagify-async-in-progress-%',
	'_transient_%imagify-ngg-async-in-progress-%',
	'_site_transient_%imagify-file-async-in-progress-%',
	'_transient_%imagify_rpc_%',
) );
$wpdb->query( 'DELETE from ' . $wpdb->options . ' WHERE option_name LIKE "' . $transients . '"' ); // WPCS: unprepared SQL ok.

// Clear scheduled hooks.
wp_clear_scheduled_hook( 'imagify_rating_event' );
wp_clear_scheduled_hook( 'imagify_update_library_size_calculations_event' );

// Delete all user meta related to Imagify.
delete_metadata( 'user', '', '_imagify_ignore_notices', '', true );

// Drop the tables.
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . 'imagify_files' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . 'imagify_folders' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'ngg_imagify_data' );
