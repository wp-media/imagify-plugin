<?php
declare(strict_types=1);

namespace Imagify\Abilities;

use Imagify\Bulk\BulkOptimizerInterface;

/**
 * MCP ability: schedule a bulk image optimization run.
 *
 * Registers itself with the WP Abilities API under the slug
 * `imagify/bulk-optimize` and delegates to `Imagify\Bulk\Bulk::run_optimize()`.
 *
 * @since 2.3.0
 */
class BulkOptimize implements AbilitiesInterface {

	/**
	 * Bulk optimization instance.
	 *
	 * @var BulkOptimizerInterface
	 */
	private $bulk;

	/**
	 * Constructor.
	 *
	 * @param BulkOptimizerInterface $bulk The bulk optimization instance.
	 */
	public function __construct( BulkOptimizerInterface $bulk ) {
		$this->bulk = $bulk;
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
			'imagify/bulk-optimize',
			[
				'label'               => __( 'Bulk optimize', 'imagify' ),
				'description'         => __( 'Schedule a bulk image optimization run for the WordPress media library or custom folders.', 'imagify' ),
				'category'            => 'imagify',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'context'            => [
							'type'        => 'string',
							'description' => __( 'Optimization context: "wp" for the WordPress media library or "custom-folders" for custom folder sources.', 'imagify' ),
							'enum'        => [ 'wp', 'custom-folders' ],
						],
						'optimization_level' => [
							'type'        => 'integer',
							'description' => __( 'Optimization level: 0 (normal), 1 (aggressive), or 2 (ultra). Defaults to the global Imagify setting.', 'imagify' ),
							'minimum'     => 0,
							'maximum'     => 2,
						],
					],
					'required'   => [ 'context' ],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'status'        => [
							'type'        => 'string',
							'description' => __( 'Result status: "scheduled" when the bulk run was queued, "error" otherwise.', 'imagify' ),
							'enum'        => [ 'scheduled', 'error' ],
						],
						'context'       => [
							'type'        => 'string',
							'description' => __( 'The requested optimization context, echoed back.', 'imagify' ),
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
						'destructive' => false,
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
	 * Execute the ability: schedule a bulk optimization run.
	 *
	 * @param array $args Input arguments. Expects `context` (string) and optionally `optimization_level` (int).
	 * @return array{status: string, context: string, error_message: string|null}
	 */
	public function execute( array $args = [] ): array {
		$context = isset( $args['context'] ) ? (string) $args['context'] : '';

		if ( ! in_array( $context, [ 'wp', 'custom-folders' ], true ) ) {
			return [
				'status'        => 'error',
				'context'       => $context,
				'error_message' => 'Invalid context. Allowed values: wp, custom-folders.',
			];
		}

		$optimization_level = isset( $args['optimization_level'] )
			? max( 0, min( 2, (int) $args['optimization_level'] ) )
			: (int) get_imagify_option( 'optimization_level' );

		$result = $this->bulk->run_optimize( $context, $optimization_level );

		if ( $result['success'] ) {
			return [
				'status'        => 'scheduled',
				'context'       => $context,
				'error_message' => null,
			];
		}

		return [
			'status'        => 'error',
			'context'       => $context,
			'error_message' => $result['message'],
		];
	}
}
