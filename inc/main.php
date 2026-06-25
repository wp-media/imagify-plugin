<?php
use Imagify\Dependencies\League\Container\Container;
use Imagify\Plugin;

defined( 'ABSPATH' ) || exit;

if ( file_exists( IMAGIFY_PATH . 'vendor/autoload.php' ) ) {
	require_once IMAGIFY_PATH . 'vendor/autoload.php';
}

require_once IMAGIFY_PATH . 'inc/Dependencies/ActionScheduler/action-scheduler.php';

/**
 * Plugin init.
 *
 * @since 1.0
 */
function imagify_init() {
	// Nothing to do during autosave.
	if ( defined( 'DOING_AUTOSAVE' ) ) {
		return;
	}

	$providers = require_once IMAGIFY_PATH . 'config/providers.php';

	$plugin = new Plugin(
		new Container(),
		[
			'plugin_path' => IMAGIFY_PATH,
		]
	);

	$plugin->init( $providers );

	// Boot the MCP adapter after providers/subscribers are wired so that
	// ConfigSubscriber and AbilitiesSubscriber are already listening before
	// the adapter fires `mcp_adapter_init` / `wp_abilities_api_*` actions
	// (those fire from `rest_api_init` priority 15, well after `plugins_loaded`).
	$can_boot_mcp_adapter =
		class_exists( \WP\MCP\Core\McpAdapter::class )
		&& function_exists( 'wp_register_ability' )
		&& function_exists( 'wp_get_ability' )
		&& function_exists( 'wp_get_abilities' )
		&& function_exists( 'wp_register_ability_category' );

	if ( $can_boot_mcp_adapter ) {
		\WP\MCP\Core\McpAdapter::instance();
	}
}
add_action( 'plugins_loaded', 'imagify_init' );
