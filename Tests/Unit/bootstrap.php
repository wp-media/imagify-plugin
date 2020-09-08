<?php
/**
 * Bootstraps the Imagify Plugin Unit Tests
 *
 * @package Imagify\Tests\Unit
 */

namespace Imagify\Tests\Unit;

use function Imagify\Tests\init_test_suite;

require_once dirname( dirname( __FILE__ ) ) . '/bootstrap-functions.php';
init_test_suite( 'Unit' );

/**
 * The original files need to loaded into memory before we mock them with Patchwork. Add files here before the unit
 * tests start.
 *
 * @since 3.5
 */
function load_original_files_before_mocking() {
	$originals = [
		'inc/functions/constants.php',
		'inc/functions/common.php'
	];
	foreach ( $originals as $file ) {
		require_once IMAGIFY_PLUGIN_ROOT . $file;
	}
}

load_original_files_before_mocking();

define( 'FS_CHMOD_DIR' , 0777 );
define( 'FS_CHMOD_FILE', 0777 );
