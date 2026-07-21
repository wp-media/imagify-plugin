<?php
declare(strict_types=1);

namespace Imagify\Abilities;

use Imagify\User\User;

/**
 * Base class for all Imagify MCP abilities.
 *
 * Provides the `check_permissions()` template method (fires the
 * `imagify_mcp_permission_denied` action on denial), the `fire_executed()`
 * helper used by concrete `execute()` implementations to fire
 * `imagify_mcp_ability_executed` after every invocation, and the
 * `guard_credit_confirmation()` template method reused by every
 * credit-consuming ability.
 *
 * @since 2.3.0
 */
abstract class AbstractAbility implements AbilitiesInterface {

	/**
	 * Returns the ability slug used to identify this ability in hooks and tracking.
	 *
	 * @return string
	 */
	abstract public function get_id(): string;

	/**
	 * Returns the human-readable ability label used in hooks and tracking.
	 *
	 * @return string
	 */
	abstract public function get_name(): string;

	/**
	 * Internal permission check delegated by check_permissions().
	 *
	 * @return bool True when the current user may execute the ability.
	 */
	abstract protected function has_permission(): bool;

	/**
	 * Returns the capability name reported to `imagify_mcp_permission_denied`
	 * when `has_permission()` denies access.
	 *
	 * Overridable so abilities whose `has_permission()` checks a capability
	 * other than the Imagify `manage` capability (e.g. `manage_options`)
	 * report the real required capability in tracking/analytics.
	 *
	 * @return string
	 */
	protected function get_required_capability(): string {
		return 'manage';
	}

	/**
	 * Check if the current user has permission to execute this ability.
	 *
	 * Delegates the capability check to has_permission() and fires
	 * `imagify_mcp_permission_denied` when access is denied so that
	 * tracking and logging subscribers can react.
	 *
	 * @return bool True when the current user may execute the ability.
	 */
	public function check_permissions(): bool {
		$allowed = $this->has_permission();

		if ( ! $allowed ) {
			do_action( 'imagify_mcp_permission_denied', $this->get_id(), $this->get_name(), $this->get_required_capability() );
		}

		return $allowed;
	}

	/**
	 * Fire the `imagify_mcp_ability_executed` action after execute() resolves.
	 *
	 * Called by every concrete execute() so that tracking and other subscribers
	 * receive the result for both success and failure outcomes.
	 *
	 * @param mixed $result     Return value of the ability's do_execute().
	 * @param float $start_time microtime(true) captured before do_execute() ran.
	 * @param array $args       Raw input args forwarded from execute().
	 * @return void
	 */
	protected function fire_executed( $result, float $start_time, array $args = [] ): void {
		do_action( 'imagify_mcp_ability_executed', $this->get_id(), $this->get_name(), $result, $start_time, $args );
	}

	/**
	 * Fetch an initialized Imagify User instance.
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
	 * Shared pre-flight guard for credit-consuming abilities.
	 *
	 * Implements a 4-step flow, in this exact order:
	 * 1. If the Imagify API key is invalid, returns an `invalid_api_key`
	 *    response — `$run` is never invoked.
	 * 2. If the account is over quota, returns an `insufficient_quota`
	 *    response — `$run` is never invoked.
	 * 3. If `$args['confirm']` is not strictly `true`, returns a
	 *    `confirmation_required` response built from `get_impact_estimate()` —
	 *    `$run` is never invoked.
	 * 4. Otherwise calls `$run( $args )` and returns its result unchanged.
	 *
	 * Callers MUST pass a closure created inside the defining ability class
	 * (e.g. `function ( array $a ) { return $this->do_execute( $a ); }`),
	 * never a `[ $this, 'method' ]` callable-array: a private target method's
	 * visibility is resolved against the scope that invokes it, which is this
	 * method on `AbstractAbility` — not the ability class where the private
	 * method is declared.
	 *
	 * @param array    $args Raw input arguments passed to execute().
	 * @param callable $run  Closure invoked with `$args` once confirmed and
	 *                       quota/API-key checks pass.
	 * @return array
	 */
	protected function guard_credit_confirmation( array $args, callable $run ): array {
		if ( ! \Imagify_Requirements::is_api_key_valid() ) {
			return $this->invalid_api_key_response();
		}

		if ( \Imagify_Requirements::is_over_quota() ) {
			return $this->insufficient_quota_response();
		}

		if ( true !== ( $args['confirm'] ?? null ) ) {
			return $this->confirmation_required_response( $args );
		}

		return $run( $args );
	}

