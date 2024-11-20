<?php

namespace Imagify\Tests\Integration\inc\classes\ImagifyUser;

use Imagify;
use Imagify\User\User;
use stdClass;
use WP_Error;

/**
 * @covers \Imagify\User\User::get_error
 * @group  ImagifyAPI
 */
class Test_GetError extends TestCase {

	/**
	 * Test \Imagify\User\User->get_error() should return false when succesfully fetched user account data.
	 */
	public function testShouldReturnFalseWhenFetchedUserData() {
		update_imagify_option( 'api_key', $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) );

		// Verify the static $user property is null.
		$this->assertNull( $this->getNonPublicPropertyValue( 'user', Imagify::class ) );

		$user = new User();

		$this->assertFalse( $user->get_error() );

		$user_data = $this->getNonPublicPropertyValue( 'user', Imagify::class );
		$this->assertInstanceOf( stdClass::class, $user_data );
		$this->assertTrue( property_exists( $user_data, 'account_type' ) );
	}

	/**
	 * Test \Imagify\User\User->get_error() should return a WP_Error object when couldn’t fetch user account data.
	 */
	public function testShouldReturnErrorWhenCouldNotFetchUserData() {
		update_imagify_option( 'api_key', $this->invalidApiKey );

		// Verify the static $user property is null.
		$this->assertNull( $this->getNonPublicPropertyValue( 'user', Imagify::class ) );

		$user = new User();

		$this->assertInstanceOf( WP_Error::class, $user->get_error() );

		$user_data = $this->getNonPublicPropertyValue( 'user', Imagify::class );
		$this->assertInstanceOf( WP_Error::class, $user_data );
		$this->assertStringContainsString( 'Invalid token', $user_data->get_error_message() );
	}
}
