<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

global $pagenow;

/**
 * Update the Heartbeat API settings.
 *
 * @since 1.5
 */
if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && imagify_get_ngg_bulk_screen_slug() === $_GET['page'] ) { // WPCS: CSRF ok.
	add_filter( 'heartbeat_settings', '_imagify_heartbeat_settings', IMAGIFY_INT_MAX );
}
