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

	private function setApiKey( $api_key_value ) {
		update_imagify_option( 'api_key', $api_key_value );
		$imagify = Imagify::get_instance();

		$property = $this->get_reflective_property( 'api_key', $imagify );
		$property->setValue( $imagify, $api_key_value );
		$property = $this->get_reflective_property( 'all_headers', $imagify );
		$all_headers = $property->getValue( $imagify );
		$all_headers['Authorization'] = 'Authorization: token ' . $api_key_value;
		$property->setValue( $imagify, $all_headers );
	}

	public function testShouldReturnFalseWhenFetchedUserData() {
		$this->setApiKey( $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) );

		// Verify the static $user property is null.
		$this->assertNull( $this->getStaticPropertyValue( 'user', Imagify::class ) );

		$user = new Imagify_User();

		$this->assertFalse( $user->get_error() );

		// Check that the user data is set.
		$user_data = $this->getStaticPropertyValue( 'user', Imagify::class );
		$this->assertInstanceOf( \stdClass::class, $user_data );
		$this->assertTrue( property_exists( $user_data, 'account_type' ) );
	}

	public function testShouldReturnErrorWhenCouldNotFetchUserData() {
		$this->setApiKey( '1234567890abcdefghijklmnopqrstuvwxyz' );

		// Verify the static $user property is null.
		$this->assertNull( $this->getStaticPropertyValue( 'user', Imagify::class ) );

		$user = new Imagify_User();

		$this->assertInstanceOf( WP_Error::class, $this->getStaticPropertyValue( 'user', Imagify::class ) );
		$error = $user->get_error();
		$this->assertInstanceOf( WP_Error::class, $error );
		$this->assertSame( 'Invalid token.', $error->get_error_message() );

	}
}
