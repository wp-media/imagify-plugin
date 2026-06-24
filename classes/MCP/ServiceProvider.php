<?php
declare(strict_types=1);

namespace Imagify\MCP;

use Imagify\Abilities\BulkOptimize;
use Imagify\Bulk\Bulk;
use Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * Service provider for the MCP (Model Context Protocol) module.
 *
 * Wires `ConfigSubscriber`, `AbilitiesSubscriber`, and all concrete ability
 * classes into the DI container.
 *
 * @since 2.3.0
 */
class ServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 *
	 * @var array<int, string>
	 */
	protected $provides = [
		ConfigSubscriber::class,
		AbilitiesSubscriber::class,
		BulkOptimize::class,
	];

	/**
	 * Subscribers provided by this provider.
	 *
	 * @var array<int, string>
	 */
	public $subscribers = [
		ConfigSubscriber::class,
		AbilitiesSubscriber::class,
	];

	/**
	 * Checks whether this provider provides a given service.
	 *
	 * @param string $id Service identifier.
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
		$this->getContainer()->addShared( ConfigSubscriber::class );
		$this->getContainer()->addShared( BulkOptimize::class )
			->addArgument( Bulk::get_instance() );
		$this->getContainer()->addShared( AbilitiesSubscriber::class )
			->addArguments( [ BulkOptimize::class ] );
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
