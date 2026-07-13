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
class UpdateSettings extends AbstractAbility {

	/**
	 * Returns the ability slug.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'imagify/update-settings';
	}

	/**
	 * Returns the ability label.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Update Imagify settings';
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
			'imagify/update-settings',
			[
				'label'               => __( 'Update Imagify settings', 'imagify' ),
				'description'         => __( 'Updates one or more Imagify configuration options. Only supplied keys are changed; others remain unchanged.', 'imagify' ),
				'category'            => 'imagify',
				'execute_callback'    => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'meta'                => [
					'show_in_rest' => true,
					'mcp'          => [ 'public' => true ],
					'annotations'  => [
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					],
				],
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'api_key'                => [
							'type'        => 'string',
							'description' => __( 'Imagify API key. Cannot be updated when IMAGIFY_API_KEY constant is defined.', 'imagify' ),
						],
						'optimization_level'     => [
							'type'        => 'integer',
							'minimum'     => 0,
							'maximum'     => 2,
							'description' => __( 'Optimization aggressiveness: 0 = normal, 1 = aggressive, 2 = ultra.', 'imagify' ),
						],
						'lossless'               => [
							'type'        => 'integer',
							'enum'        => [ 0, 1 ],
							'description' => __( 'Enable lossless compression (0 = off, 1 = on).', 'imagify' ),
						],
						'auto_optimize'          => [
							'type'        => 'integer',
							'enum'        => [ 0, 1 ],
							'description' => __( 'Automatically optimize images on upload (0 = off, 1 = on).', 'imagify' ),
						],
						'backup'                 => [
							'type'        => 'integer',
							'enum'        => [ 0, 1 ],
							'description' => __( 'Keep a backup of original images (0 = off, 1 = on).', 'imagify' ),
						],
						'resize_larger'          => [
							'type'        => 'integer',
							'enum'        => [ 0, 1 ],
							'description' => __( 'Resize images wider than resize_larger_w (0 = off, 1 = on).', 'imagify' ),
						],
						'resize_larger_w'        => [
							'type'        => 'integer',
							'description' => __( 'Maximum image width in pixels when resize_larger is enabled.', 'imagify' ),
						],
						'display_nextgen'        => [
							'type'        => 'integer',
							'enum'        => [ 0, 1 ],
							'description' => __( 'Serve next-gen images to supported browsers (0 = off, 1 = on).', 'imagify' ),
						],
						'display_nextgen_method' => [
							'type'        => 'string',
							'enum'        => [ 'picture', 'rewrite' ],
							'description' => __( 'Delivery method for next-gen images: picture element or URL rewrite.', 'imagify' ),
						],
						'display_webp'           => [
							'type'        => 'integer',
							'enum'        => [ 0, 1 ],
							'description' => __( 'Serve WebP images to supported browsers (0 = off, 1 = on).', 'imagify' ),
						],
						'display_webp_method'    => [
							'type'        => 'string',
							'enum'        => [ 'picture', 'rewrite' ],
							'description' => __( 'Delivery method for WebP images: picture element or URL rewrite.', 'imagify' ),
						],
						'cdn_url'                => [
							'type'        => 'string',
							'description' => __( 'CDN base URL for serving optimized images.', 'imagify' ),
						],
						'disallowed-sizes'       => [
							'type'        => 'object',
							'description' => __( 'Image sizes excluded from optimization, keyed by size slug.', 'imagify' ),
						],
						'admin_bar_menu'         => [
							'type'        => 'integer',
							'enum'        => [ 0, 1 ],
							'description' => __( 'Show Imagify entry in the WordPress admin bar (0 = off, 1 = on).', 'imagify' ),
						],
						'partner_links'          => [
							'type'        => 'integer',
							'enum'        => [ 0, 1 ],
							'description' => __( 'Show partner links in the Imagify admin area (0 = off, 1 = on).', 'imagify' ),
						],
						'convert_to_avif'        => [
							'type'        => 'integer',
							'enum'        => [ 0, 1 ],
							'description' => __( 'Convert images to AVIF format (0 = off, 1 = on).', 'imagify' ),
						],
						'convert_to_webp'        => [
							'type'        => 'integer',
							'enum'        => [ 0, 1 ],
							'description' => __( 'Convert images to WebP format (0 = off, 1 = on).', 'imagify' ),
						],
						'optimization_format'    => [
							'type'        => 'string',
							'enum'        => [ 'off', 'webp', 'avif' ],
							'description' => __( 'Output format for optimized images.', 'imagify' ),
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'required'   => [ 'updated', 'settings' ],
					'properties' => [
						'updated'  => [
							'type'        => 'array',
							'items'       => [ 'type' => 'string' ],
							'description' => __( 'Keys whose values changed after the update.', 'imagify' ),
						],
						'settings' => [
							'type'        => 'object',
							'description' => __( 'Full post-update settings, excluding api_key and version.', 'imagify' ),
						],
					],
				],
			]
		);
	}

	/**
	 * Check if the current user has permission to execute this ability.
	 *
	 * Routes through Imagify's capability abstraction so the `imagify_capacity`
	 * filter and multisite network-admin logic are honoured.
	 *
	 * @return bool True when the current user has the Imagify `manage` capability.
	 */
	protected function has_permission(): bool {
		return imagify_get_context( 'wp' )->current_user_can( 'manage' );
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
		$start_time = microtime( true );
		$result     = $this->do_execute( $args );

		$this->fire_executed( $result, $start_time, is_array( $args ) ? $args : [] );

		return $result;
	}

	/**
	 * Internal execution logic for the ability.
	 *
	 * @param mixed $args Partial settings to update.
	 * @return array|\WP_Error
	 */
	private function do_execute( $args ) {
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

			if ( 'version' === $key ) {
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
