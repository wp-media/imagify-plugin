<?php
declare(strict_types=1);

namespace Imagify\Tracking;

use Imagify\EventManagement\SubscriberInterface;
use Imagify\Optimization\Process\ProcessInterface;

/**
 * Subscriber that hooks Imagify optimization events to tracking.
 *
 * @since 2.3.0
 */
class Subscriber implements SubscriberInterface {

	/**
	 * The tracking service.
	 *
	 * @var Tracking
	 */
	private $tracking;

	/**
	 * Constructor.
	 *
	 * @param Tracking $tracking The tracking service.
	 */
	public function __construct( Tracking $tracking ) {
		$this->tracking = $tracking;
	}

	/**
	 * Returns the list of events this subscriber wants to listen to.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public static function get_subscribed_events(): array {
		return [
			// @action imagify_after_optimize
			'imagify_after_optimize'              => [ 'track_media_optimized', 10, 2 ],
			// @action update_option_imagify_settings fires on single-site saves.
			'update_option_imagify_settings'      => [ 'track_settings_saved', 10, 2 ],
			// @action update_site_option_imagify_settings fires on multisite saves.
			'update_site_option_imagify_settings' => [ 'track_settings_saved', 10, 2 ],
			// @action imagify_after_reset_internal_state
			'imagify_after_reset_internal_state'  => [ 'track_internal_state_reset', 10, 0 ],
			// @action imagify_after_restore_media
			'imagify_after_restore_media'         => [ 'track_media_restored', 10, 4 ],
		];
	}

	/**
	 * Track a "Media Optimized" event after optimization completes.
	 *
	 * @param ProcessInterface $process The optimization process instance.
	 * @param array            $item    The optimization item data.
	 *
	 * @return void
	 */
	public function track_media_optimized( $process, $item ): void {
		$this->tracking->track_media_optimized( $process, $item );
	}

	/**
	 * Delegate to Tracking::track_settings_saved().
	 *
	 * Both update_option_imagify_settings and update_site_option_imagify_settings fire
	 * with different arg ordering (on multisite the first arg is the option name string,
	 * not the old value). Both args are cast to array so the strict-typed downstream
	 * signature is always satisfied. $old_value is unused downstream.
	 *
	 * @param mixed $old_value Old option value, or option name string on multisite.
	 * @param mixed $new_value New option value.
	 *
	 * @return void
	 */
	public function track_settings_saved( $old_value, $new_value ): void {
		$this->tracking->track_settings_saved( (array) $old_value, (array) $new_value );
	}

	/**
	 * Track an "Internal State Reset" event after the internal state is reset.
	 *
	 * @return void
	 */
	public function track_internal_state_reset(): void {
		$this->tracking->track_internal_state_reset();
	}

	/**
	 * Track a "Media Restored" event after a media file is restored.
	 *
	 * @param ProcessInterface $process  The optimization process instance.
	 * @param bool|\WP_Error   $response True on success, WP_Error on failure.
	 * @param array            $files    The list of files before restoring.
	 * @param array            $data     The optimization data captured before deletion.
	 *
	 * @return void
	 */
	public function track_media_restored( $process, $response, array $files, array $data ): void {
		$this->tracking->track_media_restored( $process, $response, $files, $data );
	}
}
