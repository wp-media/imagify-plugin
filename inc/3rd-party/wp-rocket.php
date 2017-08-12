<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

// temp fix on 1.6.9.1
if ( defined( 'WP_ROCKET_VERSION' ) ) {
	/**
	 * Don't load Imagify CSS & JS files on WP Rocket options screen to avoid conflict.
	 *
	 * @since 1.6.9.1
	 * @author Jonathan Buttigieg
	 */
	add_action( 'admin_print_styles', 'imagify_dequeue_sweetalert_wprocket', 11 );
	function imagify_dequeue_sweetalert_wprocket() {
		$current_screen = get_current_screen();
		if ( isset( $current_screen ) && ( 'settings_page_wprocket' === $current_screen->base || 'settings_page_wprocket-network' === $current_screen->base ) ) {
			wp_dequeue_style( 'imagify-css-sweetalert' );
			wp_dequeue_script( 'imagify-js-admin' );
			wp_dequeue_script( 'imagify-js-sweetalert' );
		}
	}
}
