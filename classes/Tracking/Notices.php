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
			// @action imagify_settings_after_lossless
			'imagify_settings_after_lossless'       => 'render_optin_section',
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
		$data               = $this->collect_data();
		$data['is_enabled'] = $this->optin->is_enabled();
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $data, EXTR_SKIP );

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
	 * Render the "Thank you" admin notice after opt-in is first enabled.
	 *
	 * Triggered on the next page load via transient.
	 *
	 * @return void
	 */
	public function render_thankyou_notice(): void {
		if ( ! get_transient( self::THANKYOU_TRANSIENT ) ) {
			return;
		}

		delete_transient( self::THANKYOU_TRANSIENT );

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $this->collect_data(), EXTR_SKIP );

		include IMAGIFY_PATH . 'views/notice-analytics-thankyou.php';
	}

	/**
	 * Collect the anonymous tracking data to display in the analytics UI.
	 *
	 * @return array<string, string>
	 */
	private function collect_data(): array {
		$wp_version     = get_bloginfo( 'version' );
		$php_version    = PHP_VERSION;
		$plugin_version = IMAGIFY_VERSION;
		$opt_level      = imagify_get_optimization_level_label( (int) get_imagify_option( 'optimization_level' ) );

		$convert_webp = (bool) get_imagify_option( 'convert_to_webp' );
		$convert_avif = (bool) get_imagify_option( 'convert_to_avif' );
		$next_gen     = __( 'None', 'imagify' );
		if ( $convert_webp && $convert_avif ) {
			$next_gen = __( 'WebP and AVIF', 'imagify' );
		} elseif ( $convert_avif ) {
			$next_gen = __( 'AVIF', 'imagify' );
		} elseif ( $convert_webp ) {
			$next_gen = __( 'WebP', 'imagify' );
		}

		$imagify_user = get_imagify_user();
		$license_type = ! is_wp_error( $imagify_user ) && ! empty( $imagify_user->plan_label )
			? ucfirst( $imagify_user->plan_label )
			: __( 'N/A', 'imagify' );

		return compact( 'wp_version', 'php_version', 'plugin_version', 'opt_level', 'next_gen', 'license_type' );
	}
}
