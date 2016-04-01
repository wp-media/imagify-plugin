<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Add some JS for NGG compatibility
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
add_action( 'admin_print_styles', '_imagify_ngg_admin_print_styles', PHP_INT_MAX );
function _imagify_ngg_admin_print_styles() {
	$current_screen = get_current_screen();
	
	/**
	 * Scripts loaded in /wp-admin/admin.php?page=nggallery-manage-gallery
	 */
	if ( isset( $current_screen ) && ( 'nggallery-manage-images' === $current_screen->base ) ) {
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
	 * Scripts loaded in /wp-admin/admin.php?page=imagify-ngg-bulk-optimization
	 */
	if ( isset( $current_screen ) && 'galerie_page_imagify-ngg-bulk-optimization' === $current_screen->base ) {
		wp_enqueue_script( 'heartbeat' );
		
		$user	   = get_imagify_user();
		$bulk_data = get_imagify_localize_script_translations( 'bulk' );
				
		wp_localize_script( 'imagify-js-bulk', 'imagifyBulk', $bulk_data );
		wp_localize_script( 'imagify-js-bulk', 'imagifyBulkHearbeat', array( 'id' => 'update_ngg_bulk_data' ) );
		wp_enqueue_script( 'imagify-js-chart' );
		wp_enqueue_script( 'imagify-js-async' );
		wp_enqueue_script( 'imagify-js-bulk' );
	}
}

/**
 * Add Intercom on Options page an Bulk Optimization
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
add_action( 'admin_footer-galerie_page_nggallery-manage-gallery', '_imagify_admin_print_intercom' );