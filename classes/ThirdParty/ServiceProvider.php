<?php
declare(strict_types=1);

namespace Imagify\ThirdParty;

use Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use Imagify\ThirdParty\Plugins\GravityForms;

/**
 * Service provider for Third Party(Plugins, Themes, Hosting).
 */
class ServiceProvider extends AbstractServiceProvider {
	/**
	 * Services provided by this provider
	 *
	 * @var array
	 */
	protected $provides = [
		'gravity_from_subscriber',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		'gravity_from_subscriber',
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
	 * Returns the subscribers array
	 *
	 * @return array
	 */
	public function get_subscribers() {
		return $this->subscribers;
	}

	/**
	 * Registers the provided classes
	 *
	 * @return void
	 */
	public function register(): void {
		$this->getContainer()->addShared( 'gravity_from_subscriber', GravityForms::class );
	}
}
