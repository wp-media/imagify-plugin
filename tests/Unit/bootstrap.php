<?php
/**
 * Bootstraps the WP Rocket Plugin Unit Tests
 *
 * @package Imagify\Tests\Unit
 */

namespace Imagify\Tests\Unit;

use function Imagify\Tests\init_test_suite;

require_once dirname( dirname( __FILE__ ) ) . '/boostrap-functions.php';
init_test_suite( 'Unit' );