	/**
	 * Builds the `invalid_api_key` guard response.
	 *
	 * @return array{status: string, message: string}
	 */
	private function invalid_api_key_response(): array {
		return [
			'status'  => 'invalid_api_key',
			'message' => __( 'Your Imagify API key is invalid or missing. Update it in the Imagify settings before retrying.', 'imagify' ),
		];
	}

	/**
	 * Builds the `insufficient_quota` guard response.
	 *
	 * @return array{status: string, message: string, next_date_update: string, upgrade_url: string}
	 */
	private function insufficient_quota_response(): array {
		$user = $this->fetch_user();

		return [
			'status'           => 'insufficient_quota',
			'message'          => __( 'Your Imagify quota is exhausted. Wait for the next reset date or upgrade your plan to continue.', 'imagify' ),
			'next_date_update' => $user->next_date_update ? (string) $user->next_date_update : '',
			'upgrade_url'      => imagify_get_external_url(
				'subscription',
				[
					'utm_source'  => 'plugin',
					'utm_medium'  => 'imagify-wp',
					'utm_content' => 'over-quota',
				]
			),
		];
	}

	/**
	 * Builds the `confirmation_required` guard response.
	 *
	 * The confirmation step is kept for every account, but the messaging adapts
	 * to the plan: quota-limited accounts get the credit-consumption wording plus
	 * a `quota_remaining` figure, while Infinite accounts (whose plans have no
	 * per-image quota to consume) get operation-focused wording and no
	 * `quota_remaining` key.
	 *
	 * @param array $args Raw input arguments passed to execute().
	 * @return array{status: string, message: string, impact: array, quota_remaining?: float, confirm_with: array}
	 */
	private function confirmation_required_response( array $args ): array {
		$impact = $this instanceof CreditConsumingAbilityInterface ? $this->get_impact_estimate( $args ) : [];

		$unit  = isset( $impact['unit'] ) ? (string) $impact['unit'] : 'image';
		$count = isset( $impact['count'] ) ? (int) $impact['count'] : 0;
		$label = isset( $impact['label'] ) ? (string) $impact['label'] : $unit;

		$impact_response = [
			'unit'  => $unit,
			'count' => $count,
		];

		if ( isset( $impact['total'] ) ) {
			$impact_response['total'] = (int) $impact['total'];
		}

		$user        = $this->fetch_user();
		$is_infinite = $user->is_infinite();
		$has_total   = isset( $impact['total'] );

		if ( $is_infinite ) {
			$message = $has_total
				? sprintf(
					/* translators: 1: number of units about to be processed, 2: total number of units, 3: unit label */
					__( 'This action will process %1$d of %2$d %3$s. Add "confirm": true to the same call to proceed.', 'imagify' ),
					$count,
					(int) $impact['total'],
					$label
				)
				: sprintf(
					/* translators: 1: number of units about to be processed, 2: unit label */
					__( 'This action will process %1$d %2$s. Add "confirm": true to the same call to proceed.', 'imagify' ),
					$count,
					$label
				);
		} else {
			$message = $has_total
				? sprintf(
					/* translators: 1: number of units about to be consumed, 2: total number of units, 3: unit label */
					__( 'This action will consume Imagify quota: %1$d of %2$d %3$s. Add "confirm": true to the same call to proceed.', 'imagify' ),
					$count,
					(int) $impact['total'],
					$label
				)
				: sprintf(
					/* translators: 1: number of units about to be consumed, 2: unit label */
					__( 'This action will consume Imagify quota: %1$d %2$s. Add "confirm": true to the same call to proceed.', 'imagify' ),
					$count,
					$label
				);
		}

		$response = [
			'status'       => 'confirmation_required',
			'message'      => $message,
			'impact'       => $impact_response,
			'confirm_with' => [ 'confirm' => true ],
		];

		if ( ! $is_infinite ) {
			$response['quota_remaining'] = (float) $user->get_percent_unconsumed_quota();
		}

		return $response;
	}
}
