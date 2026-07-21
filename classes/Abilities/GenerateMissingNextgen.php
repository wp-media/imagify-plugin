<?php
declare(strict_types=1);

namespace Imagify\Abilities;

use Imagify\Bulk\Bulk;
use Imagify\Stats\StatInterface;

/**
 * MCP ability: generate missing next-gen (WebP/AVIF) versions.
 *
 * Queues generation of missing next-gen versions for all optimized media
 * by delegating to `Bulk::run_generate_nextgen()`, exactly as the WP-CLI
 * `GenerateMissingNextgenCommand` and the AJAX `missing_nextgen_callback` do.
 *
 * @since 2.3.0
 */
class GenerateMissingNextgen extends AbstractAbility implements CreditConsumingAbilityInterface {

	const ABILITY_ID   = 'imagify/generate-missing-nextgen';
	const ABILITY_NAME = 'Generate missing next-gen versions';

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
	 * Stat service used to compute the missing-nextgen count for the
	 * credit-consumption impact estimate. Typed via docblock for the same
	 * reflection-injection reason as `$bulk` above.
	 *
	 * @var StatInterface
	 */
	private $stat;

	/**
	 * Constructor.
	 *
	 * @param Bulk          $bulk Bulk instance injected by the DI container.
	 * @param StatInterface $stat Stat service (`OptimizedMediaWithoutNextGen`) injected by the DI container.
	 */
	public function __construct( Bulk $bulk, StatInterface $stat ) {
		$this->bulk = $bulk;
		$this->stat = $stat;
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
					'properties' => [
						'confirm' => [
							'type'        => 'boolean',
							'description' => __( 'Set to true to execute after reviewing the credit-consumption preview returned by a prior call without this flag.', 'imagify' ),
							'default'     => false,
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'status'        => [
							'type' => 'string',
							'enum' => [ 'scheduled', 'error', 'confirmation_required', 'insufficient_quota', 'invalid_api_key' ],
						],
						'queued_count'  => [ 'type' => 'integer' ],
						'error_message' => [ 'type' => [ 'string', 'null' ] ],
					],
				],
				'execute_callback'    => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'meta'                => [
					'show_in_rest' => true,
					'mcp'          => [ 'public' => true ],
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
	 * @return bool True if the current user has the `manage_options` capability.
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
	 * Returns the credit-consumption impact estimate for generating missing next-gen versions.
	 *
	 * `count` is the number of optimized media still missing a next-gen version,
	 * computed live via `OptimizedMediaWithoutNextGen::get_stat()` (not the
	 * 2-day cached `get_cached_stat()`) because this preview drives an AI
	 * credit-spend decision and must not go stale relative to `total`. `total`
	 * is the number of optimized media across both contexts, summed the same
	 * way `OptimizedMediaWithoutNextGen::get_stat()` sums its own per-context
	 * loop. `count` is clamped to `total` as a defensive safeguard.
	 *
	 * @param array $args Input arguments (unused: the estimate does not depend on input).
	 * @return array{unit: string, count: int, total: int, label: string}
	 */
	public function get_impact_estimate( array $args ): array {
		$count = (int) $this->stat->get_stat();
		$total = imagify_count_optimized_attachments() + \Imagify_Files_Stats::count_optimized_files();
		$count = min( $count, (int) $total );

		return [
			'unit'  => 'image',
			'count' => $count,
			'total' => (int) $total,
			'label' => __( 'optimized images missing a next-gen version', 'imagify' ),
		];
	}

	/**
	 * Execute the ability: queue generation of missing next-gen versions.
	 *
	 * Wraps the real execution behind `guard_credit_confirmation()` so the AI
	 * never even attempts the call when quota is already exhausted.
	 *
	 * @param array $args Input arguments. Expects optionally `confirm` (bool).
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
	 * Delegates to `Bulk::run_generate_nextgen()` and maps its return value to
	 * the MCP contract shape:
	 * - `success=true`             → `status=scheduled, queued_count=N`
	 * - `success=false, no-images` → `status=scheduled, queued_count=0` (AC #3 no-op)
	 * - `success=false, other`     → `status=error, queued_count=0, error_message=...`
	 *
	 * Separated from execute() so guard_credit_confirmation() can invoke it via
	 * a closure without a `[$this, 'method']` callable-array visibility problem
	 * (this method is private, and the guard lives on AbstractAbility).
	 *
	 * @param array $args Input arguments (unused by the underlying Bulk call).
	 * @return array{status: string, queued_count: int, error_message: string|null}
	 */
	private function do_execute( array $args ): array {
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
