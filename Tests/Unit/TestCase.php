<?php
/**
 * Test Case for all of the unit tests.
 *
 * @package Imagify\Tests\Unit
 */

namespace Imagify\Tests\Unit;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Brain\Monkey;
use Imagify\Tests\TestCaseTrait;
use ReflectionObject;
use WP_Error;

abstract class TestCase extends PHPUnitTestCase {
	use TestCaseTrait;

	protected $config;

	/**
	 * Prepares the test environment before each test.
	 */
	protected function setUp() {
		parent::setUp();
		Monkey\setUp();

		if ( empty( $this->config ) ) {
			$this->loadTestDataConfig();
		}

		$this->mockCommonWpFunctions();
	}

	/**
	 * Cleans up the test environment after each test.
	 */
	protected function tearDown() {
		Monkey\tearDown();
		parent::tearDown();
	}

	protected function loadTestDataConfig() {
		$obj      = new ReflectionObject( $this );
		$filename = $obj->getFileName();

		$this->config = $this->getTestData( dirname( $filename ), basename( $filename, '.php' ) );
	}

	/**
	 * Gets the test data, if it exists, for this test class.
	 *
	 * @param string $dir      Directory of the test class.
	 * @param string $filename Test data filename without the .php extension.
	 *
	 * @return array array of test data.
	 */
	protected function getTestData( $dir, $filename ) {
		if ( empty( $dir ) || empty( $filename ) ) {
			return [];
		}

		$dir = str_replace( [ 'Integration', 'Unit' ], 'Fixtures', $dir );
		$dir = rtrim( $dir, '\\/' );
		$testdata = "$dir/{$filename}.php";

		return is_readable( $testdata )
			? require $testdata
			: [];
	}


	/**
	 * Mock common WP functions.
	 */
	protected function mockCommonWpFunctions() {
		Monkey\Functions\stubs(
			[
				'__',
				'esc_attr__',
				'esc_html__',
				'_x',
				'esc_attr_x',
				'esc_html_x',
				'_n',
				'_nx',
				'esc_attr',
				'esc_html',
				'esc_textarea',
				'esc_url',
			]
		);

		$functions = [
			'_e',
			'esc_attr_e',
			'esc_html_e',
			'_ex',
		];

		foreach ( $functions as $function ) {
			Monkey\Functions\when( $function )->echoArg();
		}

		include_once IMAGIFY_PLUGIN_TESTS_ROOT . '../Fixtures/WP/class-wp-error.php';

		Monkey\Functions\when( 'is_wp_error' )
			->alias( function( $thing ) {
				return $thing instanceof WP_Error;
			} );
	}
}
