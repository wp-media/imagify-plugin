<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Get all translations we can use with wp_localize_script()
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 *
 * @param  string  $context  	  The translation context
 * @return array   $translations  The translations
 */
function get_imagify_localize_script_translations( $context ) {
	$translations = array();

	switch( $context ) {
		case 'admin':
			$translations = array(
				'labels' => array(
					'signupTitle'             	    => __( 'Let\'s get you started!', 'imagify' ),
					'signupText'              	    => __( 'Enter your email to get an API key:', 'imagify' ),
					'signupConfirmButtonText' 	    => __( 'Sign Up', 'imagify' ),
					'signupErrorEmptyEmail'   	    => __( 'You need to specify an email!', 'imagify' ),
					'signupSuccessTitle'   	  	    => __( 'Congratulations!', 'imagify' ),
					'signupSuccessText'   	  	    => __( 'Your account has been succesfully created. Please check your mailbox, you are going to receive an email with API key.', 'imagify' ),
					'saveApiKeyTitle'   	  	    => __( 'Connect to Imagify!', 'imagify' ),
					'saveApiKeyText'   	  	    	=> __( 'Paste your API key below:', 'imagify' ),
					'saveApiKeyConfirmButtonText'   => __( 'Connect me', 'imagify' ),
					'waitApiKeyCheckText'     	    => __( 'Check in progress...', 'imagify' ),
					'ApiKeyCheckSuccessTitle' 	    => __( 'Congratulations!', 'imagify' ),
					'ApiKeyCheckSuccessText'  	    => __( 'Your API key is valid. You can now configure the Imagify settings to optimize your images.', 'imagify' ),
					'ValidApiKeyText'  		  	    => __( 'Your API key is valid.', 'imagify' ),
					'swalCancel'					=> __( 'Cancel' )		
				)
			);
		break;

		case 'options':
			$translations = array(
				'noBackupTitle'	=> __( 'Don\'t Need a Parachute?', 'imagify' ),
				'noBackupText'	=> __( 'If you keep this option deactivated, you won\'t be able to re-optimize your images to another compression level and restore your original images in case of need.', 'imagify' )
			);
		break;
		
		case 'upload':
			$translations = array(
				'bulkActionsLabels' => array( 
					'optimize' => __( 'Optimize', 'imagify' ),
					'restore'  => __( 'Restore Original', 'imagify' ),
				),
			);
		break;
		
		case 'twentytwenty':
			$translations = array(
				'labels' => array(
					'original_l'	=> esc_html__( 'Original Image', 'imagify' ),
					'optimized_l'	=> esc_html__( 'Optimized Image', 'imagify' ),
					'compare'		=> esc_html__( 'Compare Original VS Optimized', 'imagify' ),
					'close'			=> esc_html__( 'Close', 'imagify' ),
					'filesize'		=> esc_html__( 'File Size:', 'imagify' ),
					'saving'		=> esc_html__( 'Original Saving:', 'imagify' ),
					'optimize'		=> esc_html__( 'Optimize', 'imagify' )
				)
			);
		break;
		
		case 'bulk':
			$user	      = get_imagify_user();
			$translations = array(
				'labels' => array(
					'waitTitle' 	=> __( 'Please wait...', 'imagify' ),
					'waitText'  	=> __( 'We are trying to get your unoptimized images, it may take time depending on the number of images.', 'imagify' ),
					'waitImageUrl'  => IMAGIFY_ASSETS_IMG_URL . 'popin-loader.svg',
					'getUnoptimizedImagesErrorTitle'    => __( 'Oops, There is something wrong!', 'imagify' ),
					'getUnoptimizedImagesErrorText'     => __( 'An unknow error occurred when we tried to get all your unoptimized images. Try again and if the issue still persist, please contact us!', 'imagify' ),
					'invalidAPIKeyTitle' => __( 'Your API key isn\'t valid!', 'imagify' ),
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
					'processing'                    => __( 'Imagify is still processing. Are you sure you want to leave this page?', 'imagify' )
				)
			);
			
			if ( imagify_valid_key() ) {
				if ( is_wp_error( $user ) ) {
					$translations['overQuotaText'] = sprintf( __( 'To continue to optimize your images, log in to your Imagify account to %sbuy a pack or subscribe to a plan%s.', 'imagify' ), '<a href="' . IMAGIFY_APP_MAIN . '/#/subscription' . '">', '</a>' );
				}
				else {
					$translations['overQuotaText'] = sprintf( __( 'You have consumed all your credit for this month. You will have <strong>%s back on %s</strong>.', 'imagify' ), size_format( $user->quota * 1048576 ), date_i18n( __( 'F j, Y' ), strtotime( $user->next_date_update ) ) ) . '<br/><br/>' . sprintf( __( 'To continue to optimize your images, log in to your Imagify account to %sbuy a pack or subscribe to a plan%s.', 'imagify' ), '<a href="' . IMAGIFY_APP_MAIN . '/#/subscription' . '">', '</a>' );
				}
			}
		break;
	}

	return $translations;
}