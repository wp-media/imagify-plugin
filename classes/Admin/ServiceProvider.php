<?php
declare(strict_types=1);

namespace Imagify\Admin;

use Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use Imagify\Dependencies\WPMedia\PluginFamily\Controller\PluginFamily;

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
		'admin_bar',
		'admin_subscriber',
		'plugin_family',
		'plugin_family_subscriber',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		'admin_bar',
		'admin_subscriber',
		'plugin_family_subscriber',
	];

	/**
	 * Registers the provided classes
	 *
	 * @return void
	 */
	public function register() {

		$this->getContainer()->share( 'admin_bar', AdminBar::class )
			->addArgument( $this->getContainer()->get( 'user' ) );
		$this->getContainer()->share( 'admin_subscriber', AdminSubscriber::class )
			->addArgument( $this->getContainer()->get( 'user' ) );

		$this->getContainer()->add( 'plugin_family', PluginFamily::class );

		$this->getContainer()->add( 'plugin_family_subscriber', PluginFamilySubscriber::class )
			->addArgument( $this->getContainer()->get( 'plugin_family' ) );
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
