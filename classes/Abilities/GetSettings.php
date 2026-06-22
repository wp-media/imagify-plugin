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
			'imagify/get-settings',
			[
				'label'               => __( 'Get Imagify settings', 'imagify' ),
				'description'         => __( 'Returns all Imagify configuration options and their current values.', 'imagify' ),
				'category'            => 'imagify',
				'execute_callback'    => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'meta'                => [
					'show_in_rest' => true,
					'mcp'          => [
						'public' => true,
					],
				],
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
	 * Fetch the raw settings array from the options layer.
	 *
	 * Extracted into a protected method so that unit tests can override
	 * this call without needing to re-mock the legacy singleton.
	 *
	 * @return array<string, mixed>
	 */
	protected function fetch_raw_settings(): array {
		return \Imagify_Options::get_instance()->get_all();
	}

	/**
	 * Execute the ability: return all Imagify settings.
	 *
	 * Strips the internal `version` key and omits `api_key` to
	 * avoid leaking credentials over the MCP endpoint.
	 *
	 * @return array<string, mixed> All user-facing Imagify options.
	 */
	public function execute(): array {
		$settings = $this->fetch_raw_settings();

		unset( $settings['version'] );
		unset( $settings['api_key'] );

		return $settings;
	}
}
