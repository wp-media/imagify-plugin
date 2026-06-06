<?php
use Imagify\Plugin;
use Imagify\Dependencies\League\Container\Container;

defined( 'ABSPATH' ) || exit;

if ( file_exists( IMAGIFY_PATH . 'vendor/autoload.php' ) ) {
	require_once IMAGIFY_PATH . 'vendor/autoload.php';
}

// Support Composer dependency install where Strauss prefixing hasn't run.
// Prefixed classes exist when installed as root package; unprefixed when installed as dependency.
if ( ! class_exists( 'Imagify\Dependencies\League\Container\Container', false ) ) {
	if ( class_exists( 'League\Container\Container', false ) ) {
		class_alias( 'League\Container\Container', 'Imagify\Dependencies\League\Container\Container' );
	}
}
if ( ! interface_exists( 'Imagify\Dependencies\League\Container\ServiceProvider\ServiceProviderInterface', false ) ) {
	if ( interface_exists( 'League\Container\ServiceProvider\ServiceProviderInterface', false ) ) {
		class_alias( 'League\Container\ServiceProvider\ServiceProviderInterface', 'Imagify\Dependencies\League\Container\ServiceProvider\ServiceProviderInterface' );
	}
}
if ( ! class_exists( 'Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider', false ) ) {
	if ( class_exists( 'League\Container\ServiceProvider\AbstractServiceProvider', false ) ) {
		class_alias( 'League\Container\ServiceProvider\AbstractServiceProvider', 'Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider' );
	}
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
}
add_action( 'plugins_loaded', 'imagify_init' );
