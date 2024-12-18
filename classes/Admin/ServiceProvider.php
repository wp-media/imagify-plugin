<?php
declare(strict_types=1);

namespace Imagify\Admin;

use Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use Imagify\Dependencies\WPMedia\PluginFamily\Controller\PluginFamily;
use Imagify\User\User;

/**
 * Service provider for Admin.
 */
class ServiceProvider extends AbstractServiceProvider {
	/**
	 * Services provided by this provider
	 *
	 * @var array
	 */
	protected $provides = [
		AdminBar::class,
		AdminSubscriber::class,
		PluginFamily::class,
		PluginFamilySubscriber::class,
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		AdminBar::class,
		AdminSubscriber::class,
		PluginFamilySubscriber::class,
	];

	/**
	 * Check if the service provider provides a specific service.
	 *
	 * @param string $id The id of the service.
	 *
	 * @return bool
	 */
	public function provides( string $id ): bool {
		return in_array( $id, $this->provides, true );
	}

	/**
	 * Registers the provided classes
	 *
	 * @return void
	 */
	public function register(): void {
		$this->getContainer()->addShared( AdminBar::class )
			->addArgument( User::class );
		$this->getContainer()->addShared( AdminSubscriber::class )
			->addArgument( User::class );

		$this->getContainer()->add( PluginFamily::class );
		$this->getContainer()->addShared( PluginFamilySubscriber::class )
			->addArgument( PluginFamily::class );
	}

	/**
	 * Returns the subscribers array
	 *
	 * @return array
	 */
	public function get_subscribers() {
		return $this->subscribers;
	}
}
