<?php
declare(strict_types=1);

namespace Imagify\Admin;

use Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * Service provider for Admin
 */
class ServiceProvider extends AbstractServiceProvider {
	/**
	 * Services provided by this provider
	 *
	 * @var array
	 */
	protected $provides = [
		'admin_bar',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		'admin_bar',
	];

	/**
	 * Registers the provided classes
	 *
	 * @return void
	 */
	public function register() {
		$this->getContainer()->share( 'admin_bar', AdminBar::class )
			->addArgument( $this->getContainer()->get( 'user' ) );
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
