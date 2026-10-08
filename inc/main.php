<?php
use Imagify\Dependencies\League\Container\Container;
use Imagify\Plugin;

defined( 'ABSPATH' ) || exit;

if ( file_exists( IMAGIFY_PATH . 'vendor/autoload.php' ) ) {
	require_once IMAGIFY_PATH . 'vendor/autoload.php';
}

/**
 * When Imagify is installed as a Composer dependency, Strauss never runs and the
 * prefixed dependency classes do not exist. Alias them to their unprefixed
 * originals on demand. Registered after Composer's autoloader, so a normal
 * install never reaches it.
 */
require_once IMAGIFY_PATH . 'inc/functions/dependencies.php';

imagify_register_dependencies_fallback_autoloader();

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

	// The MCP Adapter is no longer bundled: boot MCP OAuth only when the standalone plugin is loaded.
	if (
		class_exists( 'WP\MCP\Core\McpAdapter' )
		&& class_exists( \WPMedia\MCP\OAuth\Bootstrap::class )
		&& function_exists( 'wp_register_ability' )
		&& function_exists( 'wp_get_ability' )
		&& function_exists( 'wp_get_abilities' )
		&& function_exists( 'wp_register_ability_category' )
	) {
		\WPMedia\MCP\OAuth\Bootstrap::instance();
	}
}
add_action( 'plugins_loaded', 'imagify_init' );
