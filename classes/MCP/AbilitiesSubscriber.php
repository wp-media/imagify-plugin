<?php
declare(strict_types=1);

namespace Imagify\MCP;

use Imagify\Abilities\AbilitiesInterface;
use Imagify\EventManagement\SubscriberInterface;

/**
 * Registers the Imagify ability category and all Imagify MCP abilities.
 *
 * Listens on `wp_abilities_api_categories_init` to register the `imagify`
 * category and on `wp_abilities_api_init` to register each concrete ability
 * that is injected via the constructor.
 *
 * All 7 ability instances are wired by `ServiceProvider` and injected at
 * construction time. See docs/api/mcp.md for the full list of abilities.
 *
 * @since 2.3.0
 */
class AbilitiesSubscriber implements SubscriberInterface {

	/**
	 * Ability instances to register on `wp_abilities_api_init`.
	 *
	 * @var AbilitiesInterface[]
	 */
	private $abilities;

	/**
	 * Constructor.
	 *
	 * @param AbilitiesInterface ...$abilities Ability instances to register.
	 */
	public function __construct( AbilitiesInterface ...$abilities ) {
		$this->abilities = $abilities;
	}

	/**
	 * Returns the events this subscriber listens to.
	 *
	 * @return array<string, string>
	 */
	public static function get_subscribed_events(): array {
		return [
			// @action wp_abilities_api_categories_init
			'wp_abilities_api_categories_init' => 'register_categories',
			// @action wp_abilities_api_init
			'wp_abilities_api_init'            => 'register_abilities',
		];
	}

	/**
	 * Registers the `imagify` ability category.
	 *
	 * No-ops gracefully when the WP Abilities API is not available (WP < 6.9).
	 *
	 * @return void
	 */
	public function register_categories(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			'imagify',
			[
				'label'       => __( 'Imagify', 'imagify' ),
				'description' => __( 'Image optimization tools for WordPress.', 'imagify' ),
			]
		);
	}

	/**
	 * Registers all injected Imagify abilities.
	 *
	 * No-ops gracefully when the WP Abilities API is not available (WP < 6.9).
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		foreach ( $this->abilities as $ability ) {
			$ability->register();
		}
	}
}
