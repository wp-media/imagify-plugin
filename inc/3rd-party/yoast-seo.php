<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( defined( 'WPSEO_VERSION' ) && is_admin() && ! wp_doing_ajax() ) :

	add_action( 'wp_print_scripts', '_imagify_dequeue_yoastseo_script' );
	/**
	 * Remove Yoast SEO bugged script.
	 *
	 * @since 1.4.1
	 */
	function _imagify_dequeue_yoastseo_script() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}
		$current_screen = get_current_screen();

		if ( isset( $current_screen ) && 'post' === $current_screen->base && 'attachment' === $current_screen->post_type ) {
			wp_dequeue_script( 'yoast-seo' );
			wp_deregister_script( 'yoast-seo' );
		}
	}

endif;
