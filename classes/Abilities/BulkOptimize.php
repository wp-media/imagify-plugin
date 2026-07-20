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
class BulkOptimize extends AbstractAbility implements CreditConsumingAbilityInterface {

	const ABILITY_ID   = 'imagify/bulk-optimize';
	const ABILITY_NAME = 'Bulk optimize';

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
						'confirm'            => [
							'type'        => 'boolean',
							'description' => __( 'Set to true to execute after reviewing the credit-consumption preview returned by a prior call without this flag.', 'imagify' ),
							'default'     => false,
						],
					],
					'required'   => [ 'context' ],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'status'        => [
							'type'        => 'string',
							'description' => __( 'Result status: "scheduled", "error", "confirmation_required", "insufficient_quota", or "invalid_api_key".', 'imagify' ),
							'enum'        => [ 'scheduled', 'error', 'confirmation_required', 'insufficient_quota', 'invalid_api_key' ],
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
	protected function has_permission(): bool {
		return (bool) current_user_can( 'manage_options' );
	}

	/**
	 * Returns the capability required to execute this ability.
	 *
	 * @return string
	 */
	protected function get_required_capability(): string {
		return 'manage_options';
	}

	/**
	 * Returns the credit-consumption impact estimate for a bulk optimization run.
	 *
	 * Uses cheap COUNT-only helpers per context — never the heavy ID-fetching
	 * `get_unoptimized_media_ids()`, which would run a full JOIN query just to
	 * build a preview count.
	 *
	 * @param array $args Input arguments. Expects `context` (string).
	 * @return array{unit: string, count: int, total: int, label: string}
	 */
	public function get_impact_estimate( array $args ): array {
		$context = isset( $args['context'] ) ? (string) $args['context'] : '';
		$context = ( 'custom-folders' === $context ) ? 'custom-folders' : 'wp';

		if ( 'custom-folders' === $context ) {
			$remaining = \Imagify_Files_Stats::count_unoptimized_files();
			$total     = \Imagify_Files_Stats::count_files();
			$label     = __( 'unoptimized images in custom folders', 'imagify' );
		} else {
			$remaining = imagify_count_unoptimized_attachments();
			$total     = imagify_count_attachments();
			$label     = __( 'unoptimized images in the WordPress media library', 'imagify' );
		}

		return [
			'unit'  => 'image',
			'count' => (int) $remaining,
			'total' => (int) $total,
			'label' => $label,
		];
	}

	/**
	 * Execute the ability: schedule a bulk optimization run.
	 *
	 * Wraps the real execution behind `guard_credit_confirmation()`.
	 *
	 * @param array $args Input arguments. Expects `context` (string) and optionally `optimization_level` (int), `confirm` (bool).
	 * @return array<string, mixed> Guard response (invalid_api_key/insufficient_quota/confirmation_required) or the do_execute() result shape.
	 */
	public function execute( array $args = [] ): array {
		$start_time = microtime( true );
		$result     = $this->guard_credit_confirmation(
			$args,
			function ( array $a ) {
				return $this->do_execute( $a );
			}
		);

		$this->fire_executed( $result, $start_time, $args );

		return $result;
	}

	/**
	 * Internal execution logic for the ability.
	 *
	 * Separated from execute() so guard_credit_confirmation() can invoke it
	 * via a closure without a `[$this, 'method']` callable-array visibility
	 * problem (this method is private, and the guard lives on AbstractAbility).
	 *
	 * @param array $args Input arguments. Expects `context` (string) and optionally `optimization_level` (int).
	 * @return array{status: string, context: string, error_message: string|null}
	 */
	private function do_execute( array $args ): array {
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
