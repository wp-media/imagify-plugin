<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Add some CSS on the whole administration
 *
 * @since 1.0
 */
add_action( 'admin_print_styles', '_imagify_admin_print_styles' );
function _imagify_admin_print_styles()
{
	global $pagenow;
	$current_screen = get_current_screen();

	wp_register_style(
		'imagify-css-admin',
		IMAGIFY_ASSETS_CSS_URL . 'admin.css',
		array(),
		IMAGIFY_VERSION
	);

	wp_register_style(
		'imagify-css-sweetalert',
		IMAGIFY_ASSETS_CSS_URL . 'sweetalert.css',
		array(),
		IMAGIFY_VERSION
	);

	wp_register_script(
		'imagify-js-async',
		IMAGIFY_ASSETS_JS_URL . 'imagify.min.js',
		array(),
		IMAGIFY_VERSION,
		true
	);

	wp_register_script(
		'imagify-js-admin',
		IMAGIFY_ASSETS_JS_URL . 'admin.min.js',
		array(),
		IMAGIFY_VERSION,
		true
	);

	wp_register_script(
		'imagify-js-options',
		IMAGIFY_ASSETS_JS_URL . 'options.min.js',
		array(),
		IMAGIFY_VERSION,
		true
	);

	wp_register_script(
		'imagify-js-upload',
		IMAGIFY_ASSETS_JS_URL . 'upload.min.js',
		array( 'jquery' ),
		IMAGIFY_VERSION,
		true
	);

	wp_register_script(
		'imagify-js-sweetalert',
		IMAGIFY_ASSETS_JS_URL . 'sweetalert.min.js',
		array( 'jquery' ),
		IMAGIFY_VERSION,
		true
	);

	wp_register_script(
		'imagify-js-chart',
		IMAGIFY_ASSETS_JS_URL . 'chart.min.js',
		array(),
		IMAGIFY_VERSION,
		true
	);

	wp_register_script(
		'imagify-js-bulk',
		IMAGIFY_ASSETS_JS_URL . 'bulk.js',
		array( 'jquery', 'imagify-js-chart' ),
		IMAGIFY_VERSION,
		true
	);

	/*
	 * Styles loaded in the whole admnistration
	*/
	wp_enqueue_style( 'imagify-css-admin' );
	wp_enqueue_style( 'imagify-css-sweetalert' );

	/*
	 * Scripts loaded in the whole admnistration
	*/
	wp_enqueue_script( 'imagify-js-sweetalert' );
	wp_enqueue_script( 'imagify-js-admin' );

	$admin_data = array(
		'signupTitle'             	    => __( 'Let\'s get you started!', 'imagify' ),
		'signupText'              	    => __( 'Enter your email to get an API key:', 'imagify' ),
		'signupConfirmButtonText' 	    => __( 'Sign Up', 'imagify' ),
		'signupErrorEmptyEmail'   	    => __( 'You need to specify an email!', 'imagify' ),
		'signupSuccessTitle'   	  	    => __( 'Congratulations!', 'imagify' ),
		'signupSuccessText'   	  	    => __( 'Your account has been succesfully created. Please check your mailbox, you are going to receive an email with API key.', 'imagify' ),
		'saveApiKeyTitle'   	  	    => __( 'Connect to Imagify!', 'imagify' ),
		'saveApiKeyText'   	  	    	=> __( 'Paste your API key below:', 'imagify' ),
		'saveApiKeyConfirmButtonText'   => __( 'Connect me', 'imagify' ),
		'waitApiKeyCheckText'     	    => __( 'Checking in process...', 'imagify' ),
		'ApiKeyCheckSuccessTitle' 	    => __( 'Congratulations!', 'imagify' ),
		'ApiKeyCheckSuccessText'  	    => __( 'Your API key is valid. You can now configure the Imagify settings to optimize your images.', 'imagify' ),
		'ValidApiKeyText'  		  	    => __( 'Your API key is valid.', 'imagify' )

	);
	wp_localize_script( 'imagify-js-admin', 'imagify', $admin_data );
	wp_enqueue_script( 'imagify-js-admin' );

	$bulk_data = array(
		'overviewChartLabels'			=> array( 
			'optimized'   => __( 'Optimized', 'imagify' ),
			'unoptimized' => __( 'Unoptimized', 'imagify' ),
			'error'       => __( 'Error', 'imagify' ),
		),
		'noAttachmentToOptimizeTitle'	=> __( 'Hold on!', 'imagify' ),
		'noAttachmentToOptimizeText'	=> __( 'All your images have been optimized by Imagify. Congratulations!', 'imagify' ),
		'pluginURL'						=> 'https://wordpress.org/plugins/imagify',
		'textToShare'					=> __( 'Discover @imagify, the new compression tool to optimize your images for free. I saved %1$s out of %2$s!', 'imagify' ),
		'totalOptimizedAttachments'	    => imagify_count_optimized_attachments(),
		'totalUnoptimizedAttachments'   => imagify_count_unoptimized_attachments(),
		'totalErrorsAttachments' 	    => imagify_count_error_attachments()
	);
	wp_localize_script( 'imagify-js-bulk', 'imagifyBulk', $bulk_data );
	
	$upload_data = array(
		'bulkActionsLabels' => array( 
			'optimize' => __( 'Optimize', 'imagify' ),
			'restore'  => __( 'Restore Original', 'imagify' ),
		),
	);
	wp_localize_script( 'imagify-js-upload', 'imagifyUpload', $upload_data );

	/*
	 * Scripts loaded in /wp-admin/options-general.php?page=imagify
	*/
	if ( isset( $current_screen ) && ( 'settings_page_imagify' === $current_screen->base || 'settings_page_imagify-network' === $current_screen->base ) ) {
		wp_enqueue_script( 'imagify-js-options' );
	}

	/*
	 * Scripts loaded in /wp-admin/upload.php
	*/
	if ( isset( $current_screen ) && 'upload' === $current_screen->base ) {
		wp_enqueue_script( 'imagify-js-chart' );
		wp_enqueue_script( 'imagify-js-upload' );
	}

	/*
	 * Scripts loaded in /wp-admin/upload.php?page=imagify-bulk-optimization
	*/
	if ( isset( $current_screen ) && 'media_page_imagify-bulk-optimization' === $current_screen->base ) {
		wp_enqueue_script( 'imagify-js-chart' );
		wp_enqueue_script( 'imagify-js-async' );
		wp_enqueue_script( 'imagify-js-bulk' );
	}
}