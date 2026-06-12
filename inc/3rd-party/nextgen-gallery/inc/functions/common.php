<?php
defined( 'ABSPATH' ) || die( "Cheatin' uh?" );

/**
 * Tell if the NGG v3 POPE storage framework is available.
 * Returns true when both Mixin (POPE base class) and C_Gallery_Storage exist, i.e. NGG v3 is active.
 * On NGG v4 these classes are absent, so the function returns false.
 *
 * @since  2.3
 *
 * @return bool
 */
function imagify_ngg_has_pope_storage() {
	return class_exists( 'Mixin' ) && class_exists( 'C_Gallery_Storage' );
}

/**
 * Get the correct NGG top-level admin menu slug for the current NGG version.
 * On NGG v4.x the top-level slug changed from 'nextgen-gallery' (or NGGFOLDER basename) to 'imagely'.
 *
 * @since  2.3
 *
 * @return string
 */
function imagify_get_ngg_parent_menu_slug() {
	// @codeCoverageIgnoreStart — requires live NGG v4 classes or NGG v3 constant; not available in unit tests.
	if ( class_exists( 'Imagely\NGG\Admin\App' ) ) {
		return 'imagely';
	}

	if ( defined( 'NGGFOLDER' ) ) {
		return plugin_basename( NGGFOLDER );
	}
	// @codeCoverageIgnoreEnd

	return 'nextgen-gallery';
}

/**
 * Get the correct NGG manage-gallery URL slug for the current NGG version.
 * On NGG v4.x, the manage gallery lives under the 'imagely' SPA.
 * On NGG v3.x, the dedicated slug is 'nggallery-manage-gallery'.
 *
 * @since  2.3
 *
 * @return string The page slug (without 'admin.php?page=').
 */
function imagify_get_ngg_manage_gallery_url() {
	// @codeCoverageIgnoreStart — requires live NGG v4 class; not available in unit tests.
	if ( class_exists( 'Imagely\NGG\Admin\App' ) ) {
		return imagify_get_ngg_parent_menu_slug();
	}
	// @codeCoverageIgnoreEnd

	return 'nggallery-manage-gallery';
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

	// On NGG v4.x the parent slug is 'imagely'; on v3.x it is the NGGFOLDER basename or 'nextgen-gallery'.
	$parent_slug   = imagify_get_ngg_parent_menu_slug();
	$ngg_menu_slug = isset( $admin_page_hooks[ $parent_slug ] ) ? $admin_page_hooks[ $parent_slug ] : sanitize_title( $parent_slug );

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
