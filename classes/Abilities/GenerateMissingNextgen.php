<?php
declare(strict_types=1);

namespace Imagify\Abilities;

use Imagify\Bulk\Bulk;

/**
 * MCP ability: generate missing next-gen (WebP/AVIF) versions.
 *
 * Queues generation of missing next-gen versions for all optimized media
 * by delegating to `Bulk::run_generate_nextgen()`, exactly as the WP-CLI
 * `GenerateMissingNextgenCommand` and the AJAX `missing_nextgen_callback` do.
 *
 * @since 2.3.0
 */
class GenerateMissingNextgen implements AbilitiesInterface {

	/**
	 * Bulk instance for queueing next-gen generation jobs.
	 *
	 * Typed via docblock (not property declaration) so that test doubles can be
	 * injected via reflection without triggering PHP 8+ typed-property enforcement.
	 *
	 * @var Bulk
	 */
	private $bulk;

	/**
	 * Constructor.
	 *
	 * @param Bulk $bulk Bulk instance injected by the DI container.
	 */
	public function __construct( Bulk $bulk ) {
		$this->bulk = $bulk;
	}

	/**
	 * Register the ability with the WP Abilities API.
	 *
	 * No-ops gracefully when the WP Abilities API is not available (WP < 6.9).
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'imagify/generate-missing-nextgen',
			[
				'label'               => __( 'Generate missing next-gen versions', 'imagify' ),
				'description'         => __( 'Queues generation of missing next-gen (WebP/AVIF) versions for all optimized media. Runs asynchronously via Action Scheduler.', 'imagify' ),
				'category'            => 'imagify',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'status'        => [
							'type' => 'string',
							'enum' => [ 'scheduled', 'error' ],
						],
						'queued_count'  => [ 'type' => 'integer' ],
						'error_message' => [ 'type' => [ 'string', 'null' ] ],
					],
				],
				'execute_callback'    => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => false,
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
	 * @return bool True if the current user has the `manage_options` capability.
	 */
	public function check_permissions(): bool {
		return (bool) current_user_can( 'manage_options' );
	}

	/**
	 * Execute the ability: queue generation of missing next-gen versions.
	 *
	 * Delegates to `Bulk::run_generate_nextgen()` and maps its return value to
	 * the MCP contract shape:
	 * - `success=true`             → `status=scheduled, queued_count=N`
	 * - `success=false, no-images` → `status=scheduled, queued_count=0` (AC #3 no-op)
	 * - `success=false, other`     → `status=error, queued_count=0, error_message=...`
	 *
	 * Signature matches `AbilitiesInterface::execute()` — no params, untyped return.
	 *
	 * @return mixed
	 */
	public function execute() {
		$contexts = $this->bulk->get_contexts();
		$formats  = imagify_nextgen_images_formats();
		$result   = $this->bulk->run_generate_nextgen( $contexts, $formats );

		if ( true === $result['success'] ) {
			return [
				'status'        => 'scheduled',
				'queued_count'  => (int) $result['message'],
				'error_message' => null,
			];
		}

		if ( 'no-images' === $result['message'] ) {
			// Nothing to generate — not an error (AC #3).
			return [
				'status'        => 'scheduled',
				'queued_count'  => 0,
				'error_message' => null,
			];
		}

		return $this->error_response( (string) $result['message'] );
	}

	/**
	 * Build an error response array from a Bulk error message string.
	 *
	 * @param string $message The raw message returned by `Bulk::run_generate_nextgen()`.
	 *
	 * @return array{status: string, queued_count: int, error_message: string}
	 */
	private function error_response( string $message ): array {
		if ( 'over-quota' === $message ) {
			$readable = __( 'Imagify account is over quota or the API key is invalid.', 'imagify' );
		} elseif ( 'no-backup' === $message ) {
			$readable = __( 'No backup available; next-gen versions cannot be generated.', 'imagify' );
		} else {
			$readable = $message;
		}

		return [
			'status'        => 'error',
			'queued_count'  => 0,
			'error_message' => $readable,
		];
	}
}
