<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Get NGG Bulk Optimization screen ID.
 * Because WP nonsense, the screen ID depends on the menu title, which is translated. So the screen ID changes depending on the administration locale.
 *
 * @since  1.6.13
 * @author Grégory Viguier
 *
 * @return string
 */
function imagify_get_ngg_bulk_screen_id() {
	global $admin_page_hooks;

	$ngg_menu_slug = defined( 'NGGFOLDER' ) ? plugin_basename( NGGFOLDER ) : 'nextgen-gallery';
	$ngg_menu_slug = isset( $admin_page_hooks[ $ngg_menu_slug ] ) ? $admin_page_hooks[ $ngg_menu_slug ] : 'gallery';

	return $ngg_menu_slug . '_page_' . imagify_get_ngg_bulk_screen_slug();
}

/**
 * Get NGG Bulk Optimization screen slug.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @return string
 */
function imagify_get_ngg_bulk_screen_slug() {
	return IMAGIFY_SLUG . '-ngg-bulk-optimization';
}
