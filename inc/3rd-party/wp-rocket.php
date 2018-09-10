<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( defined( 'WP_ROCKET_VERSION' ) ) :

	add_action( 'imagify_assets_enqueued', 'imagify_dequeue_sweetalert_wprocket' );
	/**
	 * Don't load Imagify CSS & JS files on WP Rocket options screen to avoid conflict with older version of SweetAlert.
	 * Since 1.6.10 they should be enqueued only if one of our notices displays here.
	 *
	 * @since  1.6.9.1
	 * @since  1.6.10 Use the new class Imagify_Assets.
	 * @author Jonathan Buttigieg
	 * @author Grégory Viguier
	 */
	function imagify_dequeue_sweetalert_wprocket() {
		if ( ! defined( 'WP_ROCKET_PLUGIN_SLUG' ) ) {
			return;
		}

		if ( ! imagify_is_screen( 'settings_page_' . WP_ROCKET_PLUGIN_SLUG ) && ! imagify_is_screen( 'settings_page_' . WP_ROCKET_PLUGIN_SLUG . '-network' ) ) {
			return;
		}

		Imagify_Assets::get_instance()->dequeue_script( array( 'sweetalert-core', 'sweetalert', 'notices' ) );
	}

endif;
