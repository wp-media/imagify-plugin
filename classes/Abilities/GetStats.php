<?php
declare(strict_types=1);

namespace Imagify\Abilities;

/**
 * MCP ability: returns optimization statistics for WP media and custom folders.
 *
 * @since 2.3.0
 */
class GetStats extends AbstractAbility {

	/**
	 * Returns the ability slug.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'imagify/get-stats';
	}

	/**
	 * Returns the ability label.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Get Imagify optimization stats';
	}

	/**
	 * Register the ability with the WP Abilities API.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'imagify/get-stats',
			[
				'label'               => __( 'Get Imagify optimization stats', 'imagify' ),
				'description'         => __( 'Returns optimization statistics for WP media and custom folders.', 'imagify' ),
				'category'            => 'imagify',
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'wp'             => [
							'type'       => 'object',
							'properties' => [
								'count_optimized' => [ 'type' => 'integer' ],
								'count_errors'    => [ 'type' => 'integer' ],
								'original_size'   => [ 'type' => 'integer' ],
								'optimized_size'  => [ 'type' => 'integer' ],
								'savings_percent' => [ 'type' => 'number' ],
							],
						],
						'custom-folders' => [
							'type'       => 'object',
							'properties' => [
								'count_optimized' => [ 'type' => 'integer' ],
								'count_errors'    => [ 'type' => 'integer' ],
								'original_size'   => [ 'type' => 'integer' ],
								'optimized_size'  => [ 'type' => 'integer' ],
								'savings_percent' => [ 'type' => 'number' ],
							],
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
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
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
	 * Execute the ability and return optimization statistics.
	 *
	 * @return array{
	 *     wp: array{count_optimized: int, count_errors: int, original_size: int, optimized_size: int, savings_percent: float},
	 *     custom-folders: array{count_optimized: int, count_errors: int, original_size: int, optimized_size: int, savings_percent: float}
	 * }
	 */
	public function execute(): array {
		$start_time = microtime( true );
		$result     = $this->do_execute();

		$this->fire_executed( $result, $start_time );

		return $result;
	}

	/**
	 * Internal execution logic for the ability.
	 *
	 * @return array{
	 *     wp: array{count_optimized: int, count_errors: int, original_size: int, optimized_size: int, savings_percent: float},
	 *     custom-folders: array{count_optimized: int, count_errors: int, original_size: int, optimized_size: int, savings_percent: float}
	 * }
	 */
	private function do_execute(): array {
		$cf_original_size   = (int) \Imagify_Files_Stats::get_original_size();
		$cf_optimized_size  = (int) \Imagify_Files_Stats::get_optimized_size();
		$cf_savings_percent = $cf_original_size > 0
			? round( ( ( $cf_original_size - $cf_optimized_size ) / $cf_original_size ) * 100, 1 )
			: 0.0;

		return [
			'wp'             => [
				'count_optimized' => (int) imagify_count_optimized_attachments(),
				'count_errors'    => (int) imagify_count_error_attachments(),
				'original_size'   => (int) imagify_count_saving_data( 'original_size' ),
				'optimized_size'  => (int) imagify_count_saving_data( 'optimized_size' ),
				'savings_percent' => (float) imagify_count_saving_data( 'percent' ),
			],
			'custom-folders' => [
				'count_optimized' => (int) \Imagify_Files_Stats::count_optimized_files(),
				'count_errors'    => (int) \Imagify_Files_Stats::count_error_files(),
				'original_size'   => $cf_original_size,
				'optimized_size'  => $cf_optimized_size,
				'savings_percent' => (float) $cf_savings_percent,
			],
		];
	}
}
