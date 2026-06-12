<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', '_imagify_ngg_fix_spa_bulk_link' );
/**
 * Add a capture-phase click shim so the NGG v4 React SPA router does not intercept
 * clicks on the Imagify bulk-optimization submenu link.
 *
 * NGG v4 attaches a bubble-phase click listener to the document that matches all
 * <a> tags and hijacks navigation through its React router. Because capture-phase
 * listeners fire before bubble-phase ones, attaching our own capture-phase listener
 * on the document lets us call stopImmediatePropagation() and force a real browser
 * navigation via window.location.href before the SPA router ever sees the event.
 *
 * Note: if NGG v4 renders its admin sidebar inside a shadow DOM, this listener will
 * NOT fire (shadow boundaries retarget events). In that case a different approach
 * (attaching inside the shadow root) would be needed — verify on a live v4 install.
 *
 * @since 2.3
 */
function _imagify_ngg_fix_spa_bulk_link() {
	// @codeCoverageIgnoreStart — requires live NGG v4 class; not available in unit tests.
	if ( ! class_exists( 'Imagely\NGG\Admin\App' ) ) {
		return;
	}

	$bulk_slug = esc_js( imagify_get_ngg_bulk_screen_slug() );

	wp_add_inline_script(
		'jquery',
		"(function(){
			document.addEventListener('click', function(event){
				var a = event.target.closest('a[href*=\"page=" . $bulk_slug . "\"]');
				if (!a) { return; }
				event.stopImmediatePropagation();
				event.preventDefault();
				window.location.href = a.href;
			}, true);
		})();"
	);
	// @codeCoverageIgnoreEnd
}

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
	if (
		imagify_is_screen( 'nggallery-manage-images' )
		||
		(
			isset( $_GET['gid'] ) && ! empty( $_GET['pid'] ) && imagify_is_screen( 'nggallery-manage-gallery' ) // WPCS: CSRF ok.
		)
	) {
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

	$l10n = $assets->get_localization_data(
		'bulk',
		[
			'bufferSizes' => [
				'ngg' => 4,
			],
		]
	);

	/** This filter is documented in inc/functions/i18n.php */
	$l10n['bufferSizes'] = apply_filters( 'imagify_bulk_buffer_sizes', $l10n['bufferSizes'] );

	$assets->enqueue_assets( [ 'pricing-modal', 'bulk' ] )->localize( 'imagifyBulk', $l10n );
}
