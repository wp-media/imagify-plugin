<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_action( 'admin_print_styles', '_imagify_admin_print_styles' );
/**
 * Add some CSS on the whole administration
 *
 * @since 1.0
 */
function _imagify_admin_print_styles() {
	global $pagenow;
	$current_screen = get_current_screen();
	$css_ext        = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.css' : '.min.css';
	$js_ext         = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.js'  : '.min.js';

	/**
	 * 3rd Party Styles.
	 */
	wp_register_style(
		'imagify-css-sweetalert',
		IMAGIFY_ASSETS_CSS_URL . 'sweetalert2' . $css_ext,
		array(),
		'4.0.15'
	);

	/**
	 * Imagify Styles.
	 */
	wp_register_style(
		'imagify-css-twentytwenty',
		IMAGIFY_ASSETS_CSS_URL . 'twentytwenty' . $css_ext,
		array(),
		IMAGIFY_VERSION
	);

	wp_register_style(
		'imagify-css-admin',
		IMAGIFY_ASSETS_CSS_URL . 'admin' . $css_ext,
		array(),
		IMAGIFY_VERSION
	);

	/**
	 * 3rd Party Scripts.
	 */
	wp_register_script(
		'imagify-js-promise-polyfill',
		IMAGIFY_ASSETS_JS_URL . 'es6-promise.auto' . $js_ext,
		array(),
		'4.1.0',
		true
	);

	wp_register_script(
		'imagify-js-sweetalert',
		IMAGIFY_ASSETS_JS_URL . 'sweetalert2' . $js_ext,
		array( 'jquery', 'imagify-js-promise-polyfill' ),
		'4.0.15',
		true
	);

	wp_register_script(
		'imagify-js-chart',
		IMAGIFY_ASSETS_JS_URL . 'chart' . $js_ext,
		array(),
		'1.0.2',
		true
	);

	wp_register_script(
		'imagify-js-event-move',
		IMAGIFY_ASSETS_JS_URL . 'jquery.event.move' . $js_ext,
		array( 'jquery' ),
		'1.3.6',
		true
	);

	/**
	 * Imagify Scripts.
	 */
	wp_register_script(
		'imagify-js-async',
		IMAGIFY_ASSETS_JS_URL . 'imagify' . $js_ext,
		array(),
		IMAGIFY_VERSION,
		true
	);

	wp_register_script(
		'imagify-js-bulk',
		IMAGIFY_ASSETS_JS_URL . 'bulk' . $js_ext,
		array( 'jquery', 'imagify-js-chart', 'imagify-js-sweetalert', 'imagify-js-async' ),
		IMAGIFY_VERSION,
		true
	);

	wp_register_script(
		'imagify-js-admin',
		IMAGIFY_ASSETS_JS_URL . 'admin' . $js_ext,
		array( 'jquery', 'imagify-js-sweetalert' ),
		IMAGIFY_VERSION,
		true
	);

	wp_register_script(
		'imagify-js-options',
		IMAGIFY_ASSETS_JS_URL . 'options' . $js_ext,
		array( 'jquery', 'imagify-js-sweetalert' ),
		IMAGIFY_VERSION,
		true
	);

	wp_register_script(
		'imagify-js-upload',
		IMAGIFY_ASSETS_JS_URL . 'upload' . $js_ext,
		array( 'jquery', 'imagify-js-chart' ),
		IMAGIFY_VERSION,
		true
	);

	wp_register_script(
		'imagify-js-twentytwenty',
		IMAGIFY_ASSETS_JS_URL . 'jquery.twentytwenty' . $js_ext,
		array( 'jquery', 'imagify-js-event-move', 'imagify-js-chart' ),
		IMAGIFY_VERSION,
		true
	);

	/*
	 * Loaded in the whole admnistration.
	 */
	wp_enqueue_style( 'imagify-css-admin' );
	wp_enqueue_style( 'imagify-css-sweetalert' );

	wp_enqueue_script( 'imagify-js-admin' );
	wp_localize_script( 'imagify-js-admin', 'imagifyAdmin', get_imagify_localize_script_translations( 'admin' ) );

	/*
	 * Loaded in /wp-admin/options-general.php?page=imagify.
	 */
	if ( isset( $current_screen ) && ( 'settings_page_imagify' === $current_screen->base || 'settings_page_imagify-network' === $current_screen->base ) ) {
		wp_enqueue_style( 'imagify-css-twentytwenty' );

		wp_enqueue_script( 'imagify-js-twentytwenty' );
		wp_enqueue_script( 'imagify-js-options' );
		wp_localize_script( 'imagify-js-options', 'imagifyOptions', get_imagify_localize_script_translations( 'options' ) );
	}

	/**
	 * Loaded in /wp-admin/upload.php and post.php.
	 */
	if ( isset( $current_screen ) && ( 'upload' === $current_screen->base || 'post' === $current_screen->base ) ) {
		wp_enqueue_script( 'imagify-js-upload' );
		wp_localize_script( 'imagify-js-upload', 'imagifyUpload', get_imagify_localize_script_translations( 'upload' ) );
	}

	/**
	 * Loaded in:
	 *     /wp-admin/post.php (for attachment post type),
	 *     /wp-admin/upload.php (for attachments list).
	 */
	if ( isset( $current_screen ) && ( ('post' === $current_screen->base && 'attachment' === $current_screen->post_type ) || 'upload' === $current_screen->base ) ) {
		wp_enqueue_style( 'imagify-css-twentytwenty' );

		wp_enqueue_script( 'imagify-js-twentytwenty' );
		wp_localize_script( 'imagify-js-twentytwenty', 'imagifyTTT', get_imagify_localize_script_translations( 'twentytwenty' ) );
	}

	/**
	 * Loaded in /wp-admin/upload.php?page=imagify-bulk-optimization.
	 */
	if ( isset( $current_screen ) && 'media_page_imagify-bulk-optimization' === $current_screen->base ) {
		wp_enqueue_script( 'heartbeat' );
		wp_enqueue_script( 'imagify-js-bulk' );

		$bulk_data = get_imagify_localize_script_translations( 'bulk' );
		$bulk_data['heartbeat_id'] = 'update_bulk_data';
		$bulk_data['ajax_action']  = 'imagify_get_unoptimized_attachment_ids';
		$bulk_data['ajax_context'] = 'wp';
		$bulk_data['buffer_size']  = get_imagify_bulk_buffer_size();

		/**
		 * Filter the number of parallel queries during the Bulk Optimization
		 *
		 * @since 1.5.4
		 *
		 * @param int $buffer_size Number of parallel queries.
		 */
		$bulk_data['buffer_size'] = apply_filters( 'imagify_bulk_buffer_size', $bulk_data['buffer_size'] );

		wp_localize_script( 'imagify-js-bulk', 'imagifyBulk', $bulk_data );
	}
}

