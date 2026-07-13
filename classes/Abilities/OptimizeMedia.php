<?php
declare(strict_types=1);

namespace Imagify\Abilities;

/**
 * MCP ability: optimize a media on-demand.
 *
 * Registers itself with the WP Abilities API under the slug
 * `imagify/optimize-media` and delegates to the existing
 * `Imagify\Optimization\Process\WP` class.
 *
 * @since 2.3.0
 */
class OptimizeMedia extends AbstractAbility {

	const ABILITY_ID   = 'imagify/optimize-media';
	const ABILITY_NAME = 'Optimize media';

	/**
	 * Returns the ability slug.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return self::ABILITY_ID;
	}

	/**
	 * Returns the human-readable ability label.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return self::ABILITY_NAME;
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
			'imagify/optimize-media',
			[
				'label'               => __( 'Optimize media', 'imagify' ),
				'description'         => __( 'Optimizes a specific media on-demand using Imagify.', 'imagify' ),
				'category'            => 'imagify',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'media_id'           => [
							'type'        => 'integer',
							'description' => __( 'The WordPress attachment ID to optimize.', 'imagify' ),
						],
						'optimization_level' => [
							'type'        => 'integer',
							'description' => __( 'Optimization level: 0 (normal), 1 (aggressive), or 2 (ultra). If omitted, uses the global setting.', 'imagify' ),
							'minimum'     => 0,
							'maximum'     => 2,
						],
					],
					'required'   => [ 'media_id' ],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'status'          => [
							'type'        => 'string',
							'description' => __( 'Result status: "success" or "error".', 'imagify' ),
							'enum'        => [ 'success', 'error' ],
						],
						'original_size'   => [
							'type'        => [ 'integer', 'null' ],
							'description' => __( 'Original file size in bytes before optimization, or null on error.', 'imagify' ),
						],
						'optimized_size'  => [
							'type'        => [ 'integer', 'null' ],
							'description' => __( 'Optimized file size in bytes after optimization, or null on error or if not yet available.', 'imagify' ),
						],
						'savings_percent' => [
							'type'        => [ 'number', 'null' ],
							'description' => __( 'Percentage savings, or null on error.', 'imagify' ),
						],
						'error_message'   => [
							'type'        => [ 'string', 'null' ],
							'description' => __( 'Human-readable error message on failure, or null on success.', 'imagify' ),
						],
					],
				],
				'execute_callback'    => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'meta'                => [
					'show_in_rest' => true,
					'mcp'          => [
						'public' => true,
					],
					'annotations'  => [
						'readonly'    => false,
						'destructive' => true,
						'idempotent'  => false,
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
	 * Note: `manual-optimize` is not used here because `check_permissions()` is
	 * called before `execute()` and receives no `media_id`, making the underlying
	 * `edit_post` check ambiguous. The `manage` descriptor is the correct top-level
	 * gate consistent with existing AJAX equivalents.
	 *
	 * @return bool True when the current user has the Imagify `manage` capability.
	 */
	protected function has_permission(): bool {
		return imagify_get_context( 'wp' )->current_user_can( 'manage' );
	}

