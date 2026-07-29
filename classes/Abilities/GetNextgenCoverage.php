<?php
declare(strict_types=1);

namespace Imagify\Abilities;

use Imagify\Stats\StatInterface;

/**
 * MCP ability that reports next-gen coverage for optimized media.
 *
 * Returns the number of optimized media missing a next-gen version and the
 * current next-gen format configured in Imagify settings.
 *
 * @since 2.3.0
 */
class GetNextgenCoverage extends AbstractAbility {

	/**
	 * Returns the ability slug.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'imagify/get-nextgen-coverage';
	}

	/**
	 * Returns the ability label.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Get next-gen coverage';
	}

	/**
	 * Stat service that counts optimized media without next-gen versions.
	 *
	 * @var StatInterface
	 */
	private $stat;

	/**
	 * Constructor.
	 *
	 * @param StatInterface $stat Stat service injected via DI.
	 */
	public function __construct( StatInterface $stat ) {
		$this->stat = $stat;
	}

	/**
	 * Registers the ability with the WP Abilities API.
	 *
	 * No-ops when `wp_register_ability` is not available (WP < 6.9).
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
			'imagify/get-nextgen-coverage',
			[
				'label'               => __( 'Get next-gen coverage', 'imagify' ),
				'description'         => __( 'Returns the number of optimized images missing a next-gen version and the configured next-gen format.', 'imagify' ),
				'category'            => 'imagify',
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'missing_nextgen_count' => [ 'type' => 'integer' ],
						'nextgen_format'        => [ 'type' => 'string' ],
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
	 * Executes the ability and returns next-gen coverage data.
	 *
	 * @since 2.3.0
	 *
	 * @return array{missing_nextgen_count: int, nextgen_format: string}
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
	 * @return array{missing_nextgen_count: int, nextgen_format: string}
	 */
	private function do_execute(): array {
		return [
			'missing_nextgen_count' => (int) $this->stat->get_cached_stat(),
			'nextgen_format'        => get_imagify_option( 'optimization_format' ),
		];
	}
}
