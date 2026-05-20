<?php
declare(strict_types=1);

namespace Imagify\Tools;

use Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * Service provider for the Tools module.
 *
 * @since 2.3
 */
class ServiceProvider extends AbstractServiceProvider {

	/**
	 * Services registered by this provider.
	 *
	 * @var string[]
	 */
	protected $provides = [
		ResetInternalState::class,
		Subscriber::class,
	];

	/**
	 * Subscribers exposed to the event manager.
	 *
	 * @var string[]
	 */
	public $subscribers = [
		Subscriber::class,
	];

	/**
	 * Check whether the given service ID is provided by this provider.
	 *
	 * @param string $id Service identifier.
	 *
	 * @return bool
	 */
	public function provides( string $id ): bool {
		return in_array( $id, $this->provides, true );
	}

	/**
	 * Bind concrete implementations into the container.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->getContainer()->addShared( ResetInternalState::class );

		$this->getContainer()->addShared( Subscriber::class )
			->addArgument( ResetInternalState::class );
	}

	/**
	 * Return the list of subscriber class names managed by this provider.
	 *
	 * @return string[]
	 */
	public function get_subscribers(): array {
		return $this->subscribers;
	}
}
