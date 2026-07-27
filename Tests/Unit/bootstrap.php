<?php
/**
 * Bootstraps the Imagify Plugin Unit Tests
 *
 * @package Imagify\Tests\Unit
 */

namespace Imagify\Tests\Unit;

define( 'IMAGIFY_PLUGIN_ROOT', dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR );
define( 'IMAGIFY_PLUGIN_TESTS_FIXTURES_DIR', dirname( __DIR__ ) . '/Fixtures' );
define( 'IMAGIFY_SLUG', 'imagify' );

/**
 * The original files need to loaded into memory before we mock them with Patchwork. Add files here before the unit
 * tests start.
 *
 * @since 3.5
 */
function load_original_files_before_mocking() {
	$originals = [
		'inc/functions/admin-ui.php',
		'inc/functions/api.php',
		'inc/functions/attachments.php',
		'inc/functions/common.php',
		'inc/3rd-party/nextgen-gallery/inc/functions/common.php',
	];
	foreach ( $originals as $file ) {
		require_once IMAGIFY_PLUGIN_ROOT . $file;
	}

	$fixtures = [
		'/WP/class-wp-error.php',
		'/WP/class-wp-cli.php',
		'/inc/functions/nextgen-images-formats.php',
	];
	foreach ( $fixtures as $file ) {
		require_once IMAGIFY_PLUGIN_TESTS_FIXTURES_DIR . $file;
	}
}

load_original_files_before_mocking();

/**
 * Prepend the worktree's own classes/ and Tests/ directories to the autoloader
 * so that classes modified in this worktree take precedence over the shared
 * vendor autoload map (which resolves to the main plugin directory).
 */
spl_autoload_register(
	function ( $class ) {
		$root = IMAGIFY_PLUGIN_ROOT;

		// Map Imagify\Abilities\* → classes/Abilities/*.php etc.
		$classes_prefix = 'Imagify\\';
		// Exclude Imagify\Tests\* — handled below, and Imagify\Dependencies\* — stay in vendor.
		if (
			strncmp( $classes_prefix, $class, strlen( $classes_prefix ) ) === 0
			&& strncmp( 'Imagify\\Tests\\', $class, strlen( 'Imagify\\Tests\\' ) ) !== 0
			&& strncmp( 'Imagify\\Dependencies\\', $class, strlen( 'Imagify\\Dependencies\\' ) ) !== 0
		) {
			$relative = substr( $class, strlen( $classes_prefix ) );
			$path     = $root . 'classes' . DIRECTORY_SEPARATOR . str_replace( '\\', DIRECTORY_SEPARATOR, $relative ) . '.php';
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}

		// Map Imagify\Tests\* → Tests/*.php.
		$tests_prefix = 'Imagify\\Tests\\';
		if ( strncmp( $tests_prefix, $class, strlen( $tests_prefix ) ) === 0 ) {
			$relative = substr( $class, strlen( $tests_prefix ) );
			$path     = $root . 'Tests' . DIRECTORY_SEPARATOR . str_replace( '\\', DIRECTORY_SEPARATOR, $relative ) . '.php';
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	},
	true, // throw on error
	true  // prepend — run before composer's autoloader
);
