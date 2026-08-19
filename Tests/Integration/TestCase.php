<?php

namespace Imagify\Tests\Integration;

use Imagify;
use WPMedia\PHPUnit\Integration\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {
	protected $useApi = true;
	protected $api_credentials_config_file = 'imagify-api.php';
	protected $invalidApiKey = '1234567890abcdefghijklmnopqrstuvwxyz';
	protected $originalImagifyInstance;
	protected $originalApiKeyOption;

	/**
	 * Prepares the test environment before each test.
	 */
	public function set_up() {
		parent::set_up();

		if ( empty( $this->config ) ) {
			$this->loadTestDataConfig();
		}

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
	public function tear_down() {
		parent::tear_down();

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

	/**
	 * Set the singleton's `$instance` property to the given instance.
	 *
	 * @param string $class    Name of the target class.
	 * @param mixed  $instance Instance of the target object.
	 *
	 * @return mixed            Previous value.
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 */
	protected function setSingletonInstance( $class, $instance ) {
		return $this->setPropertyValue( 'instance', $class, $instance );
	}

	/**
	 * Set the value of a private/protected property.
	 *
	 * @param string        $property Property name for which to gain access.
	 * @param string|object $class    Class name for a static property, or instance for an instance property.
	 * @param mixed         $value    The value to set to the property.
	 *
	 * @return mixed                   The previous value of the property.
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 */
	protected function setPropertyValue( $property, $class, $value ) {
		$instance = is_object( $class ) ? $class : null;

		// Read the previous value, then set the new one, delegating to the
		// wp-media/phpunit trait which handles static properties safely
		// (ReflectionProperty::setValue() with a single argument is deprecated as of PHP 8.3).
		$previous = $this->getNonPublicPropertyValue( $property, $class, $instance );
		$this->set_reflective_property( $value, $property, $class );

		return $previous;
	}

	/**
	 * Reset the value of a private/protected property.
	 *
	 * @param string        $property Property name for which to gain access.
	 * @param string|object $class    Class name for a static property, or instance for an instance property.
	 *
	 * @return mixed                   The previous value of the property.
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 */
	protected function resetPropertyValue( $property, $class ) {
		return $this->setPropertyValue( $property, $class, null );
	}
}
