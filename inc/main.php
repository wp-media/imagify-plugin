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
		array(
			'plugin_path' => IMAGIFY_PATH,
		)
	);

	$plugin->init( $providers );
}
add_action( 'plugins_loaded', 'imagify_init' );
