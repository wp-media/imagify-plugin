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

		// Store original instance.
		$this->originalImagifyInstance = Imagify::get_instance();

		if ( $this->useApi ) {
			$this->originalApiKeyOption = get_imagify_option( 'api_key' );
		}

		// Clear the static `$instance` property.
		$this->resetPropertyValue( 'instance', $this->originalImagifyInstance );
	}

	/**
	 * Cleans up the test environment after each test.
	 */
	public function tearDown() {
		Monkey\tearDown();
		parent::tearDown();

		// Restore the Imagify instance.
		$this->setPropertyValue( 'instance', Imagify::class, $this->originalImagifyInstance );

		// Restore the option.
		if ( $this->useApi ) {
			update_imagify_option( 'api_key', $this->originalApiKeyOption );
		}
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
