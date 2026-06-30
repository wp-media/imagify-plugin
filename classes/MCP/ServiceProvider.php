<?php
declare(strict_types=1);

namespace Imagify\MCP;

use Imagify\Abilities\GetAccount;
use Imagify\Abilities\GetMediaStatus;
use Imagify\Abilities\GetNextgenCoverage;
use Imagify\Abilities\GetSettings;
use Imagify\Abilities\GetStats;
use Imagify\Abilities\OptimizeMedia;
use Imagify\Abilities\UpdateSettings;
use Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use Imagify\Stats\OptimizedMediaWithoutNextGen;

/**
 * Service provider for the MCP (Model Context Protocol) module.
 *
 * Wires `ConfigSubscriber` and `AbilitiesSubscriber` into the DI container.
 * For the foundation `AbilitiesSubscriber` is registered with no ability
 * arguments. Downstream sub-issues extend the wiring via `addArguments()`
 * once concrete ability classes are added (see Downstream Wiring Contract
 * in spec #1108).
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
		GetAccount::class,
		GetMediaStatus::class,
		GetNextgenCoverage::class,
		GetSettings::class,
		GetStats::class,
		OptimizeMedia::class,
		UpdateSettings::class,
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
		$this->getContainer()->addShared( GetAccount::class );
		$this->getContainer()->addShared( GetMediaStatus::class );
		$this->getContainer()->addShared( GetNextgenCoverage::class )
			->addArgument( OptimizedMediaWithoutNextGen::class );
		$this->getContainer()->addShared( GetSettings::class );
		$this->getContainer()->addShared( GetStats::class );
		$this->getContainer()->addShared( OptimizeMedia::class );
		$this->getContainer()->addShared( UpdateSettings::class );
		$this->getContainer()->addShared( AbilitiesSubscriber::class )
			->addArguments( [ GetAccount::class, GetMediaStatus::class, GetNextgenCoverage::class, GetSettings::class, GetStats::class, OptimizeMedia::class, UpdateSettings::class ] );
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
