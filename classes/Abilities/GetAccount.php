<?php
declare(strict_types=1);

namespace Imagify\Abilities;

use Imagify\User\User;

/**
 * MCP ability: returns the current Imagify account status and quota data.
 *
 * Registers itself with the WP Abilities API under the slug
 * `imagify/get-account` and returns plan details, quota consumption,
 * and whether the configured API key is valid.
 *
 * @since 2.3.0
 */
class GetAccount extends AbstractAbility {

	/**
	 * Returns the ability slug.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'imagify/get-account';
	}

	/**
	 * Returns the ability label.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Get Imagify account status';
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
			'imagify/get-account',
			[
				'label'               => __( 'Get Imagify account status', 'imagify' ),
				'description'         => __( 'Returns account quota, plan details, and API key validity.', 'imagify' ),
				'category'            => 'imagify',
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'plan_label'                   => [ 'type' => 'string' ],
						'quota'                        => [ 'type' => [ 'string', 'number' ] ],
						'consumed_current_month_quota' => [ 'type' => [ 'string', 'number' ] ],
						'extra_quota'                  => [ 'type' => [ 'string', 'number' ] ],
						'extra_quota_consumed'         => [ 'type' => [ 'string', 'number' ] ],
						'next_date_update'             => [ 'type' => 'string' ],
						'is_api_key_valid'             => [ 'type' => 'boolean' ],
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
	 * Routes through Imagify's capability abstraction so the `imagify_capacity`
	 * filter and multisite network-admin logic are honoured.
	 *
	 * @return bool True when the current user has the Imagify `manage` capability.
	 */
	protected function has_permission(): bool {
		return imagify_get_context( 'wp' )->current_user_can( 'manage' );
	}

	/**
	 * Fetch the initialized User instance.
	 *
	 * Extracted into a protected method so that unit tests can override
	 * this call without needing to bootstrap the full Imagify API layer.
	 *
	 * @return User
	 */
	protected function fetch_user(): User {
		$user = new User();
		$user->init_user();
		return $user;
	}

	/**
	 * Execute the ability: return the current Imagify account status.
	 *
	 * @return array<string, mixed> Account quota, plan details, and API key validity.
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
	 * @return array<string, mixed>
	 */
	private function do_execute(): array {
		$user     = $this->fetch_user();
		$is_valid = ! is_wp_error( $user->get_error() );

		if ( ! $is_valid ) {
			return [
				'plan_label'                   => '',
				'quota'                        => 0,
				'consumed_current_month_quota' => 0,
				'extra_quota'                  => 0,
				'extra_quota_consumed'         => 0,
				'next_date_update'             => '',
				'is_api_key_valid'             => false,
			];
		}

		return [
			'plan_label'                   => $user->plan_label,
			'quota'                        => $user->quota,
			'consumed_current_month_quota' => $user->consumed_current_month_quota,
			'extra_quota'                  => $user->extra_quota,
			'extra_quota_consumed'         => $user->extra_quota_consumed,
			'next_date_update'             => $user->next_date_update,
			'is_api_key_valid'             => true,
		];
	}
}
