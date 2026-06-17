<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/classes/Tools/InternalStateList.php';

global $wpdb;

// Delete Imagify options.
delete_site_option( 'imagify_settings' );
delete_site_option( 'imagify_old_version' );
delete_site_option( 'imagify_files_db_version' );
delete_site_option( 'imagify_folders_db_version' );
delete_option( 'imagify_data' );
delete_option( 'ngg_imagify_data_db_version' );
delete_option( $wpdb->prefix . 'ngg_imagify_data_db_version' );

// Delete all transients.
delete_site_transient( 'imagify_activation' );
delete_site_transient( 'imagify_check_licence_1' );
delete_site_transient( 'imagify_check_api_version' );
delete_site_transient( 'imagify_user' );
delete_site_transient( 'imagify_themes_plugins_to_sync' );
delete_site_transient( 'do_imagify_rating_cron' );
delete_site_transient( 'imagify_seen_rating_notice' );
delete_site_transient( 'imagify_user_images_count' );
delete_transient( 'imagify_activation' );
delete_transient( 'imagify_large_library' );
delete_transient( 'imagify_max_image_size' );
delete_transient( 'imagify_user' );
delete_transient( 'imagify_stat_without_next_gen' );
delete_transient( 'imagify_attachments_number_modal' );
delete_transient( 'imagify_user_cache' );
foreach ( \Imagify\Tools\InternalStateList::get_bulk_transients() as $transient ) {
	delete_transient( $transient );
}

// Delete transients.
foreach ( \Imagify\Tools\InternalStateList::get_locked_transient_patterns() as $pattern ) {
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $pattern ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
}

// Clear scheduled hooks.
wp_clear_scheduled_hook( 'imagify_rating_event' );
wp_clear_scheduled_hook( 'imagify_update_library_size_calculations_event' );

// Delete all user meta related to Imagify.
delete_metadata( 'user', '', '_imagify_ignore_notices', '', true );

// Drop the tables.
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . 'imagify_files' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . 'imagify_folders' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'ngg_imagify_data' );
