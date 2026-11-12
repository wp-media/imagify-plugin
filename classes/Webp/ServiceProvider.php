<?php
declare(strict_types=1);

namespace Imagify\Webp;

use Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use Imagify\Webp\RewriteRules\Display as RewriteRules;

/**
 * Service provider for WebP rewrite rules
 */
class ServiceProvider extends AbstractServiceProvider {
	/**
	 * Services provided by this provider
	 *
	 * @var array
	 */
	protected $provides = [
		Display::class,
		RewriteRules::class,
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		Display::class,
		RewriteRules::class,
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
		$this->getContainer()->addShared( Display::class );
		$this->getContainer()->addShared( RewriteRules::class );
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
