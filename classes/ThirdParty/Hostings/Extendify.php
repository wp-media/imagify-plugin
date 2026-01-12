<?php
declare( strict_types=1 );

namespace Imagify\ThirdParty\Hostings;

use Imagify\EventManagement\SubscriberInterface;

/**
 * Extendify compatibility class
 */
class Extendify implements SubscriberInterface {
	/**
	 * Returns an array of events that this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'imagify_hide_plugin_family' => 'hide_plugin_family',
		];
	}

	/**
	 * Hide the plugin family if the Extendify site ID option is present.
	 *
	 * @param bool $hide Current value.
	 *
	 * @return bool
	 */
	public function hide_plugin_family( $hide ) {
		$option = get_option( 'extendify_site_id', false );

		if ( false === $option ) {
			return $hide;
		}

		return true;
	}
}
