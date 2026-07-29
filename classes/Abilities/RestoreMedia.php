<?php
declare(strict_types=1);

namespace Imagify\Abilities;

/**
 * MCP ability: restore a media to its original state from backup.
 *
 * Registers itself with the WP Abilities API under the slug
 * `imagify/restore-media` and delegates to the existing
 * `Imagify\Optimization\Process\AbstractProcess::restore()` method.
 *
 * @since 2.3.0
 */
class RestoreMedia implements AbilitiesInterface {

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
			'imagify/restore-media',
			[
				'label'               => __( 'Restore media', 'imagify' ),
				'description'         => __( 'Restores an optimized media to its original state using the stored backup file.', 'imagify' ),
				'category'            => 'imagify',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'media_id' => [
							'type'        => 'integer',
							'description' => __( 'The WordPress attachment ID to restore.', 'imagify' ),
						],
					],
					'required'   => [ 'media_id' ],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'status'        => [
							'type'        => 'string',
							'description' => __( 'Result status: "success" or "error".', 'imagify' ),
							'enum'        => [ 'success', 'error' ],
						],
						'restored_size' => [
							'type'        => [ 'integer', 'null' ],
							'description' => __( 'Restored original file size in bytes, or null on error.', 'imagify' ),
						],
						'error_message' => [
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
	 * @return bool True when the current user has the `manage_options` capability.
	 */
	public function check_permissions(): bool {
		return (bool) current_user_can( 'manage_options' );
	}

	/**
	 * Execute the ability: restore the media to its original state.
	 *
	 * @param array $args Input arguments. Expects `media_id` (int) — the WordPress attachment ID.
	 * @return array{status: string, restored_size: int|null, error_message: string|null}
	 */
	public function execute( array $args = [] ): array {
		$media_id = isset( $args['media_id'] ) ? (int) $args['media_id'] : 0;

		if ( $media_id <= 0 ) {
			return [
				'status'        => 'error',
				'restored_size' => null,
				'error_message' => 'Invalid or missing media_id.',
			];
		}

		$process = $this->get_process( $media_id );

		if ( null === $process ) {
			return [
				'status'        => 'error',
				'restored_size' => null,
				'error_message' => 'Invalid media.',
			];
		}

		if ( ! $process->get_data()->is_optimized() ) {
			return [
				'status'        => 'error',
				'restored_size' => null,
				'error_message' => 'This media is not optimized and cannot be restored.',
			];
		}

		$result = $process->restore();

		if ( is_wp_error( $result ) ) {
			return [
				'status'        => 'error',
				'restored_size' => null,
				'error_message' => $result->get_error_message(),
			];
		}

		$restored_size = $this->get_restored_size( $process );

		return [
			'status'        => 'success',
			'restored_size' => $restored_size,
			'error_message' => null,
		];
	}

	/**
	 * Resolve the optimization process for the given media ID.
	 *
	 * Tries the `wp` context first, then falls back to `custom-folders`.
	 * Returns null when neither context recognises the media.
	 *
	 * Extracted into a protected method so unit tests can inject a mock process.
	 *
	 * @param int $media_id The attachment ID.
	 * @return \Imagify\Optimization\Process\ProcessInterface|null
	 */
	protected function get_process( int $media_id ) {
		if ( get_post_type( $media_id ) === 'attachment' ) {
			return imagify_get_optimization_process( $media_id, 'wp' );
		}

		$process = imagify_get_optimization_process( $media_id, 'custom-folders' );

		if ( $process->is_valid() ) {
			return $process;
		}

		return null;
	}

	/**
	 * Get the restored original file size in bytes.
	 *
	 * Extracted into a protected method so unit tests can override.
	 *
	 * @param \Imagify\Optimization\Process\ProcessInterface $process The restore process.
	 * @return int File size in bytes, or 0 if unavailable.
	 */
	protected function get_restored_size( $process ): int {
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
}
