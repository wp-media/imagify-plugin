<?php
declare(strict_types=1);

namespace Imagify\Abilities;

/**
 * Contract that every Imagify MCP ability must implement.
 *
 * Each ability is responsible for registering itself with the WP Abilities API,
 * verifying that the current user has sufficient permissions, and executing the
 * ability's business logic when invoked by the MCP layer.
 *
 * @since 2.3.0
 */
interface AbilitiesInterface {

	/**
	 * Register the ability with the WP Abilities API.
	 *
	 * Implementations must call `wp_register_ability()` (guarded by
	 * `function_exists()`) and wire `execute_callback` and `permission_callback`
	 * to the concrete class's own methods.
	 *
	 * @return void
	 */
	public function register(): void;

	/**
	 * Check if the current user has permission to execute this ability.
	 *
	 * @return bool True if the current user may execute the ability.
	 */
	public function check_permissions(): bool;

	/**
	 * Execute the ability and return the tool result.
	 *
	 * The return type is intentionally untyped because abilities may return a
	 * tool-result array, a resource string, or any other MCP-compatible value.
	 *
	 * @return mixed
	 */
	public function execute();
}
