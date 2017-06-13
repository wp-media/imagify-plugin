<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_action( 'admin_menu', '_imagify_ngg_bulk_optimization_menu' );
/**
 * Add submenu in menu "Media"
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
function _imagify_ngg_bulk_optimization_menu() {
	if ( ! defined( 'NGGFOLDER' ) ) {
		return;
	}

	add_submenu_page( NGGFOLDER, __( 'Bulk Optimization', 'imagify' ), __( 'Bulk Optimization', 'imagify' ), imagify_get_capacity( true ), IMAGIFY_SLUG . '-ngg-bulk-optimization', '_imagify_display_bulk_page' );
}
