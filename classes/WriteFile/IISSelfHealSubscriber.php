<?php
declare(strict_types=1);

namespace Imagify\WriteFile;

use Imagify\Avif\Display as AvifDisplay;
use Imagify\EventManagement\SubscriberInterface;
use Imagify\Webp\Display as WebpDisplay;

/**
 * One-time self-heal of IIS `web.config` files broken by duplicate `<staticContent>`
 * siblings (issue #509): older versions wrote a whole `<staticContent>` collection for
 * both the WebP and AVIF MIME mappings, and IIS allows only one such collection under
 * `system.webServer`, so a second sibling made IIS return HTTP 500 for the whole site.
 *
 * @since 2.3.4
 */
class IISSelfHealSubscriber implements SubscriberInterface {

	/**
	 * Version from which the root-cause fix (single shared `<staticContent>`) ships.
	 *
	 * @var string
	 */
	const FIXED_IN_VERSION = '2.3.5';

	/**
	 * Returns an array of events this subscriber listens to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() {
		return [
			// @action imagify_upgrade
			'imagify_upgrade' => 'maybe_self_heal_static_content',
		];
	}

	/**
	 * Collapse already-broken installs' duplicate `<staticContent>` siblings into the
	 * single shared collection the root-cause fix now maintains.
	 *
	 * Remove-then-add: strip all Imagify-created `<staticContent>` containers and stray
	 * mimeMaps, then re-add both leaf mimeMaps (mirroring Display::activate(), gated
	 * solely on `display_nextgen`) when the setting is on. Never fatals: each writer is
	 * only touched when its file exists and is writable.
	 *
	 * @since 2.3.4
	 *
	 * @param string $network_version Previous version stored on the network.
	 * @param string $site_version    Previous version stored on site level.
	 */
	public function maybe_self_heal_static_content( $network_version, $site_version ) {
		if ( version_compare( $site_version, self::FIXED_IN_VERSION, '>=' ) ) {
			return;
		}

		// WebpDisplay::get_instance() (singleton) vs `new AvifDisplay()` is intentional,
		// not an oversight: unlike Webp\Display, Avif\Display doesn't use
		// InstanceGetterTrait and has no get_instance() to call.
		$webp_conf = WebpDisplay::get_instance()->get_iis_conf();

		// Not on IIS (nothing to heal, or a different server writer altogether).
		if ( ! $webp_conf ) {
			return;
		}

		$avif_conf = ( new AvifDisplay() )->get_iis_conf();

		if ( ! $avif_conf ) {
			return;
		}

		$filesystem = \Imagify_Filesystem::get_instance();

		$webp_ready = $filesystem->exists( $webp_conf->get_file_path() ) && ! is_wp_error( $webp_conf->is_file_writable() );
		$avif_ready = $filesystem->exists( $avif_conf->get_file_path() ) && ! is_wp_error( $avif_conf->is_file_writable() );

		if ( $webp_ready ) {
			$webp_conf->remove();
		}

		if ( $avif_ready ) {
			$avif_conf->remove();
		}

		if ( ! get_imagify_option( 'display_nextgen' ) ) {
			return;
		}

		if ( $webp_ready ) {
			$webp_conf->add();
		}

		if ( $avif_ready ) {
			$avif_conf->add();
		}
	}
}
