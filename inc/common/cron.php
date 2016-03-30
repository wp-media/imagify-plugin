<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Planning cron
 * If the task is not programmed, it is automatically triggered
 *
 * @since 1.4.2
 */
add_action( 'init', '_imagify_rating_scheduled' );
function _imagify_rating_scheduled() {
	if ( ! wp_next_scheduled( 'imagify_rating_event' ) && ! get_site_transient( 'do_imagify_rating_cron' ) ) {
		wp_schedule_event( time(), 'daily', 'imagify_rating_event' );
	}
}

/*
 * Saved the user images count to display it later
 * in a notice message to ask him to rate Imagify on WordPress.org
 *
 * @since 1.4.2
 */
add_action( 'imagify_rating_event', '_do_imagify_rating_cron' );
function _do_imagify_rating_cron() {	
	// Stop the process if the plugin isn't installed since 3 days
	if ( get_site_transient( 'imagify_seen_rating_notice' ) ) {
		return;
	}

	// Check if the Imagify servers & the API are accessible
	if ( ! is_imagify_servers_up() ) {
		return;
	}
	
	$user = get_imagify_user();
	
	if ( isset( $user ) && (int) $user->image_count > 100 ) {
		set_site_transient( 'imagify_user_images_count', $user->image_count );
	}
}