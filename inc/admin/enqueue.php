<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Add some CSS on the whole administration
 *
 * @since 1.0
 */
add_action( 'admin_print_styles', '_imagify_admin_print_styles' );
function _imagify_admin_print_styles() {
	global $pagenow;
	$user			= get_imagify_user();
	$current_screen = get_current_screen();
	$css_ext        = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.css' : '.min.css';
	$js_ext         = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.js' : '.min.js';

	wp_register_style(
		'imagify-css-admin',
		IMAGIFY_ASSETS_CSS_URL . 'admin' . $css_ext,
		array(),
		IMAGIFY_VERSION
	);

	wp_register_style(
		'imagify-css-sweetalert',
		IMAGIFY_ASSETS_CSS_URL . 'sweetalert' . $css_ext,
		array(),
		IMAGIFY_VERSION
	);

	wp_register_style(
		'imagify-css-twentytwenty',
		IMAGIFY_ASSETS_CSS_URL . 'twentytwenty' . $css_ext,
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
		IMAGIFY_ASSETS_JS_URL . 'admin' . $js_ext,
		array(),
		IMAGIFY_VERSION,
		true
	);

	wp_register_script(
		'imagify-js-options',
		IMAGIFY_ASSETS_JS_URL . 'options' . $js_ext,
		array(),
		IMAGIFY_VERSION,
		true
	);

	wp_register_script(
		'imagify-js-upload',
		IMAGIFY_ASSETS_JS_URL . 'upload' . $js_ext,
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
		IMAGIFY_ASSETS_JS_URL . 'bulk' . $js_ext,
		array( 'jquery', 'imagify-js-chart' ),
		IMAGIFY_VERSION,
		true
	);

	wp_register_script(
		'imagify-js-twentytwenty',
		IMAGIFY_ASSETS_JS_URL . 'jquery.twentytwenty' . $js_ext,
		array( 'jquery', 'imagify-js-event-move' ),
		IMAGIFY_VERSION,
		true
	);
	wp_register_script(
		'imagify-js-event-move',
		IMAGIFY_ASSETS_JS_URL . 'jquery.event.move' . $js_ext,
		array( 'jquery' ),
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
		'overQuotaTitle'              	=> __( 'Oops, It\'s Over!', 'imagify' ),
		'noAttachmentToOptimizeTitle' 	=> __( 'Hold on!', 'imagify' ),
		'noAttachmentToOptimizeText'	=> __( 'All your images have been optimized by Imagify. Congratulations!', 'imagify' ),
		'pluginURL'						=> 'https://wordpress.org/plugins/imagify',
		'textToShare'					=> __( 'Discover @imagify, the new compression tool to optimize your images for free. I saved %1$s out of %2$s!', 'imagify' ),
		'totalOptimizedAttachments'	    => imagify_count_optimized_attachments(),
		'totalUnoptimizedAttachments'   => imagify_count_unoptimized_attachments(),
		'totalErrorsAttachments' 	    => imagify_count_error_attachments()
	);
	
	if ( imagify_valid_key() ) {
		$bulk_data['overQuotaText'] = sprintf( __( 'You have consumed all your credit for this month. You will have <strong>%s back on %s</strong>.', 'imagify' ), size_format( $user->quota * 1048576 ), date_i18n( get_option( 'date_format' ), strtotime( $user->next_date_update ) ) ) . '<br/><br/>' . sprintf( __( 'To continue to optimize your images, log in to your Imagify account to %sbuy a pack or subscribe to a plan%s.', 'imagify' ), '<a href="' . IMAGIFY_APP_MAIN . '/#/subscription' . '">', '</a>' );
	}
	
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
		wp_enqueue_script( 'imagify-js-event-move' );
		wp_enqueue_script( 'imagify-js-twentytwenty' );
		wp_enqueue_script( 'imagify-js-options' );
		wp_enqueue_style( 'imagify-css-twentytwenty' );
	}

	/*
	 * Scripts loaded in /wp-admin/upload.php and post.php
	*/
	if ( isset( $current_screen ) && ( 'upload' === $current_screen->base || 'post' === $current_screen->base ) ) {
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

/**
 * Add Intercom on Options page an Bulk Optimization
 *
 * @since 1.0
 */
add_action( 'admin_footer-media_page_imagify-bulk-optimization', '_imagify_admin_print_intercom' );
add_action( 'admin_footer-settings_page_imagify', '_imagify_admin_print_intercom' );
function _imagify_admin_print_intercom() { 
	$user = get_imagify_user();

	if ( ! imagify_valid_key() || empty( $user->is_intercom ) ) {
		return;
	}
	?>	
	<script>
	window.intercomSettings = {
		app_id: "cd6nxj3z",
		user_id: <?php echo (int) $user->id; ?>,
	};
	</script>
	<script>(function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',intercomSettings);}else{var d=document;var i=function(){i.c(arguments)};i.q=[];i.c=function(args){i.q.push(args)};w.Intercom=i;function l(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/cd6nxj3z';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);}if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})()</script>
<?php
}