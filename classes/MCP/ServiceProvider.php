<?php
declare(strict_types=1);

namespace Imagify\MCP;

use Imagify\Abilities\GenerateMissingNextgen;
use Imagify\Bulk\Bulk;
use Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * Service provider for the MCP (Model Context Protocol) module.
 *
 * Wires `ConfigSubscriber` and `AbilitiesSubscriber` into the DI container,
 * along with all concrete ability classes injected into `AbilitiesSubscriber`.
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
		Bulk::class,
		GenerateMissingNextgen::class,
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
	 * Bulk is registered by passing the already-initialised singleton directly
	 * (Plugin::init() calls Bulk::get_instance()->init() before any provider
	 * runs), so no factory closure is needed. This avoids the ~12 KB opcode
	 * allocation that a closure would incur in CLI environments with a tight
	 * memory limit (e.g. wp-env at 128 MB).
	 *
	 * @return void
	 */
	public function register(): void {
		$this->getContainer()->addShared( Bulk::class, Bulk::get_instance() );

		$this->getContainer()->addShared( GenerateMissingNextgen::class )
			->addArgument( Bulk::class );

		$this->getContainer()->addShared( ConfigSubscriber::class );
		$this->getContainer()->addShared( AbilitiesSubscriber::class )
			->addArgument( GenerateMissingNextgen::class );
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
