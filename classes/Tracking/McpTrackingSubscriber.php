<?php
declare(strict_types=1);

namespace Imagify\Tracking;

use Imagify\EventManagement\SubscriberInterface;

/**
 * WordPress hook listener that routes MCP ability lifecycle events to McpTracking.
 *
 * @since 2.3.0
 */
class McpTrackingSubscriber implements SubscriberInterface {

	/**
	 * The MCP tracking service.
	 *
	 * @var McpTracking
	 */
	private $mcp_tracking;

	/**
	 * Constructor.
	 *
	 * @param McpTracking $mcp_tracking The MCP tracking service.
	 */
	public function __construct( McpTracking $mcp_tracking ) {
		$this->mcp_tracking = $mcp_tracking;
	}

	/**
	 * Returns the list of events this subscriber wants to listen to.
	 *
	 * @return array<string, array<int, int|string>>
	 */
	public static function get_subscribed_events(): array {
		return [
			// @action imagify_mcp_ability_executed
			'imagify_mcp_ability_executed'  => [ 'on_ability_executed', 10, 5 ],
			// @action imagify_mcp_permission_denied
			'imagify_mcp_permission_denied' => [ 'on_permission_denied', 10, 3 ],
		];
	}

	/**
	 * Fired after an MCP ability's execute() method returns (success or failure).
	 *
	 * Calls both the generic ability-executed tracker (all abilities) and the
	 * media-optimized tracker (optimize-media success only).
	 *
	 * @param string $ability_id   Ability slug, e.g. `imagify/optimize-media`.
	 * @param string $ability_name Human-readable ability label.
	 * @param mixed  $result       Return value of the ability's execute() method.
	 * @param float  $start_time   microtime(true) captured before execute() ran.
	 * @param array  $input_params Raw input params passed to execute().
	 * @return void
	 */
	public function on_ability_executed( string $ability_id, string $ability_name, $result, float $start_time, array $input_params ): void {
		$this->mcp_tracking->track_ability_executed( $ability_id, $ability_name, $start_time );

		if ( 'imagify/optimize-media' === $ability_id && is_array( $result ) ) {
			$this->mcp_tracking->track_media_optimized( $ability_id, $result, $start_time, $input_params );
		}
	}

	/**
	 * Fired when an MCP ability's check_permissions() returns false.
	 *
	 * @param string $ability_id          Ability slug.
	 * @param string $ability_name        Human-readable ability label.
	 * @param string $required_capability The capability that was missing.
	 * @return void
	 */
	public function on_permission_denied( string $ability_id, string $ability_name, string $required_capability ): void {
		$this->mcp_tracking->track_permission_denied( $ability_id, $ability_name, $required_capability );
	}
}
