<?php
declare(strict_types=1);

namespace Imagify\Tracking;

use Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use Imagify\Dependencies\WPMedia\Mixpanel\Optin;
use Imagify\Dependencies\WPMedia\Mixpanel\TrackingPlugin;

/**
 * Service provider for the Tracking module.
 *
 * Wires Optin, TrackingPlugin, Tracking, and Subscriber into the DI container.
 *
 * @since 2.3.0
 */
class ServiceProvider extends AbstractServiceProvider {

	const MIXPANEL_TOKEN = '517e881edc2636e99a2ecf013d8134d3';
	const APPLICATION    = 'imagify';
	const BRAND          = 'wp media';

	/**
	 * Services provided by this provider.
	 *
	 * @var array<int, string>
	 */
	protected $provides = [
		Optin::class,
		TrackingPlugin::class,
		Tracking::class,
		Subscriber::class,
		McpTracking::class,
		McpTrackingSubscriber::class,
		Notices::class,
	];

	/**
	 * Subscribers provided by this provider.
	 *
	 * @var array<int, string>
	 */
	public $subscribers = [
		Subscriber::class,
		McpTrackingSubscriber::class,
		Notices::class,
	];

	/**
	 * Checks whether this provider provides a given service.
	 *
	 * @param string $id Service identifier.
	 *
	 * @return bool
	 */
	public function provides( string $id ): bool {
		return in_array( $id, $this->provides, true );
	}

	/**
	 * Registers the provided services into the container.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->getContainer()->addShared( Optin::class )
			->addArguments( [ self::APPLICATION, 'manage_options' ] );

		$this->getContainer()->addShared( TrackingPlugin::class )
			->addArguments( [ self::MIXPANEL_TOKEN, self::APPLICATION . ' ' . IMAGIFY_VERSION, self::BRAND, self::APPLICATION ] );

		$this->getContainer()->addShared( Tracking::class )
			->addArguments( [ Optin::class, TrackingPlugin::class ] );

		$this->getContainer()->addShared( Subscriber::class )
			->addArgument( Tracking::class );

		$this->getContainer()->addShared( McpTracking::class )
			->addArguments( [ Optin::class, TrackingPlugin::class ] );

		$this->getContainer()->addShared( McpTrackingSubscriber::class )
			->addArgument( McpTracking::class );

		$this->getContainer()->addShared( Notices::class )
			->addArgument( Optin::class );
	}

	/**
	 * Returns the list of subscriber class names.
	 *
	 * @return array<int, string>
	 */
	public function get_subscribers(): array {
		return $this->subscribers;
	}
}
