<?php
namespace Imagify\tests\Integration\inc\classes\ImagifyUser;

use Brain\Monkey\Functions;
use Imagify\tests\Integration\TestCase;

use Imagify;
use Imagify_User;

/**
 * Tests for Imagify_User->is_over_quota().
 *
 * @covers Imagify_User::is_over_quota
 * @group  ImagifyAPI
 */
class Test_IsOverQuota extends TestCase {
	protected $api_credentials_config_file = 'imagify-api.php';

	private $invalidApiKey = '1234567890abcdefghijklmnopqrstuvwxyz';

	private $originalImagifyInstance;
	private $originalUserInstance;
	private $originalImagifyInstanceSecureKey;
	private $originalApiKeyOption;
	private $originalPreviousQuotaOption;

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

		$this->originalApiKeyOption        = get_imagify_option( 'api_key' );
		$this->originalPreviousQuotaOption = get_imagify_option( 'previous_quota_percent' );
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
		update_imagify_option( 'previous_quota_percent', $this->originalPreviousQuotaOption );
	}

	/**
	 * Test Imagify_User->is_over_quota() should return false when couldn’t fetch user account data.
	 */
	public function testShouldReturnFalseWhenCouldNotFetchUserData() {
		update_imagify_option( 'api_key', $this->invalidApiKey );

		// Verify the static $user property is null.
		$this->assertNull( $this->getPropertyValue( 'user', Imagify::class ) );

		Functions\expect( 'imagify_round_half_five' )->never();

		$this->assertFalse( ( new Imagify_User() )->is_over_quota() );
	}

	/**
	 * Test Imagify_User->is_over_quota() should return false when paid account.
	 */
	public function testShouldReturnFalseWhenPaidAccount() {
		update_imagify_option( 'api_key', $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) );

		// Verify the static $user property is null.
		$this->assertNull( $this->getPropertyValue( 'user', Imagify::class ) );

		$imagifyUser = new Imagify_User();
		// Make our account a paid one.
		$imagifyUser->plan_id = 2;
		// Even if it is supposed to be over-quota.
		$imagifyUser->quota                        = 1000;
		$imagifyUser->consumed_current_month_quota = 1000;
		$imagifyUser->extra_quota                  = 5000;
		$imagifyUser->extra_quota_consumed         = 5000;

		$this->assertFalse( $imagifyUser->is_over_quota() );
	}

	/**
	 * Test Imagify_User->is_over_quota() should return false when free and not over quota.
	 */
	public function testShouldReturnFalseWhenFreeNotOverQuota() {
		update_imagify_option( 'api_key', $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) );

		// Verify the static $user property is null.
		$this->assertNull( $this->getPropertyValue( 'user', Imagify::class ) );

		$imagifyUser = new Imagify_User();
		// Make sure the account is not over-quota.
		$imagifyUser->quota                        = 1000;
		$imagifyUser->consumed_current_month_quota = 200;
		$imagifyUser->extra_quota                  = 5000;
		$imagifyUser->extra_quota_consumed         = 300;

		$this->assertFalse( $imagifyUser->is_over_quota() );
	}

	/**
	 * Test Imagify_User->is_over_quota() should return true when free and over quota.
	 */
	public function testShouldReturnTrueWhenFreeOverQuota() {
		update_imagify_option( 'api_key', $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) );

		// Verify the static $user property is null.
		$this->assertNull( $this->getPropertyValue( 'user', Imagify::class ) );

		$imagifyUser = new Imagify_User();
		// Make it over-quota.
		$imagifyUser->quota                        = 1000;
		$imagifyUser->consumed_current_month_quota = 1000;
		$imagifyUser->extra_quota                  = 5000;
		$imagifyUser->extra_quota_consumed         = 5000;

		$this->assertTrue( $imagifyUser->is_over_quota() );
	}
}
