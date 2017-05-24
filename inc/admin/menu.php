<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_action( ( imagify_is_active_for_network() ? 'network_' : '' ) . 'admin_menu', '_imagify_settings_menu' );
/**
 * Add submenu in menu "Settings".
 *
 * @since 1.0
 */
function _imagify_settings_menu() {
	$page = imagify_is_active_for_network() ? 'settings.php' : 'options-general.php';

	add_submenu_page( $page, 'Imagify', 'Imagify', imagify_get_capacity(), IMAGIFY_SLUG, '_imagify_display_options_page' );
}

add_action( 'admin_menu', '_imagify_bulk_optimization_menu' );
/**
 * Add submenu in menu "Media".
 *
 * @since 1.0
 */
function _imagify_bulk_optimization_menu() {
	add_media_page( __( 'Bulk Optimization', 'imagify' ), __( 'Bulk Optimization', 'imagify' ), imagify_get_capacity( true ), IMAGIFY_SLUG . '-bulk-optimization', '_imagify_display_bulk_page' );
}
