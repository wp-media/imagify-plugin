<?php
/**
 * Bootstraps the Imagify Plugin integration tests
 *
 * @package Imagify\Tests\Integration
 */

namespace Imagify\Tests\Integration;


define( 'IMAGIFY_PLUGIN_ROOT', dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR );

// Opt-in: on a multisite run (WP_MULTISITE=1), IMAGIFY_TESTS_NETWORK_ACTIVE=1 makes Imagify network-activated,
// so WordPress loads it as a network plugin. imagify_is_active_for_network() caches its result while the plugin
// loads, so this must be set before. Most tests assume a site administrator manages Imagify, which no longer holds
// once network-activated: run this mode on the site-aware tests only, e.g. `--filter CheckPermissionsTest`.
if ( getenv( 'IMAGIFY_TESTS_NETWORK_ACTIVE' ) ) {
	tests_add_filter(
		'pre_site_option_active_sitewide_plugins',
		function() {
			return [ 'imagify/imagify.php' => time() ];
		}
	);
}

// Manually load the plugin being tested.
tests_add_filter(
	'muplugins_loaded',
	function() {
		// Load the plugin.
		// Once only: when network-activated, WordPress has already loaded it as a network plugin.
		require_once IMAGIFY_PLUGIN_ROOT . '/imagify.php';
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
