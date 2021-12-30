<?php
defined( 'ABSPATH' ) || die( 'Cheatin uh?' );

if ( defined( 'SWCFPC_PLUGIN_PATH' ) ) :

	/**
	 * Prevent WP Cloudflare Super Page Cache to use its outdated version of SweetAlert where we need ours, and to mess with our CSS styles.
	 */
	add_action( 'current_screen', 'imagify_wpcspc_init' );
	/**
	 * Dequeue all WP Cloudflare Super Page Cache's styles and scripts where we use ours.
	 *
	 * @since  1.10.2
	 * @author Marko Nikolic
	 */
	function imagify_wpcspc_init() {
		static $done = false;

		if ( $done ) {
			return;
		}
		$done = true;

		if ( ! class_exists( 'SWCFPC_Backend' ) ) {
			return;
		}



		if ( imagify_is_screen( 'bulk' ) ) {
			// We display a page that uses SweetAlert.
			imagify_wpcspc_dequeue();
			return;
		}

	}

	/**
	 * Prevent WP Cloudflare Super Page Cache to enqueue its styles and scripts.
	 *
	 * @since  1.10.2
	 * @author Marko Nikolic
	 */
	function imagify_wpcspc_dequeue() {
		
		// $instance = SWCFPC_Backend::getInstance(); // This is the part I need to find out
		$instance = new SWCFPC_Backend('SW_CLOUDFLARE_PAGECACHE'); // This is the part I need to find out

		remove_action( 'admin_enqueue_scripts', [ $instance, 'load_custom_wp_admin_styles_and_script' ], 0 );
		
	}

endif;