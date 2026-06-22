<?php
declare(strict_types=1);

namespace Imagify\Abilities;

/**
 * MCP ability: returns the current Imagify configuration settings.
 *
 * Registers itself with the WP Abilities API under the slug
 * `imagify_get_settings` and returns all user-facing options
 * (stripping the internal `version` key and redacting `api_key`).
 *
 * @since 2.3.0
 */
class GetSettings implements AbilitiesInterface {

	/**
	 * Register the ability with the WP Abilities API.
	 *
	 * No-ops gracefully when the API is not available (WP < 6.9).
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'imagify_get_settings',
			[
				'name'                => __( 'Get Imagify settings', 'imagify' ),
				'description'         => __( 'Returns all Imagify configuration options and their current values.', 'imagify' ),
				'category'            => 'imagify',
				'execute_callback'    => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);
	}

	/**
	 * Check if the current user has permission to execute this ability.
	 *
	 * @return bool True when the current user has the `manage_options` capability.
	 */
	public function check_permissions(): bool {
		return (bool) current_user_can( 'manage_options' );
	}

	/**
	 * Execute the ability: return all Imagify settings.
	 *
	 * Strips the internal `version` key and redacts the `api_key` to
	 * avoid leaking the raw API key over the MCP endpoint.
	 *
	 * @return array<string, mixed> All user-facing Imagify options.
	 */
	public function execute(): array {
		$settings = \Imagify_Options::get_instance()->get_all();

		unset( $settings['version'] );
		unset( $settings['api_key'] );

		return $settings;
	}
}
