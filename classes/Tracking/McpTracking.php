<?php
declare(strict_types=1);

namespace Imagify\Tracking;

/**
 * Tracking class for Imagify MCP ability events.
 *
 * Fires the same Mixpanel events as the UI path but with context `wp_plugin_mcp`
 * so Mixpanel can aggregate across both surfaces or filter by source.
 *
 * @since 2.3.0
 */
class McpTracking extends BaseTracking {

	/**
	 * Returns the default event properties shared by every MCP tracked event.
	 *
	 * Overrides context to `wp_plugin_mcp` so Mixpanel can distinguish MCP
	 * executions from UI-initiated ones.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_default_event_properties(): array {
		$defaults            = parent::get_default_event_properties();
		$defaults['context'] = 'wp_plugin_mcp';
		return $defaults;
	}

	/**
	 * Track a "Media Optimized" event triggered via the MCP ability.
	 *
	 * Only fires for the `imagify/optimize-media` ability and only on success.
	 *
	 * @param string $ability_id   Ability slug, e.g. `imagify/optimize-media`.
	 * @param array  $result       Return value of OptimizeMedia::execute().
	 * @param float  $start_time   microtime(true) captured before execute() ran.
	 * @param array  $input_params Raw input params passed to execute().
	 * @return void
	 */
	public function track_media_optimized( string $ability_id, array $result, float $start_time, array $input_params ): void {
		if ( ! $this->can_track() ) {
			return;
		}

		if ( 'imagify/optimize-media' !== $ability_id ) {
			return;
		}

		if ( 'success' !== ( $result['status'] ?? '' ) ) {
			return;
		}

		$media_id          = isset( $input_params['media_id'] ) ? (int) $input_params['media_id'] : 0;
		$execution_time_ms = round( ( microtime( true ) - $start_time ) * 1000, 2 );

		$optimization_level = isset( $input_params['optimization_level'] )
			? (int) $input_params['optimization_level']
			: (int) get_imagify_option( 'optimization_level' );

		$event_data = array_merge(
			$this->get_default_event_properties(),
			[
				'initiated_via'      => 'mcp',
				'optimization_level' => $optimization_level,
				'media_type'         => $media_id > 0 ? (string) get_post_mime_type( $media_id ) : '',
				'original_size'      => null !== $result['original_size'] ? (int) $result['original_size'] : null,
				'optimized_size'     => null !== $result['optimized_size'] ? (int) $result['optimized_size'] : null,
				'savings_percent'    => null !== $result['savings_percent'] ? (float) $result['savings_percent'] : null,
				'next_gen_format'    => $this->resolve_next_gen_format(),
				'execution_time_ms'  => $execution_time_ms,
			]
		);

		$this->mixpanel->track_direct( 'Media Optimized', $event_data );
	}

	/**
	 * Track a generic "MCP Ability Executed" event for any ability invocation.
	 *
	 * Fires for every ability (including optimize-media) so Mixpanel dashboards
	 * can aggregate all MCP calls by ability_id regardless of outcome.
	 *
	 * @param string $ability_id   Ability slug, e.g. `imagify/get-stats`.
	 * @param string $ability_name Human-readable ability label.
	 * @param float  $start_time   microtime(true) captured before execute() ran.
	 * @return void
	 */
	public function track_ability_executed( string $ability_id, string $ability_name, float $start_time ): void {
		if ( ! $this->can_track() ) {
			return;
		}

		$event_data = array_merge(
			$this->get_default_event_properties(),
			[
				'ability_id'        => $ability_id,
				'ability_name'      => $ability_name,
				'execution_time_ms' => round( ( microtime( true ) - $start_time ) * 1000, 2 ),
			]
		);

		$this->mixpanel->track_direct( 'MCP Ability Executed', $event_data );
	}

	/**
	 * Track an "MCP Ability Permission Denied" event.
	 *
	 * @param string $ability_id          Ability slug.
	 * @param string $ability_name        Human-readable ability label.
	 * @param string $required_capability The capability that was missing.
	 * @return void
	 */
	public function track_permission_denied( string $ability_id, string $ability_name, string $required_capability ): void {
		if ( ! $this->can_track() ) {
			return;
		}

		$user      = wp_get_current_user();
		$user_role = ! empty( $user->roles ) ? (string) $user->roles[0] : '';

		$event_data = array_merge(
			$this->get_default_event_properties(),
			[
				'ability_id'          => $ability_id,
				'ability_name'        => $ability_name,
				'required_capability' => $required_capability,
				'user_role'           => $user_role,
			]
		);

		$this->mixpanel->track_direct( 'MCP Ability Permission Denied', $event_data );
	}

	/**
	 * Resolve the next-gen format from the site's conversion settings.
	 *
	 * Returns 'avif' when AVIF conversion is enabled (highest priority), 'webp'
	 * when only WebP is enabled, or null when next-gen conversion is off.
	 *
	 * @return string|null
	 */
	protected function resolve_next_gen_format(): ?string {
		if ( (bool) get_imagify_option( 'convert_to_avif' ) ) {
			return 'avif';
		}

		if ( (bool) get_imagify_option( 'convert_to_webp' ) ) {
			return 'webp';
		}

		return null;
	}
}
