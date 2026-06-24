<?php
declare(strict_types=1);

namespace Imagify\Abilities;

/**
 * MCP ability: updates one or more Imagify configuration settings.
 *
 * Registers itself with the WP Abilities API under the slug
 * `imagify/update-settings`. Accepts a partial settings object and
 * updates only the supplied keys.
 *
 * @since 2.3.0
 */
class UpdateSettings implements AbilitiesInterface {

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
			'imagify/update-settings',
			[
				'label'               => __( 'Update Imagify settings', 'imagify' ),
				'description'         => __( 'Updates one or more Imagify configuration options. Only supplied keys are changed; others remain unchanged.', 'imagify' ),
				'category'            => 'imagify',
				'execute_callback'    => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => true,
				],
				'meta'                => [
					'show_in_rest' => true,
					'mcp'          => [ 'public' => true ],
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
	 * Validate a single setting value against known constraints.
	 *
	 * Returns WP_Error for clearly invalid constrained values; otherwise
	 * returns the value untouched and lets the WP option filter normalize it.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Proposed value.
	 * @return mixed|\WP_Error
	 */
	protected function validate_value( string $key, $value ) {
		switch ( $key ) {
			case 'optimization_level':
				if ( ! in_array( (int) $value, [ 0, 1, 2 ], true ) ) {
					return new \WP_Error(
						'imagify_invalid_value',
						sprintf(
							/* translators: 1: setting key name, 2: list of allowed values. */
							__( 'Invalid value for "%1$s". Expected one of: %2$s.', 'imagify' ),
							$key,
							'0, 1, 2'
						)
					);
				}
				break;

			case 'optimization_format':
				if ( ! in_array( $value, [ 'off', 'webp', 'avif' ], true ) ) {
					return new \WP_Error(
						'imagify_invalid_value',
						sprintf(
							/* translators: 1: setting key name, 2: list of allowed values. */
							__( 'Invalid value for "%1$s". Expected one of: %2$s.', 'imagify' ),
							$key,
							'"off", "webp", "avif"'
						)
					);
				}
				break;

			case 'display_nextgen_method':
			case 'display_webp_method':
				if ( ! in_array( $value, [ 'picture', 'rewrite' ], true ) ) {
					return new \WP_Error(
						'imagify_invalid_value',
						sprintf(
							/* translators: 1: setting key name, 2: list of allowed values. */
							__( 'Invalid value for "%1$s". Expected one of: %2$s.', 'imagify' ),
							$key,
							'"picture", "rewrite"'
						)
					);
				}
				break;
		}

		return $value;
	}

	/**
	 * Fetch the Imagify_Options singleton.
	 *
	 * Extracted so tests can override without re-mocking the global singleton.
	 *
	 * @return \Imagify_Options
	 */
	protected function fetch_options_instance(): \Imagify_Options {
		return \Imagify_Options::get_instance();
	}

	/**
	 * Execute the ability: update Imagify settings.
	 *
	 * Accepts a partial settings object. Only provided keys are updated;
	 * others remain unchanged. Invalid values are rejected with WP_Error.
	 *
	 * @param mixed $args Partial settings to update.
	 * @return array|\WP_Error Array with `updated` and `settings` keys on success.
	 */
	public function execute( $args = [] ) {
		$options  = $this->fetch_options_instance();
		$defaults = $options->get_default_values();
		$before   = $options->get_all();

		$to_update = [];

		foreach ( $args as $key => $value ) {
			if ( ! array_key_exists( $key, $defaults ) ) {
				return new \WP_Error(
					'imagify_unknown_setting',
					sprintf(
						/* translators: %s: the unknown setting key name. */
						__( '"%s" is not a valid Imagify setting key.', 'imagify' ),
						$key
					)
				);
			}

			if ( 'api_key' === $key && defined( 'IMAGIFY_API_KEY' ) && IMAGIFY_API_KEY ) {
				return new \WP_Error(
					'imagify_api_key_immutable',
					/* translators: IMAGIFY_API_KEY is a PHP constant name, do not translate it. */
					__( 'api_key cannot be updated when IMAGIFY_API_KEY constant is defined.', 'imagify' )
				);
			}

			$validated = $this->validate_value( $key, $value );
			if ( is_wp_error( $validated ) ) {
				return $validated;
			}

			$to_update[ $key ] = $validated;
		}

		$options->set( $to_update );

		$after        = $options->get_all();
		$updated_keys = [];
		foreach ( array_keys( $to_update ) as $key ) {
			if ( ( $before[ $key ] ?? null ) !== ( $after[ $key ] ?? null ) ) {
				$updated_keys[] = $key;
			}
		}

		$settings = $after;
		unset( $settings['version'], $settings['api_key'] );

		return [
			'updated'  => $updated_keys,
			'settings' => $settings,
		];
	}
}