	/**
	 * Execute the ability: optimize the media.
	 *
	 * Fires `imagify_mcp_ability_executed` after the ability resolves so that
	 * tracking and other subscribers can react to both success and failure outcomes.
	 *
	 * @param array $args Input arguments. Expects `media_id` (int) and optionally `optimization_level` (int).
	 * @return array{status: string, original_size: int|null, optimized_size: int|null, savings_percent: float|null, error_message: string|null}
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
	 * Separated from execute() so that the do_action hook fires for every
	 * outcome (success and all error paths) with a single call site.
	 *
	 * @param array $args Input arguments.
	 * @return array{status: string, original_size: int|null, optimized_size: int|null, savings_percent: float|null, error_message: string|null}
	 */
	private function do_execute( array $args ): array {
		$media_id = isset( $args['media_id'] ) ? (int) $args['media_id'] : 0;

		if ( $media_id <= 0 ) {
			return $this->error_response( 'Invalid or missing media_id.' );
		}

		// Verify the attachment exists.
		$post = get_post( $media_id );
		if ( ! $post ) {
			return $this->error_response( 'Invalid media.' );
		}

		// Verify the post is an attachment.
		if ( 'attachment' !== get_post_type( $post ) ) {
			return $this->error_response( 'The provided ID is not a media attachment.' );
		}

		// Determine optimization level.
		$optimization_level = null;
		if ( isset( $args['optimization_level'] ) ) {
			$optimization_level = (int) $args['optimization_level'];
		}

		// Get the process for this media.
		$process = imagify_get_optimization_process( $media_id, 'wp' );

		if ( ! $process ) {
			return $this->error_response( 'Could not initialize optimization process.' );
		}

		// Capture the original size before optimization.
		$original_size = $this->get_media_original_size( $process );

		// Determine whether to optimize or reoptimize.
		$data = $process->get_data();

		if ( $data->is_optimized() ) {
			// Re-optimize the media.
			$result = $process->reoptimize( $optimization_level );
		} else {
			// First-time optimization.
			$result = $process->optimize( $optimization_level );
		}

		// Handle errors from the process.
		if ( is_wp_error( $result ) ) {
			return $this->error_response( $result->get_error_message() );
		}

		// Capture the optimized size after optimization.
		// Note: The process queues a background job, so optimized_size may be 0 until job completes.
		$optimized_size = $this->get_media_optimized_size( $process );

		// Calculate savings percentage.
		$savings_percent = null;
		if ( $original_size > 0 && null !== $optimized_size ) {
			$savings_percent = (float) round( ( ( $original_size - $optimized_size ) / $original_size ) * 100, 1 );
		}

		return [
			'status'          => 'success',
			'original_size'   => $original_size,
			'optimized_size'  => $optimized_size,
			'savings_percent' => $savings_percent,
			'error_message'   => null,
		];
	}

	/**
	 * Build an error response array.
	 *
	 * @param string $error_message The error message.
	 * @return array{status: string, original_size: null, optimized_size: null, savings_percent: null, error_message: string}
	 */
	private function error_response( string $error_message ): array {
		return [
			'status'          => 'error',
			'original_size'   => null,
			'optimized_size'  => null,
			'savings_percent' => null,
			'error_message'   => $error_message,
		];
	}

	/**
	 * Get the original size of the media before optimization.
	 *
	 * Extracted into a protected method so unit tests can override.
	 *
	 * @param \Imagify\Optimization\Process\ProcessInterface $process The optimization process.
	 * @return int Original file size in bytes, or 0 if unavailable.
	 */
	protected function get_media_original_size( $process ): int {
		$data = $process->get_data();

		if ( ! $data ) {
			return 0;
		}

		// If already optimized, use the original_size from optimization stats.
		if ( $data->is_optimized() ) {
			$optimization_data = $data->get_optimization_data();
			if ( isset( $optimization_data['stats']['original_size'] ) ) {
				return (int) $optimization_data['stats']['original_size'];
			}
		}

		// Otherwise, get the original file size from the media object.
		$media = $process->get_media();

		if ( ! $media ) {
			return 0;
		}

		$path = $media->get_raw_original_path();

		if ( ! $path || ! file_exists( $path ) ) {
			return 0;
		}

		return (int) filesize( $path );
	}

	/**
	 * Get the optimized size of the media after optimization.
	 *
	 * For newly-queued jobs, this may return 0 until the background job completes.
	 * Clients should poll imagify_get_media_status to track final results.
	 *
	 * Extracted into a protected method so unit tests can override.
	 *
	 * @param \Imagify\Optimization\Process\ProcessInterface $process The optimization process.
	 * @return int|null Optimized file size in bytes, or null if unavailable.
	 */
	protected function get_media_optimized_size( $process ): ?int {
		$data = $process->get_data();

		if ( ! $data ) {
			return null;
		}

		$optimization_data = $data->get_optimization_data();

		if ( isset( $optimization_data['stats']['optimized_size'] ) ) {
			return (int) $optimization_data['stats']['optimized_size'];
		}

		return null;
	}
}
