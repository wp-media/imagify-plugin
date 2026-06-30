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
		$user          = get_imagify_user();
		$license_owner = '';

		if ( ! is_wp_error( $user ) && ! empty( $user->email ) ) {
			$license_owner = hash( 'sha256', $user->email );
		}

		return [
			'context'       => 'wp_plugin',
			'license_owner' => $license_owner,
			'user_id'       => (int) get_current_user_id(),
		];
	}
}
