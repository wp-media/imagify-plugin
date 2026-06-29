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
class GetMediaStatus extends AbstractAbility {

	/**
	 * Returns the ability slug.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'imagify/get-media-status';
	}

	/**
	 * Returns the ability label.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Get Media Status';
	}

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
				'meta'                => [
					'show_in_rest' => true,
					'mcp'          => [ 'public' => true ],
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
	 * @since 2.3.0
	 *
	 * @return bool True when the current user has the Imagify `manage` capability.
	 */
	protected function has_permission(): bool {
		return imagify_get_context( 'wp' )->current_user_can( 'manage' );
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
		$start_time = microtime( true );
		$result     = $this->do_execute( $args );

		$this->fire_executed( $result, $start_time, $args );

		return $result;
	}

	/**
	 * Internal execution logic for the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array
	 */
	private function do_execute( array $args ): array {
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

		// Verify the attachment exists.
		if ( ! get_post( $media_id ) ) {
			return [
				'status'             => 'error',
				'error_message'      => 'Media not found.',
				'optimization_level' => null,
				'original_size'      => 0,
				'optimized_size'     => 0,
				'webp_available'     => false,
				'avif_available'     => false,
			];
		}

		$wp_data  = $this->create_wp_data( $media_id );
		$opt_data = $wp_data->get_optimization_data();

		$internal_status = isset( $opt_data['status'] ) ? (string) $opt_data['status'] : '';
		$status          = $this->map_status( $internal_status );

		$level = isset( $opt_data['level'] ) && false !== $opt_data['level']
			? (int) $opt_data['level']
			: null;

		// get_original_size(false) falls back to the filesystem when no optimization data
		// exists yet (unoptimized media), which avoids the 0-byte read from empty meta.
		$original_size  = $wp_data->get_original_size( false );
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
	 * Instantiate the WP optimization-data object for a given media.
	 *
	 * Extracted to a protected method so tests can override it without hitting the database.
	 *
	 * @since 2.3.0
	 *
	 * @param int $media_id WordPress attachment ID.
	 * @return WP
	 */
	protected function create_wp_data( int $media_id ): WP {
		return new WP( $media_id );
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
