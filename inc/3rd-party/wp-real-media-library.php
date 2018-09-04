<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( defined( 'RML_FILE' ) ) :

	/**
	 * Prevent WP Real Media Library to use its outdated version of SweetAlert where we need ours.
	 */
	add_action( 'current_screen', 'imagify_wprml_init' );
	/**
	 * Dequeue WP Real Media Library's version of SweetAlert when we need ours.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 */
	function imagify_wprml_init() {
		static $done = false;

		if ( $done ) {
			return;
		}
		$done = true;

		if ( ! class_exists( 'MatthiasWeb\RealMediaLibrary\general\Backend' ) ) {
			return;
		}

		$notices = Imagify_Notices::get_instance();

		if ( $notices->has_notices() && ( $notices->display_welcome_steps() || $notices->display_wrong_api_key() ) ) {
			// We display a notice that uses SweetAlert.
			imagify_wprml_dequeue();
			return;
		}

		if ( imagify_is_screen( 'bulk' ) || imagify_is_screen( 'imagify-settings' ) ) {
			// We display a page that uses SweetAlert.
			imagify_wprml_dequeue();
			return;
		}

		if ( function_exists( 'imagify_get_ngg_bulk_screen_id' ) && imagify_is_screen( imagify_get_ngg_bulk_screen_id() ) ) {
			// We display the NGG Bulk Optimization page.
			imagify_wprml_dequeue();
		}
	}

	/**
	 * Prevent WP Real Media Library to enqueue its version of SweetAlert.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 */
	function imagify_wprml_dequeue() {
		$instance = call_user_func( array( 'MatthiasWeb\RealMediaLibrary\general\Backend', 'getInstance' ) );

		remove_action( 'admin_enqueue_scripts', array( $instance, 'admin_enqueue_scripts' ), 0 );
		remove_action( 'admin_footer',          array( $instance, 'admin_footer' ) );
	}

endif;
