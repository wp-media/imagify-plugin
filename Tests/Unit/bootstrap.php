<?php
/**
 * Bootstraps the Imagify Plugin Unit Tests
 *
 * @package Imagify\Tests\Unit
 */

namespace Imagify\Tests\Unit;

define( 'IMAGIFY_PLUGIN_ROOT', dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR );
define( 'IMAGIFY_PLUGIN_TESTS_FIXTURES_DIR', dirname( __DIR__ ) . '/Fixtures' );

require_once IMAGIFY_PLUGIN_ROOT . 'inc/functions/api.php';
include_once IMAGIFY_PLUGIN_TESTS_FIXTURES_DIR . '/WP/class-wp-error.php';
