<?php
namespace Imagify\tests\Integration\inc\classes\ImagifyUser;

use Imagify\tests\Integration\TestCase;
use Mockery;

use Imagify;
use Imagify_User;
use WP_Error;

/**
 * Tests for Imagify_User->get_error().
 *
 * @covers Imagify_User::get_error
 * @group  ImagifyAPI
 */
class Test_GetError extends TestCase {
	/**
	 * Name of the API credentials config file.
	 *
	 * @var string
	 */
	protected $api_credentials_config_file = 'imagify-api';

	/**
	 * Test Imagify_User->get_error() should return false when succesfully fetched user account data.
	 */
	public function testShouldReturnFalseWhenFetchedUserData() {
		update_imagify_option( 'api_key', $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) );

		$imagify_mock = Mockery::mock( Imagify::class )->shouldReceive( 'get_user' )->once()->getMock();
		// Change the Imagify::$user to the mock.
		$ref = $this->get_reflective_property( 'user', get_class( $imagify_mock ) );
		$ref->setValue( null );
		$ref = $this->get_reflective_property( 'user', Imagify::class );
		$ref->setValue( null );
		$ref = $this->get_reflective_property( 'instance', Imagify::class );
		$ref->setValue( $imagify_mock );

		$this->assertFalse( ( new Imagify_User() )->get_error() );
	}

	/**
	 * Test Imagify_User->get_error() should return a WP_Error object when couldn’t fetch user account data.
	 */
	public function testShouldReturnErrorWhenCouldNotFetchUserData() {
		update_imagify_option( 'api_key', '1234567890abcdefghijklmnopqrstuvwxyz' );

		$imagify_mock = Mockery::mock( Imagify::class )->shouldReceive( 'get_user' )->once()->getMock();
		// Change the Imagify::$user to the mock.
		$ref = $this->get_reflective_property( 'user', get_class( $imagify_mock ) );
		$ref->setValue( null );
		$ref = $this->get_reflective_property( 'user', Imagify::class );
		$ref->setValue( null );
		$ref = $this->get_reflective_property( 'instance', Imagify::class );
		$ref->setValue( $imagify_mock );

		$this->assertFalse( ( new Imagify_User() )->get_error() );
	}
}
