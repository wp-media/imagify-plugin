<?php
declare(strict_types=1);

namespace Imagify\Abilities;

/**
 * Base class for all Imagify MCP abilities.
 *
 * Provides the `check_permissions()` template method (fires the
 * `imagify_mcp_permission_denied` action on denial) and the
 * `fire_executed()` helper used by concrete `execute()` implementations
 * to fire `imagify_mcp_ability_executed` after every invocation.
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
			do_action( 'imagify_mcp_permission_denied', $this->get_id(), $this->get_name(), 'manage' );
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
}
