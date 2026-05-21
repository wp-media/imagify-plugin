<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_head', '_imagify_ngg_fix_spa_bulk_link' );
/**
 * Prevent the NGG v4 React SPA from intercepting clicks on the Imagify Bulk
 * Optimization submenu link.
 *
 * NGG v4 attaches a bubble-phase click listener to the document that captures
 * all <a> clicks and routes them through its client-side router, which doesn't
 * know about the Imagify page and falls back to the Galleries tab. A capture-
 * phase listener registered before the SPA boots intercepts the click first
 * and forces a real browser navigation via window.location.href.
 *
 * @since 2.2.9
 */
function _imagify_ngg_fix_spa_bulk_link() {
	if ( ! class_exists( 'Imagely\NGG\Admin\App' ) ) {
		return;
	}
	$slug = esc_js( imagify_get_ngg_bulk_screen_slug() );
	?>
	<script>
	document.addEventListener( 'click', function( e ) {
		var a = e.target.closest( 'a[href*="page=<?php echo esc_attr( $slug ); ?>"]' );
		if ( a ) {
			e.stopImmediatePropagation();
			e.preventDefault();
			window.location.href = a.href;
		}
	}, true );
	</script>
	<?php
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
