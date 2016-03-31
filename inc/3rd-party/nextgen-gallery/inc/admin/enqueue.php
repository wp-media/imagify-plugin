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
}

/**
 * Add Intercom on Options page an Bulk Optimization
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
add_action( 'admin_footer-galerie_page_nggallery-manage-gallery', '_imagify_admin_print_intercom' );