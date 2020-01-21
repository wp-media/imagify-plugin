<?php
/**
 * Test Case for all of the integration tests.
 *
 * @package Imagify\Tests\Integration
 */

namespace Imagify\Tests\Integration;

use Brain\Monkey;
use Imagify\Tests\TestCaseTrait;
use WP_UnitTestCase;

abstract class TestCase extends WP_UnitTestCase {
	use TestCaseTrait;

	/**
	 * Prepares the test environment before each test.
	 */
	public function setUp() {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Cleans up the test environment after each test.
	 */
	public function tearDown() {
		Monkey\tearDown();
		parent::tearDown();
	}
}
