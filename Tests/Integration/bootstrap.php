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

// The plugin creates its custom tables on `admin_init`, which never fires during
// integration tests. Create them once at bootstrap so features that query
// `imagify_files` / `imagify_folders` (e.g. stats-backed abilities) run against a
// real, empty table instead of emitting "table doesn't exist" errors.
tests_add_filter(
	'wp_loaded',
	function() {
		if ( class_exists( '\Imagify_Folders_DB' ) ) {
			\Imagify_Folders_DB::get_instance()->create_table();
		}

		if ( class_exists( '\Imagify_Files_DB' ) ) {
			\Imagify_Files_DB::get_instance()->create_table();
		}
	}
);
