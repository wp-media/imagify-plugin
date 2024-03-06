<?php
declare(strict_types=1);

namespace Imagify\Avif;

use Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use Imagify\Avif\RewriteRules\Display as RewriteRules;

/**
 * Service provider for AVIF rewrite rules
 */
class ServiceProvider extends AbstractServiceProvider {
	/**
	 * Services provided by this provider
	 *
	 * @var array
	 */
	protected $provides = [
		'avif_display',
		'avif_rewrite_rules',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		'avif_display',
		'avif_rewrite_rules',
	];

	/**
	 * Registers the provided classes
	 *
	 * @return void
	 */
	public function register() {
		$this->getContainer()->share( 'avif_display', Display::class );
		$this->getContainer()->share( 'avif_rewrite_rules', RewriteRules::class );
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
