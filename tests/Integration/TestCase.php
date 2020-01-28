<?php
/**
 * Test Case for all of the integration tests.
 *
 * @package Imagify\tests\Integration
 */

namespace Imagify\tests\Integration;

use Brain\Monkey;
use Imagify\Tests\TestCaseTrait;
use WP_UnitTestCase;

abstract class TestCase extends WP_UnitTestCase {
	use TestCaseTrait;

	/**
	 * Name of the API credentials config file, if applicable. Set in the test or new TestCase.
	 * Example: 'imagify-api.php'.
	 *
	 * @var string
	 */
	protected $api_credentials_config_file;

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

	/**
	 * Gets the credential's value from either an environment variable (stored locally on the machine or CI) or from a local constant defined in `tests/env/local/imagify-api.php`.
	 *
	 * @param  string $name Name of the environment variable or constant to find. Example: 'IMAGIFY_TESTS_API_KEY'.
	 * @return string       Return the value if available. An empty string otherwise.
	 */
	protected function getApiCredential( $name ) {
		$var = getenv( $name );

		if ( ! empty( $var ) ) {
			return $var;
		}

		if ( defined( $name ) ) {
			return constant( $name );
		}

		if ( ! $this->api_credentials_config_file ) {
			return '';
		}

		$config_file = dirname( __DIR__ ) . '/env/local/' . $this->api_credentials_config_file;

		if ( ! is_readable( $config_file ) ) {
			return '';
		}

		// This file is local to the developer's machine and not stored in the repo.
		require_once $config_file;

		if ( ! defined( $name ) ) {
			return '';
		}

		return constant( $name );
	}
}
