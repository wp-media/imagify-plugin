<?php
declare(strict_types=1);

namespace Imagify\MCP;

use Imagify\EventManagement\SubscriberInterface;

/**
 * Customizes the default MCP server configuration for Imagify.
 *
 * Subscribes to the `mcp_adapter_default_server_config` filter and sets
 * the server name and description. All other keys (`server_id`, `route`,
 * `tools`) are left untouched so the adapter keeps its canonical defaults.
 *
 * @since 2.3.0
 */
class ConfigSubscriber implements SubscriberInterface {

	/**
	 * Returns the events this subscriber listens to.
	 *
	 * @return array<string, string>
	 */
	public static function get_subscribed_events(): array {
		return [
			// @filter mcp_adapter_default_server_config
			'mcp_adapter_default_server_config' => 'customize_mcp_server',
		];
	}

	/**
	 * Customizes the MCP server name and description for Imagify.
	 *
	 * Only `server_name` and `server_description` are overridden.  All other
	 * keys — including `server_id`, `server_route`, `server_route_namespace`,
	 * and `tools` — are preserved so the canonical REST endpoint path and the
	 * adapter's built-in tool set remain intact.
	 *
	 * @param array $config Default server configuration supplied by the adapter.
	 * @return array Modified configuration with Imagify name/description.
	 */
	public function customize_mcp_server( array $config ): array {
		$config['server_name'] = 'imagify-plugin';

		$config['server_description'] = __(
			'Image optimization tools for WordPress powered by Imagify.',
			'imagify'
		);

		return $config;
	}
}
