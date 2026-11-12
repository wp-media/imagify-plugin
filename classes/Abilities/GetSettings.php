<?php
declare(strict_types=1);

namespace Imagify\Abilities;

/**
 * MCP ability: returns the current Imagify configuration settings.
 *
 * Registers itself with the WP Abilities API under the slug
 * `imagify/get-settings` and returns all user-facing options
 * (stripping the internal `version` key and redacting `api_key`).
 *
 * @since 2.3.0
 */
class GetSettings extends AbstractAbility {

	/**
	 * Returns the ability slug.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'imagify/get-settings';
	}

	/**
	 * Returns the ability label.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Get Imagify settings';
	}

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
					'annotations'  => [
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					],
				],
			]
		);
	}

	/**
	 * Routes through Imagify's capability abstraction so the `imagify_capacity`
	 * filter and multisite network-admin logic are honoured.
	 *
	 * @return bool True when the current user has the Imagify `manage` capability.
	 */
	protected function has_permission(): bool {
		return imagify_get_context( 'wp' )->current_user_can( 'manage' );
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
		$start_time = microtime( true );
		$result     = $this->do_execute();

		$this->fire_executed( $result, $start_time );

		return $result;
	}

	/**
	 * Internal execution logic for the ability.
	 *
	 * @return array<string, mixed>
	 */
	private function do_execute(): array {
		$settings = $this->fetch_raw_settings();

		unset( $settings['version'] );
		unset( $settings['api_key'] );

		return $settings;
	}
}
