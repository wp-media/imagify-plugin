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

	const THANKYOU_TRANSIENT = 'imagify_analytics_optin_thanks';

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
			// @action imagify_settings_after_tools
			'imagify_settings_after_tools'          => 'render_optin_section',
			// @action wp_ajax_imagify_toggle_tracking_optin
			'wp_ajax_imagify_toggle_tracking_optin' => 'ajax_toggle_optin',
			// @action admin_notices
			'admin_notices'                         => 'render_thankyou_notice',
		];
	}

	/**
	 * Render the opt-in section on the settings page.
	 *
	 * @return void
	 */
	public function render_optin_section(): void {
		$is_enabled = $this->optin->is_enabled();
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
			set_transient( self::THANKYOU_TRANSIENT, 1, 60 );
		} else {
			$this->optin->disable();
		}

		wp_send_json_success();
	}

	/**
	 * Render the "Thank you" admin notice after opt-in is enabled.
	 *
	 * Triggered on the next page load after enabling the toggle.
	 *
	 * @return void
	 */
	public function render_thankyou_notice(): void {
		if ( ! get_transient( self::THANKYOU_TRANSIENT ) ) {
			return;
		}

		delete_transient( self::THANKYOU_TRANSIENT );

		include IMAGIFY_PATH . 'views/notice-analytics-thankyou.php';
	}
}
