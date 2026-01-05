<?php
/**
 * Bootstraps the Imagify Plugin integration tests
 *
 * @package Imagify\Tests\Integration
 */

namespace Imagify\Tests\Integration;


define( 'IMAGIFY_PLUGIN_ROOT', dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR );

// Manually load the plugin being tested.
tests_add_filter(
	'muplugins_loaded',
	function() {
		// Load the plugin.
		require IMAGIFY_PLUGIN_ROOT . '/imagify.php';
	}
);
