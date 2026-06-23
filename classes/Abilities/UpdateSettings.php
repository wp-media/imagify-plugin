<?php
declare(strict_types=1);

namespace Imagify\Abilities;

use Imagify_Options;

/**
 * MCP Ability to update Imagify settings.
 */
class UpdateSettings implements AbilitiesInterface {

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
			'imagify/update-settings',
			[
				'label'               => __( 'Update Imagify Settings', 'imagify' ),
				'description'         => __( 'Update Imagify configuration options (optimization level, CDN URL, formats, etc).', 'imagify' ),
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
	 * Execute the ability: update Imagify settings.
	 *
	 * The `$args` parameter is passed by the WP Abilities API callback wrapper.
	 *
	 * @param array $args Partial settings object to update (optional).
	 * @return array|WP_Error {
	 *     @type array $updated Keys that were changed.
	 *     @type array $settings Full settings after the update.
	 * } or WP_Error on failure.
	 */
	public function execute( $args = [] ) {
		$options = Imagify_Options::get_instance();

		// Get current settings.
		$current = $options->get_all();

		// Validate & sanitize provided keys.
		$validated = [];
		foreach ( $args as $key => $value ) {
			// Prevent api_key update if IMAGIFY_API_KEY constant is defined.
			if ( 'api_key' === $key && defined( 'IMAGIFY_API_KEY' ) && IMAGIFY_API_KEY ) {
				return new \WP_Error(
					'imagify_api_key_immutable',
					/* translators: Describes why the API key cannot be updated. */
					__( 'api_key cannot be updated when IMAGIFY_API_KEY constant is defined.', 'imagify' )
				);
			}

			// Sanitize and validate the value.
			$sanitized = $options->sanitize_and_validate_value( $key, $value );
			if ( is_wp_error( $sanitized ) ) {
				return $sanitized;
			}

			$validated[ $key ] = $sanitized;
		}

		// Merge validated values with current settings.
		$updated_values = array_merge( $current, $validated );

		// Save the updated settings.
		$options->set( $updated_values );

		// Fetch final state.
		$final = $options->get_all();

		// Compute changed keys.
		$updated_keys = array_keys( array_diff_assoc( $validated, $current ) );

		// Return success response with updated keys and final settings.
		return [
			'updated'  => $updated_keys,
			'settings' => $final,
		];
	}
}
