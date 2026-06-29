<?php
declare(strict_types=1);

namespace Imagify\Tracking;

use Imagify\Dependencies\WPMedia\Mixpanel\Optin;
use Imagify\EventManagement\SubscriberInterface;

/**
 * Handles the Imagify Analytics opt-in UI and AJAX toggle.
 *
 * @since 2.3.0
 */
class Notices implements SubscriberInterface {

	/**
	 * The Mixpanel opt-in service.
	 *
	 * @var Optin
	 */
	private $optin;

	/**
	 * Constructor.
	 *
	 * @param Optin $optin The Mixpanel opt-in service.
	 */
	public function __construct( Optin $optin ) {
		$this->optin = $optin;
	}

	/**
	 * Returns the list of events this subscriber wants to listen to.
	 *
	 * @return array<string, string>
	 */
	public static function get_subscribed_events(): array {
		return [
			// @action imagify_settings_after_lossless
			'imagify_settings_after_lossless'       => 'render_optin_section',
			// @action wp_ajax_imagify_toggle_tracking_optin
			'wp_ajax_imagify_toggle_tracking_optin' => 'ajax_toggle_optin',
		];
	}

	/**
	 * Render the opt-in section on the settings page.
	 *
	 * @return void
	 */
	public function render_optin_section(): void {
		$is_enabled     = $this->optin->is_enabled();
		$wp_version     = get_bloginfo( 'version' );
		$php_version    = PHP_VERSION;
		$plugin_version = IMAGIFY_VERSION;
		$opt_level    = imagify_get_optimization_level_label( (int) get_imagify_option( 'optimization_level' ) );

		$convert_webp = (bool) get_imagify_option( 'convert_to_webp' );
		$convert_avif = (bool) get_imagify_option( 'convert_to_avif' );
		if ( $convert_webp && $convert_avif ) {
			$next_gen = __( 'WebP and AVIF', 'imagify' );
		} elseif ( $convert_avif ) {
			$next_gen = __( 'AVIF', 'imagify' );
		} elseif ( $convert_webp ) {
			$next_gen = __( 'WebP', 'imagify' );
		} else {
			$next_gen = __( 'None', 'imagify' );
		}

		$imagify_user = get_imagify_user();
		$license_type = ! is_wp_error( $imagify_user ) && ! empty( $imagify_user->plan_label )
			? ucfirst( $imagify_user->plan_label )
			: __( 'N/A', 'imagify' );

		include IMAGIFY_PATH . 'views/part-settings-analytics.php';
	}

	/**
	 * Handle the AJAX toggle for the analytics opt-in.
	 *
	 * @return void
	 */
	public function ajax_toggle_optin(): void {
		check_ajax_referer( 'imagify_tracking_optin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'imagify' ), 403 );
		}

		$value = isset( $_POST['value'] ) ? (int) $_POST['value'] : 0;

		if ( 1 === $value ) {
			$this->optin->enable();
		} else {
			$this->optin->disable();
		}

		wp_send_json_success();
	}
}
