<?php
declare(strict_types=1);

namespace Imagify\Tracking;

use Imagify\Dependencies\WPMedia\Mixpanel\Optin;
use Imagify\Dependencies\WPMedia\Mixpanel\TrackingPlugin;

/**
 * Abstract base class for Imagify tracking.
 *
 * @since 2.3.0
 */
abstract class BaseTracking {

	/**
	 * The Mixpanel opt-in service.
	 *
	 * @var Optin
	 */
	protected $optin;

	/**
	 * The Mixpanel tracking plugin service.
	 *
	 * @var TrackingPlugin
	 */
	protected $mixpanel;

	/**
	 * Whether the Mixpanel user has already been identified during this request.
	 *
	 * @var bool
	 */
	private $identified = false;

	/**
	 * Constructor.
	 *
	 * @param Optin          $optin    The Mixpanel opt-in service.
	 * @param TrackingPlugin $mixpanel The Mixpanel tracking plugin service.
	 */
	public function __construct( Optin $optin, TrackingPlugin $mixpanel ) {
		$this->optin    = $optin;
		$this->mixpanel = $mixpanel;
	}

	/**
	 * Check if tracking is allowed.
	 *
	 * @return bool True if tracking is allowed, false otherwise.
	 */
	public function can_track(): bool {
		return $this->optin->can_track();
	}

	/**
	 * Returns the default event properties shared by every tracked event.
	 *
	 * IMPORTANT: do NOT add `domain`, `wp_version`, `php_version`, `plugin`,
	 * `brand`, or `application` here. `TrackingPlugin::track_direct()` injects
	 * those automatically and any value set here is silently overwritten.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_default_event_properties(): array {
		$email         = $this->get_license_owner_email();
		$license_owner = '' !== $email ? hash( 'sha256', $email ) : '';

		$this->identify_user( $email );

		return [
			'context'       => 'wp_plugin',
			'license_owner' => $license_owner,
			'user_id'       => (int) get_current_user_id(),
		];
	}

	/**
	 * Registers a stable `distinct_id` super property on the Mixpanel instance.
	 *
	 * Mixpanel needs `distinct_id` on every event to compute user-level metrics
	 * (MAU/DAU, retention, cohorts, funnels). `TrackingPlugin::identify()` hashes
	 * the identifier with sha224 before it is registered, so no raw email or
	 * domain ever leaves the site.
	 *
	 * The identifier is the Imagify license owner email — the same strategy as
	 * WP Rocket, so one license maps to one Mixpanel user across all its sites.
	 * When no license email is available (unlicensed or unreachable API), the
	 * site host is used instead so events are still attributed to a stable,
	 * anonymized identity rather than none at all.
	 *
	 * Called from `get_default_event_properties()` — the single choke point every
	 * tracked event goes through — so the license user is fetched only once and
	 * never on plugin bootstrap. Runs at most once per instance per request; the
	 * underlying Mixpanel instance is shared, so the property applies to every
	 * subsequent event.
	 *
	 * @param string $email The license owner email, or an empty string when unknown.
	 *
	 * @return void
	 */
	protected function identify_user( string $email ): void {
		if ( $this->identified ) {
			return;
		}

		$identifier = '' !== $email ? $email : $this->get_site_identifier();

		if ( '' === $identifier ) {
			return;
		}

		$this->mixpanel->identify( $identifier );

		$this->identified = true;
	}

	/**
	 * Returns the Imagify license owner email.
	 *
	 * @return string The email, or an empty string when the account is unknown.
	 */
	private function get_license_owner_email(): string {
		$user = get_imagify_user();

		if ( is_wp_error( $user ) || empty( $user->email ) ) {
			return '';
		}

		return (string) $user->email;
	}

	/**
	 * Returns the site host, used as a fallback identifier when no license email exists.
	 *
	 * @return string The host, or an empty string when it cannot be resolved.
	 */
	private function get_site_identifier(): string {
		$host = wp_parse_url( get_home_url(), PHP_URL_HOST );

		return is_string( $host ) ? $host : '';
	}
}
