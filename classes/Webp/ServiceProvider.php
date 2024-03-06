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
		'webp_display',
		'webp_rewrite_rules',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		'webp_display',
		'webp_rewrite_rules',
	];

	/**
	 * Registers the provided classes
	 *
	 * @return void
	 */
	public function register() {
		$this->getContainer()->share( 'webp_display', Display::class );
		$this->getContainer()->share( 'webp_rewrite_rules', RewriteRules::class );
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
