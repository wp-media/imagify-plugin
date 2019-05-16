<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

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
	$bulk_screen_id = imagify_get_ngg_bulk_screen_id();

	if ( ! imagify_is_screen( $bulk_screen_id ) ) {
		return;
	}

	$assets->remove_deferred_localization( 'bulk', 'imagifyBulk' );

	$l10n = $assets->get_localization_data( 'bulk', [
		'bufferSizes' => [
			'ngg' => 4,
		],
	] );

	/** This filter is documented in inc/functions/i18n.php */
	$l10n['bufferSizes'] = apply_filters( 'imagify_bulk_buffer_sizes', $l10n['bufferSizes'] );

	$assets->enqueue_assets( [ 'pricing-modal', 'bulk' ] )->localize( 'imagifyBulk', $l10n );
}
