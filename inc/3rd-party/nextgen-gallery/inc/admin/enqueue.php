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
		//wp_enqueue_script( 'heartbeat' );
		
		$user	   = get_imagify_user();
		$bulk_data = array(
			'waitTitle' 	=> __( 'Please wait...', 'imagify' ),
			'waitText'  	=> __( 'We are trying to get your unoptimized images, it may take time depending on the number of images.', 'imagify' ),
			'waitImageUrl'  => IMAGIFY_ASSETS_IMG_URL . 'popin-loader.svg',
			'getUnoptimizedImagesErrorTitle'    => __( 'Oops, There is something wrong!', 'imagify' ),
			'getUnoptimizedImagesErrorText'     => __( 'An unknow error occurred when we tried to get all your unoptimized images. Try again and if the issue still persist, please contact us!', 'imagify' ),
			'overviewChartLabels'			=> array( 
				'optimized'   => __( 'Optimized', 'imagify' ),
				'unoptimized' => __( 'Unoptimized', 'imagify' ),
				'error'       => __( 'Error', 'imagify' ),
			),
			'overQuotaTitle'              	=> __( 'Oops, It\'s Over!', 'imagify' ),
			'noAttachmentToOptimizeTitle' 	=> __( 'Hold on!', 'imagify' ),
			'noAttachmentToOptimizeText'	=> __( 'All your images have been optimized by Imagify. Congratulations!', 'imagify' ),
			'pluginURL'						=> 'https://wordpress.org/plugins/imagify',
			'textToShare'					=> __( 'Discover @imagify, the new compression tool to optimize your images for free. I saved %1$s out of %2$s!', 'imagify' ),
			'totalOptimizedAttachments'	    => imagify_count_optimized_attachments(),
			'totalUnoptimizedAttachments'   => imagify_count_unoptimized_attachments(),
			'totalErrorsAttachments' 	    => imagify_count_error_attachments(),
			'processing'                    => __( 'Imagify is still processing. Are you sure you want to leave this page?', 'imagify' ),
		);
		
		if ( imagify_valid_key() ) {
			if ( is_wp_error( $user ) ) {
				$bulk_data['overQuotaText'] = sprintf( __( 'To continue to optimize your images, log in to your Imagify account to %sbuy a pack or subscribe to a plan%s.', 'imagify' ), '<a href="' . IMAGIFY_APP_MAIN . '/#/subscription' . '">', '</a>' );
			}
			else {
				$bulk_data['overQuotaText'] = sprintf( __( 'You have consumed all your credit for this month. You will have <strong>%s back on %s</strong>.', 'imagify' ), size_format( $user->quota * 1048576 ), date_i18n( __( 'F j, Y' ), strtotime( $user->next_date_update ) ) ) . '<br/><br/>' . sprintf( __( 'To continue to optimize your images, log in to your Imagify account to %sbuy a pack or subscribe to a plan%s.', 'imagify' ), '<a href="' . IMAGIFY_APP_MAIN . '/#/subscription' . '">', '</a>' );
			}
		}
		
		wp_localize_script( 'imagify-js-bulk', 'imagifyBulk', $bulk_data );
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