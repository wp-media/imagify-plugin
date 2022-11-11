<?php
use Imagify\Notices\Notices;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( defined( 'RML_FILE' ) ) :
	/**
	 * Dequeue all WP Real Media Library's styles and scripts where we use ours.
	 *
	 * Prevent WP Real Media Library to use its outdated version of SweetAlert where we need ours, and to mess with our CSS styles.
	 *
	 * @since 1.6.13
	 */
	function imagify_wprml_init() {
		static $done = false;

		if ( $done ) {
			return;
		}
		$done = true;

		if ( ! class_exists( '\\MatthiasWeb\\RealMediaLibrary\\general\\Backend' ) ) {
			return;
		}

		$notices = Notices::get_instance();

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
	add_action( 'current_screen', 'imagify_wprml_init' );

	/**
	 * Prevent WP Real Media Library to enqueue its styles and scripts.
	 *
	 * @since 1.6.13
	 */
	function imagify_wprml_dequeue() {
		$instance = \MatthiasWeb\RealMediaLibrary\general\Backend::getInstance();

		remove_action( 'admin_enqueue_scripts', [ $instance, 'admin_enqueue_scripts' ], 0 );
		remove_action( 'admin_footer',          [ $instance, 'admin_footer' ] );

		if ( class_exists( '\\MatthiasWeb\\RealMediaLibrary\\general\\FolderShortcode' ) ) {
			$instance = \MatthiasWeb\RealMediaLibrary\general\FolderShortcode::getInstance();

			remove_action( 'admin_head',            [ $instance, 'admin_head' ] );
			remove_action( 'admin_enqueue_scripts', [ $instance, 'admin_enqueue_scripts' ] );
		}

		if ( class_exists( '\\MatthiasWeb\\RealMediaLibrary\\comp\\PageBuilders' ) ) {
			$instance = \MatthiasWeb\RealMediaLibrary\comp\PageBuilders::getInstance();

			remove_action( 'init', [ $instance, 'init' ] );
		}
	}

endif;
