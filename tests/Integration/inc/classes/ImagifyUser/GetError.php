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

	private $invalidApiKey = '1234567890abcdefghijklmnopqrstuvwxyz';

	private $originalImagifyInstance;
	private $originalUserInstance;
	private $originalImagifyInstanceSecureKey;
	//private $originalImagifyInstanceApiKey;
	private $originalApiKeyOption;

	public function setUp() {
		parent::setUp();

		// Store previous state and nullify values.
		$this->originalImagifyInstance = $this->resetPropertyValue( 'instance', Imagify::class );
		$this->originalUserInstance    = $this->resetPropertyValue( 'user', Imagify::class );

		if ( $this->originalImagifyInstance ) {
			$this->originalImagifyInstanceSecureKey = $this->resetPropertyValue( 'secure_key', $this->originalImagifyInstance );
		} else {
			$this->originalImagifyInstanceSecureKey = null;
		}

		$this->originalApiKeyOption = get_imagify_option( 'api_key' );
	}

	public function tearDown() {
		parent::tearDown();

		// Reset state.
		$modifiedImagifyInstance = $this->setPropertyValue( 'instance', Imagify::class, $this->originalImagifyInstance );

		remove_filter( 'http_request_args', [ $modifiedImagifyInstance, 'force_api_key_header' ], IMAGIFY_INT_MAX + 25 );

		$this->setPropertyValue( 'user', Imagify::class, $this->originalUserInstance );

		if ( $this->originalImagifyInstance ) {
			$this->setPropertyValue( 'secure_key', $this->originalImagifyInstance, $this->originalImagifyInstanceSecureKey );
		}

		update_imagify_option( 'api_key', $this->originalApiKeyOption );
	}

	/**
	 * Test Imagify_User->get_error() should return false when succesfully fetched user account data.
	 */
	public function testShouldReturnFalseWhenFetchedUserData() {
		update_imagify_option( 'api_key', $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) );

		// Verify the static $user property is null.
		$this->assertNull( $this->getPropertyValue( 'user', Imagify::class ) );

		$user = new Imagify_User();

		$this->assertFalse( $user->get_error() );

		$user_data = $this->getPropertyValue( 'user', Imagify::class );
		$this->assertInstanceOf( \stdClass::class, $user_data );
		$this->assertTrue( property_exists( $user_data, 'account_type' ) );
	}

	/**
	 * Test Imagify_User->get_error() should return a WP_Error object when couldn’t fetch user account data.
	 */
	public function testShouldReturnErrorWhenCouldNotFetchUserData() {
		update_imagify_option( 'api_key', $this->invalidApiKey );

		// Verify the static $user property is null.
		$this->assertNull( $this->getPropertyValue( 'user', Imagify::class ) );

		$user = new Imagify_User();

		$this->assertInstanceOf( WP_Error::class, $user->get_error() );

		$user_data = $this->getPropertyValue( 'user', Imagify::class );
		$this->assertInstanceOf( WP_Error::class, $user_data );
		$this->assertContains( 'Invalid token', $user_data->get_error_message() );
	}
}
