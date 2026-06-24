<?php
declare(strict_types=1);

namespace Imagify\Abilities;

use Imagify\Optimization\Data\WP;
use Imagify\Optimization\Process\ProcessInterface;

/**
 * MCP ability: imagify/get-media-status
 *
 * Returns the optimization status and key metrics for a given WordPress
 * media library attachment.
 *
 * @since 2.3.0
 */
class GetMediaStatus implements AbilitiesInterface {

	/**
	 * Register the ability with the WP Abilities API.
	 *
	 * No-ops silently when `wp_register_ability` is unavailable (WP < 6.9).
	 *
	 * @since 2.3.0
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'imagify/get-media-status',
			[
				'label'               => __( 'Get Media Status', 'imagify' ),
				'description'         => __( 'Retrieve the optimization status and metrics for a WordPress media library attachment.', 'imagify' ),
				'category'            => 'imagify',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'media_id' => [
							'type'        => 'integer',
							'description' => __( 'The WordPress attachment ID.', 'imagify' ),
						],
					],
					'required'   => [ 'media_id' ],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'status'             => [
							'type'        => 'string',
							'description' => __( 'Optimization status: "success", "error", or "unoptimized".', 'imagify' ),
							'enum'        => [ 'success', 'error', 'unoptimized' ],
						],
						'optimization_level' => [
							'type'        => [ 'integer', 'null' ],
							'description' => __( '0 = lossless, 1 = aggressive, 2 = ultra. Null when not optimized.', 'imagify' ),
						],
						'original_size'      => [
							'type'        => 'integer',
							'description' => __( 'File size in bytes before optimization.', 'imagify' ),
						],
						'optimized_size'     => [
							'type'        => 'integer',
							'description' => __( 'File size in bytes after optimization.', 'imagify' ),
						],
						'webp_available'     => [
							'type'        => 'boolean',
							'description' => __( 'True when a WebP version of the full-size image has been generated.', 'imagify' ),
						],
						'avif_available'     => [
							'type'        => 'boolean',
							'description' => __( 'True when an AVIF version of the full-size image has been generated.', 'imagify' ),
						],
						'error_message'      => [
							'type'        => [ 'string', 'null' ],
							'description' => __( 'Human-readable error message when status is "error". Null otherwise.', 'imagify' ),
						],
					],
				],
				'execute_callback'    => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'annotations'         => [
					'readonly'    => true,
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
	 * Check whether the current user may execute this ability.
	 *
	 * @since 2.3.0
	 *
	 * @return bool
	 */
	public function check_permissions(): bool {
		return (bool) current_user_can( 'manage_options' );
	}

	/**
	 * Execute the ability and return the media optimization status.
	 *
	 * @since 2.3.0
	 *
	 * @param array $args Input arguments. Expects `media_id` (int) — the WordPress attachment ID.
	 * @return array Optimization status response keyed by status, optimization_level, original_size,
	 *               optimized_size, webp_available, avif_available, and error_message.
	 */
	public function execute( array $args = [] ): array {
		$media_id = isset( $args['media_id'] ) ? (int) $args['media_id'] : 0;

		if ( $media_id <= 0 ) {
			return [
				'status'             => 'error',
				'error_message'      => 'Invalid or missing media_id',
				'optimization_level' => null,
				'original_size'      => 0,
				'optimized_size'     => 0,
				'webp_available'     => false,
				'avif_available'     => false,
			];
		}

		$opt_data = ( new WP( $media_id ) )->get_optimization_data();

		$internal_status = isset( $opt_data['status'] ) ? (string) $opt_data['status'] : '';
		$status          = $this->map_status( $internal_status );

		$level = isset( $opt_data['level'] ) && false !== $opt_data['level']
			? (int) $opt_data['level']
			: null;

		$original_size  = isset( $opt_data['stats']['original_size'] ) ? (int) $opt_data['stats']['original_size'] : 0;
		$optimized_size = isset( $opt_data['stats']['optimized_size'] ) ? (int) $opt_data['stats']['optimized_size'] : 0;

		$webp_available = false;
		$avif_available = false;

		if ( ! empty( $opt_data['sizes'] ) && is_array( $opt_data['sizes'] ) ) {
			foreach ( array_keys( $opt_data['sizes'] ) as $size_key ) {
				if ( ! $webp_available && $this->size_key_ends_with( (string) $size_key, ProcessInterface::WEBP_SUFFIX ) ) {
					$webp_available = true;
				}
				if ( ! $avif_available && $this->size_key_ends_with( (string) $size_key, ProcessInterface::AVIF_SUFFIX ) ) {
					$avif_available = true;
				}
				if ( $webp_available && $avif_available ) {
					break;
				}
			}
		}

		$error_message = null;
		if ( 'error' === $status ) {
			$error_message = isset( $opt_data['message'] ) && '' !== $opt_data['message']
				? (string) $opt_data['message']
				: null;
		}

		return [
			'status'             => $status,
			'optimization_level' => $level,
			'original_size'      => $original_size,
			'optimized_size'     => $optimized_size,
			'webp_available'     => $webp_available,
			'avif_available'     => $avif_available,
			'error_message'      => $error_message,
		];
	}

	/**
	 * Map the internal Imagify optimization status to the public API status.
	 *
	 * @since 2.3.0
	 *
	 * @param string $internal_status The internal status string from `_imagify_status` meta.
	 * @return string 'success', 'error', or 'unoptimized'.
	 */
	private function map_status( string $internal_status ): string {
		if ( 'success' === $internal_status || 'already_optimized' === $internal_status ) {
			return 'success';
		}

		if ( 'error' === $internal_status ) {
			return 'error';
		}

		return 'unoptimized';
	}

	/**
	 * Check whether a size key ends with a given suffix.
	 *
	 * @since 2.3.0
	 *
	 * @param string $size_key The thumbnail size key to inspect.
	 * @param string $suffix   The suffix to check for.
	 * @return bool
	 */
	private function size_key_ends_with( string $size_key, string $suffix ): bool {
		$suffix_length = strlen( $suffix );

		if ( 0 === $suffix_length ) {
			return true;
		}

		return substr( $size_key, -$suffix_length ) === $suffix;
	}
}
