<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_action( 'admin_menu', '_imagify_ngg_bulk_optimization_menu' );
/**
 * Add Bulk Optimization submenu under the NextGEN Gallery admin menu.
 *
 * NGG v4.x changed its top-level menu slug from NGGFOLDER ('nextgen-gallery')
 * to 'imagely'. imagify_get_ngg_parent_menu_slug() picks the right slug so
 * the submenu appears in the visible sidebar regardless of NGG version.
 *
 * @since  1.5
 * @since  2.2.9 Use imagify_get_ngg_parent_menu_slug() for NGG v4.x compatibility.
 *               Use Imagify_Views directly instead of the deprecated wrapper.
 * @author Jonathan Buttigieg
 */
function _imagify_ngg_bulk_optimization_menu() {
	$capacity = imagify_get_context( 'ngg' )->get_capacity( 'bulk-optimize' );

	add_submenu_page(
		imagify_get_ngg_parent_menu_slug(),
		__( 'Bulk Optimization', 'imagify' ),
		__( 'Bulk Optimization', 'imagify' ),
		$capacity,
		imagify_get_ngg_bulk_screen_slug(),
		function() {
			Imagify_Views::get_instance()->display_bulk_page();
		}
	);
}
