<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_action( 'imagify_assets_enqueued', '_imagify_ngg_admin_print_styles' );
/**
 * Add some CSS and JS for NGG compatibility.
 *
 * @since  1.5
 * @since  1.6.10 Use the new class Imagify_Assets.
 * @author Jonathan Buttigieg
 * @author Grégory Viguier
 */
function _imagify_ngg_admin_print_styles() {
	global $admin_page_hooks;
	$assets = Imagify_Assets::get_instance();

	/**
	 * Manage Gallery Images.
	 */
	if ( imagify_is_screen( 'nggallery-manage-images' ) || isset( $_GET['gid'] ) && ! empty( $_GET['pid'] ) && imagify_is_screen( 'nggallery-manage-gallery' ) ) { // WPCS: CSRF ok.
		$assets->enqueue_style( 'admin' )->enqueue_script( 'library' );
		return;
	}

	/**
	 * NGG Bulk Optimization.
	 */
	// Because WP nonsense, the screen ID depends on the menu title, which is translated. So the screen ID changes depending on the administration locale.
	$ngg_menu_slug  = defined( 'NGGFOLDER' ) ? plugin_basename( NGGFOLDER ) : 'nextgen-gallery';
	$ngg_menu_slug  = isset( $admin_page_hooks[ $ngg_menu_slug ] ) ? $admin_page_hooks[ $ngg_menu_slug ] : 'gallery';
	$bulk_screen_id = $ngg_menu_slug . '_page_' . IMAGIFY_SLUG . '-ngg-bulk-optimization';

	if ( ! imagify_is_screen( $bulk_screen_id ) ) {
		return;
	}

	$assets->remove_deferred_localization( 'bulk', 'imagifyBulk' );

	$l10n = $assets->get_localization_data( 'bulk', array(
		'heartbeatId' => 'update_ngg_bulk_data',
		'ajaxAction'  => 'imagify_ngg_get_unoptimized_attachment_ids',
		'ajaxContext' => 'NGG',
		/** This filter is documented in inc/classes/class-imagify-assets.php */
		'bufferSize'  => apply_filters( 'imagify_bulk_buffer_size', 4 ),
	) );

	$assets->enqueue_assets( array( 'pricing-modal', 'bulk' ) )->localize( 'imagifyBulk', $l10n );

	// Intercom.
	add_action( 'admin_footer-' . $bulk_screen_id, array( $assets, 'print_support_script' ) );
}
