<?php
declare(strict_types=1);

namespace Imagify\Stats;

use Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * Service provider for Stats
 */
class ServiceProvider extends AbstractServiceProvider {
	/**
	 * Services provided by this provider
	 *
	 * @var array
	 */
	protected $provides = [
		'optimized_media_without_next_gen',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		'optimized_media_without_next_gen',
	];

	/**
	 * Registers the provided classes
	 *
	 * @return void
	 */
	public function register() {
		$this->getContainer()->share( 'optimized_media_without_next_gen', OptimizedMediaWithoutNextGen::class );
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
