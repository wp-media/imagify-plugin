<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Get the NGG top-level admin menu parent slug.
 *
 * NextGEN Gallery v4.x changed its top-level menu slug from NGGFOLDER
 * ('nextgen-gallery') to 'imagely'. Return the correct parent slug so
 * submenu registrations always target the visible menu.
 *
 * @since  2.2.9
 *
 * @return string
 */
function imagify_get_ngg_parent_menu_slug() {
	if ( class_exists( 'Imagely\NGG\Admin\App' ) ) {
		return 'imagely';
	}

	return defined( 'NGGFOLDER' ) ? NGGFOLDER : 'nextgen-gallery';
}

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

	$ngg_menu_slug = imagify_get_ngg_parent_menu_slug();
	$ngg_menu_slug = isset( $admin_page_hooks[ $ngg_menu_slug ] ) ? $admin_page_hooks[ $ngg_menu_slug ] : sanitize_title( $ngg_menu_slug );

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
