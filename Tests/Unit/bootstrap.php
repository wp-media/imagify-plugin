<?php
/**
 * Bootstraps the Imagify Plugin Unit Tests
 *
 * @package Imagify\Tests\Unit
 */

namespace Imagify\Tests\Unit;

define( 'IMAGIFY_PLUGIN_ROOT', dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR );
define( 'IMAGIFY_PLUGIN_TESTS_FIXTURES_DIR', dirname( __DIR__ ) . '/Fixtures' );

/**
 * The original files need to loaded into memory before we mock them with Patchwork. Add files here before the unit
 * tests start.
 *
 * @since 3.5
 */
function load_original_files_before_mocking() {
	$originals = [
		'inc/functions/api.php',
		'inc/functions/attachments.php',
		'inc/functions/common.php',
	];
	foreach ( $originals as $file ) {
		require_once IMAGIFY_PLUGIN_ROOT . $file;
	}

	$fixtures = [
		'/WP/class-wp-error.php',
	];
	foreach ( $fixtures as $file ) {
		require_once IMAGIFY_PLUGIN_TESTS_FIXTURES_DIR . $file;
	}
}

load_original_files_before_mocking();
