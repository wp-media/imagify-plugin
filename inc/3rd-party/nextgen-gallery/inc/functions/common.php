<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Get user capacity to operate Imagify within NGG galleries.
 * It is meant to be used to filter 'imagify_capacity'.
 *
 * @since  1.6.11
 * @see    imagify_get_capacity()
 * @author Grégory Viguier
 *
 * @param string $capacity  The user capacity.
 * @param string $describer Capacity describer. See imagify_get_capacity() for possible values. Can also be a "real" user capacity.
 * @return string
 */
function imagify_get_ngg_capacity( $capacity = 'edit_post', $describer = 'manual-optimize' ) {
	if ( 'manual-optimize' === $describer ) {
		return 'NextGEN Manage gallery';
	}

	return $capacity;
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

	$ngg_menu_slug  = defined( 'NGGFOLDER' ) ? plugin_basename( NGGFOLDER ) : 'nextgen-gallery';
	$ngg_menu_slug  = isset( $admin_page_hooks[ $ngg_menu_slug ] ) ? $admin_page_hooks[ $ngg_menu_slug ] : 'gallery';

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
