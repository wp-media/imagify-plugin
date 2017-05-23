<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_action( 'admin_print_styles', '_imagify_ngg_admin_print_styles', PHP_INT_MAX );
/**
 * Add some JS for NGG compatibility.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
function _imagify_ngg_admin_print_styles() {
	$current_screen = get_current_screen();

	/**
	 * Scripts loaded in /wp-admin/admin.php?page=nggallery-manage-gallery.
	 */
	if ( isset( $current_screen ) && ( 'nggallery-manage-images' === $current_screen->base || 'nggallery-manage-gallery' === $current_screen->base ) ) {
		$upload_data = array(
			'bulkActionsLabels' => array(
				'optimize' => __( 'Optimize', 'imagify' ),
				'restore'  => __( 'Restore Original', 'imagify' ),
			),
		);
		wp_localize_script( 'imagify-js-upload', 'imagifyUpload', $upload_data );

		wp_enqueue_script( 'imagify-js-chart' );
		wp_enqueue_script( 'imagify-js-upload' );
	}

	/**
	 * Scripts loaded in /wp-admin/admin.php?page=imagify-ngg-bulk-optimization.
	 */
	if ( isset( $current_screen ) && false !== strpos( $current_screen->base, '_page_imagify-ngg-bulk-optimization' ) ) {
		wp_enqueue_script( 'heartbeat' );

		$bulk_data = get_imagify_localize_script_translations( 'bulk' );
		$bulk_data['heartbeat_id'] = 'update_ngg_bulk_data';
		$bulk_data['ajax_action']  = 'imagify_ngg_get_unoptimized_attachment_ids';
		$bulk_data['ajax_context'] = 'NGG';

		/** This filter is documented in inc/admin/enqueue.php */
		$bulk_data['buffer_size'] = apply_filters( 'imagify_bulk_buffer_size', 4 );

		wp_localize_script( 'imagify-js-bulk', 'imagifyBulk', $bulk_data );
		wp_enqueue_script( 'imagify-js-chart' );
		wp_enqueue_script( 'imagify-js-async' );
		wp_enqueue_script( 'imagify-js-bulk' );
	}
}

add_action( 'admin_footer', '_imagify_ngg_admin_print_intercom' );
/**
 * Add Intercom on Options page an Bulk Optimization
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
function _imagify_ngg_admin_print_intercom() {
	$current_screen = get_current_screen();

	if ( isset( $current_screen ) && false !== strpos( $current_screen->base, '_page_imagify-ngg-bulk-optimization' ) ) {
		_imagify_admin_print_intercom();
	}
}
