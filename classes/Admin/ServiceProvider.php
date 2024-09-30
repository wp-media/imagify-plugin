<?php
declare(strict_types=1);

namespace Imagify\Admin;

use Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use Imagify\User\User;
use Imagify\Admin\AdminSubscriber;

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
		'admin_subscriber',
        'user',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		'admin_subscriber',
	];

	/**
	 * Registers the provided classes
	 *
	 * @return void
	 */
	public function register() {
        $this->getContainer()->add( 'user', User::class );
        
		$this->getContainer()->share( 'admin_subscriber', AdminSubscriber::class )
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
