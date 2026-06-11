<?php
defined( 'ABSPATH' ) || die( "Cheatin' uh?" );

add_action( 'admin_menu', '_imagify_ngg_bulk_optimization_menu' );
/**
 * Add submenu in menu "Media"
 *
 * @since  1.5
 * @author Jonathan Buttigieg
 */
function _imagify_ngg_bulk_optimization_menu() {
	$capacity    = imagify_get_context( 'ngg' )->get_capacity( 'bulk-optimize' );
	$parent_slug = imagify_get_ngg_parent_menu_slug();

	add_submenu_page(
		$parent_slug,
		__( 'Bulk Optimization', 'imagify' ),
		__( 'Bulk Optimization', 'imagify' ),
		$capacity,
		imagify_get_ngg_bulk_screen_slug(),
		function () {
			Imagify_Views::get_instance()->display_bulk_page();
		}
	);
}
