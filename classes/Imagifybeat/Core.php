<?php
namespace Imagify\Imagifybeat;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Imagifybeat core.
 *
 * @since  1.9.3
 * @author Grégory Viguier
 */
class Core {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * Class init: launch hooks.
	 *
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		add_action( 'wp_ajax_imagifybeat',        [ $this, 'core_handler' ], 1 );
		add_filter( 'imagifybeat_refresh_nonces', [ $this, 'refresh_imagifybeat_nonces' ] );
	}

	/**
	 * Ajax handler for the Imagifybeat API.
	 *
	 * Runs when the user is logged in.
	 *
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
	 */
	public function core_handler() {
		if ( empty( $_POST['_nonce'] ) ) {
			wp_send_json_error();
		}

		$data        = [];
		$response    = [];
		$nonce_state = wp_verify_nonce( wp_unslash( $_POST['_nonce'] ), 'imagifybeat-nonce' );

		// Screen_id is the same as $current_screen->id and the JS global 'pagenow'.
		if ( ! empty( $_POST['screen_id'] ) ) {
			$screen_id = sanitize_key( $_POST['screen_id'] );
		} else {
			$screen_id = 'front';
		}

		if ( ! empty( $_POST['data'] ) ) {
			$data = wp_unslash( (array) $_POST['data'] );
		}

		if ( 1 !== $nonce_state ) {
			/**
			 * Filters the nonces to send.
			 *
			 * @since  1.9.3
			 * @author Grégory Viguier
			 *
			 * @param array  $response  The Imagifybeat response.
			 * @param array  $data      The $_POST data sent.
			 * @param string $screen_id The screen id.
			 */
			$response = apply_filters( 'imagifybeat_refresh_nonces', $response, $data, $screen_id );

			if ( false === $nonce_state ) {
				// User is logged in but nonces have expired.
				$response['nonces_expired'] = true;
				wp_send_json( $response );
			}
		}

		if ( ! empty( $data ) ) {
			/**
			 * Filters the Imagifybeat response received.
			 *
			 * @since  1.9.3
			 * @author Grégory Viguier
			 *
			 * @param array  $response  The Imagifybeat response.
			 * @param array  $data      The $_POST data sent.
			 * @param string $screen_id The screen id.
			 */
			$response = apply_filters( 'imagifybeat_received', $response, $data, $screen_id );
		}

		/**
		 * Filters the Imagifybeat response sent.
		 *
		 * @since  1.9.3
		 * @author Grégory Viguier
		 *
		 * @param array  $response  The Imagifybeat response.
		 * @param string $screen_id The screen id.
		 */
		$response = apply_filters( 'imagifybeat_send', $response, $screen_id );

		/**
		 * Fires when Imagifybeat ticks in logged-in environments.
		 *
		 * Allows the transport to be easily replaced with long-polling.
		 *
		 * @since  1.9.3
		 * @author Grégory Viguier
		 *
		 * @param array  $response  The Imagifybeat response.
		 * @param string $screen_id The screen id.
		 */
		do_action( 'imagifybeat_tick', $response, $screen_id );

		// Send the current time according to the server.
		$response['server_time'] = time();

		wp_send_json( $response );
	}

	/**
	 * Add the latest Imagifybeat nonce to the Imagifybeat response.
	 *
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $response  The Imagifybeat response.
	 * @return array            The Imagifybeat response.
	 */
	public function refresh_imagifybeat_nonces( $response ) {
		// Refresh the Imagifybeat nonce.
		$response['imagifybeat_nonce'] = wp_create_nonce( 'imagifybeat-nonce' );
		return $response;
	}

	/**
	 * Get Imagifybeat settings.
	 *
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_settings() {
		global $pagenow;

		$settings = [];

		if ( ! is_admin() ) {
			$settings['ajaxurl'] = admin_url( 'admin-ajax.php', 'relative' );
		}

		if ( is_user_logged_in() ) {
			$settings['nonce'] = wp_create_nonce( 'imagifybeat-nonce' );
		}

		if ( 'customize.php' === $pagenow ) {
			$settings['screenId'] = 'customize';
		}

		/**
		 * Filters the Imagifybeat settings.
		 *
		 * @since  1.9.3
		 * @author Grégory Viguier
		 *
		 * @param array $settings Imagifybeat settings array.
		 */
		return (array) apply_filters( 'imagifybeat_settings', $settings );
	}
}
