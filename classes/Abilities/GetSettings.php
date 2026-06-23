<?php
declare(strict_types=1);

namespace Imagify\Abilities;

use Imagify_Options;

/**
 * MCP Ability to retrieve Imagify settings.
 */
class GetSettings implements AbilitiesInterface {

	/**
	 * Register the ability.
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
				'label'               => __( 'Get Imagify Settings', 'imagify' ),
				'description'         => __( 'Retrieve current Imagify configuration options.', 'imagify' ),
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
	 * Check if the current user can execute this ability.
	 *
	 * @return bool
	 */
	public function check_permissions(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Execute the ability: retrieve Imagify settings.
	 *
	 * @param array $args Unused.
	 * @return array Full settings object.
	 */
	public function execute( $args = [] ) {
		$options = Imagify_Options::get_instance();
		return $options->get_all();
	}
}