add_action( 'admin_footer-media_page_imagify-bulk-optimization', '_imagify_admin_print_intercom' );
add_action( 'admin_footer-settings_page_imagify',                '_imagify_admin_print_intercom' );
/**
 * Add Intercom on Options page an Bulk Optimization
 *
 * @since 1.0
 */
function _imagify_admin_print_intercom() {
	if ( ! imagify_valid_key() ) {
		return;
	}

	$user = get_imagify_user();

	if ( empty( $user->is_intercom ) || false === $user->display_support ) {
		return;
	}
	?>
	<script>
	window.intercomSettings = {
		app_id: 'cd6nxj3z',
		user_id: <?php echo (int) $user->id; ?>,
	};
	</script>
	<script>(function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',intercomSettings);}else{var d=document;var i=function(){i.c(arguments)};i.q=[];i.c=function(args){i.q.push(args)};w.Intercom=i;function l(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/cd6nxj3z';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);}if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})()</script>
	<?php
}

add_action( 'wp_print_scripts', '_imagify_dequeue_yoastseo_script' );
/**
 * Remove Yoast SEO bugged script
 *
 * @since 1.4.1
 */
function _imagify_dequeue_yoastseo_script() {
	global $pagenow;
	$current_screen = get_current_screen();

	if ( isset( $current_screen ) && 'post' === $current_screen->base && 'attachment' === $current_screen->post_type ) {
		wp_dequeue_script( 'yoast-seo' );
		wp_deregister_script( 'yoast-seo' );
	}
}
