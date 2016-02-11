<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Add submenu in menu "Settings"
 *
 * @since 1.0
 */
add_action( ( imagify_is_active_for_network() ? 'network_' : '' ) . 'admin_menu', '_imagify_settings_menu' );
function _imagify_settings_menu() {
	$page = imagify_is_active_for_network() ? 'settings.php' : 'options-general.php';
	$cap  = imagify_is_active_for_network() ? 'manage_network_options' : 'manage_options';

	add_submenu_page( $page, 'Imagify', 'Imagify', apply_filters( 'imagify_capacity', $cap ), IMAGIFY_SLUG, '_imagify_display_options_page' );
}

/**
 * Add submenu in menu "Media"
 *
 * @since 1.0
 */
add_action( 'admin_menu', '_imagify_bulk_optimization_menu' );
function _imagify_bulk_optimization_menu() {
	add_media_page( __( 'Bulk Optimization', 'imagify' ), __( 'Bulk Optimization', 'imagify' ), apply_filters( 'imagify_capacity', 'manage_options' ), IMAGIFY_SLUG . '-bulk-optimization', '_imagify_display_bulk_page' );
}