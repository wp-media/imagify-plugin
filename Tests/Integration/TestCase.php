<?php

namespace Imagify\Tests\Integration;

use Brain\Monkey;
use Imagify;
use Imagify\Tests\TestCaseTrait;
use WP_UnitTestCase;

abstract class TestCase extends WP_UnitTestCase {
	use TestCaseTrait;

	protected $useApi = true;
	protected $api_credentials_config_file = 'imagify-api.php';
	protected $invalidApiKey = '1234567890abcdefghijklmnopqrstuvwxyz';
	protected $originalImagifyInstance;
	protected $originalApiKeyOption;

	/**
	 * Prepares the test environment before each test.
	 */
	public function setUp() {
		parent::setUp();
		Monkey\setUp();

		if ( ! $this->useApi ) {
			return;
		}

		// Store original instance and clear the static `$instance` property.
		$this->originalImagifyInstance = $this->setSingletonInstance( Imagify::class, null );
		$this->originalApiKeyOption    = get_imagify_option( 'api_key' );
	}

	/**
	 * Cleans up the test environment after each test.
	 */
	public function tearDown() {
		Monkey\tearDown();
		parent::tearDown();

		if ( ! $this->useApi ) {
			return;
		}

		// Restore the Imagify instance and API key option.
		$this->setSingletonInstance( Imagify::class, $this->originalImagifyInstance ); // $this->originalImagifyInstance can be null.
		update_imagify_option( 'api_key', $this->originalApiKeyOption );
	}

	/**
	 * Gets the credential's value from either an environment variable (stored locally on the machine or CI) or from a
	 * local constant defined in `tests/env/local/imagify-api.php`.
	 *
	 * @param string $name Name of the environment variable or constant to find. Example: 'IMAGIFY_TESTS_API_KEY'.
	 *
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
