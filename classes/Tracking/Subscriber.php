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
			'imagify_after_optimize'      => [ 'track_media_optimized', 10, 2 ],
			// @action imagify_after_restore_media
			'imagify_after_restore_media' => [ 'track_media_restored', 10, 4 ],
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
