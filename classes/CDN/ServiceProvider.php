<?php
declare(strict_types=1);

namespace Imagify\CDN;

use Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * Service provider for CDN compatibility
 */
class ServiceProvider extends AbstractServiceProvider {
	/**
	 * Services provided by this provider
	 *
	 * @var array
	 */
	protected $provides = [
		'cdn',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		'cdn',
	];

	/**
	 * Registers the provided classes
	 *
	 * @return void
	 */
	public function register() {
		$this->getContainer()->share( 'cdn', CDN::class );
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
