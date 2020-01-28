<?php

namespace Imagify\tests\Integration\inc\classes\ImagifyUser;

use Imagify;
use Imagify_User;
use Imagify\tests\Integration\TestCase;
use WP_Error;

/**
 * Tests for Imagify_User->get_error().
 *
 * @covers Imagify_User::get_error
 * @group  ImagifyAPI
 */
class Test_GetError extends TestCase {
	protected $api_credentials_config_file = 'imagify-api';

	public function setUp() {
		parent::setUp();

		$this->resetStaticUser();
	}

	public function tearDown() {
		parent::tearDown();

		// Reset state.
		$this->resetStaticUser();
	}

	function resetStaticUser() {
		$this->resetStaticProperty( 'user', Imagify::class );
	}

	/**
	 * Test Imagify_User->get_error() should return false when succesfully fetched user account data.
	 */
	public function testShouldReturnFalseWhenFetchedUserData() {
		update_imagify_option( 'api_key', $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) );

		// Verify the static $user property is null.
		$this->assertNull( $this->getStaticPropertyValue( 'user', Imagify::class ) );

		$user = new Imagify_User();

		$this->assertFalse( $user->get_error() );
		$user_data = $this->getStaticPropertyValue( 'user', Imagify::class );
		$this->assertInstanceOf( \stdClass::class, $user_data );
		$this->assertTrue( property_exists( $user_data, 'account_type' ) );
	}

	/**
	 * Test Imagify_User->get_error() should return a WP_Error object when couldn’t fetch user account data.
	 */
	public function testShouldReturnErrorWhenCouldNotFetchUserData() {
		update_imagify_option( 'api_key', '1234567890abcdefghijklmnopqrstuvwxyz' );
		$imagify = Imagify::get_instance();
		$api_key = $this->get_reflective_property( 'api_key', $imagify );
		$api_key->setValue( $imagify, 'invalid_api_token' );

		// Verify the static $user property is null.
		$this->assertNull( $this->getStaticPropertyValue( 'user', Imagify::class ) );

		// Callback is needed to force an invalid API key. TODO Resolve why not pulling from the API token. Then remove this callback.
		$callback = function( $args, $url ) use ( $imagify ) {
			if ( 'https://app.imagify.io/api/users/me/' !== $url ) {
				return $args;
			}
			$prop = $this->get_reflective_property( 'secure_key', $imagify );
			$secure_key = $prop->getValue( $imagify );

			$args['headers']['Authorization'] = 'token 1234567890abcdefghijklmnopqrstuvwxyz';
			if ( isset($args[ $secure_key ] )) {
				$args[ $secure_key ] = 'token 1234567890abcdefghijklmnopqrstuvwxyz';
			}

			return $args;
		};

		add_filter( 'http_request_args', $callback, IMAGIFY_INT_MAX - 200, 2 );

		$user = new Imagify_User();

		remove_filter( 'http_request_args', $callback, 9999, 2 );

		$this->assertInstanceOf( WP_Error::class, $this->getStaticPropertyValue( 'user', Imagify::class ) );
		$error = $user->get_error();
		$this->assertContains( 'Invalid token header.', $error->get_error_message() );

	}
}
