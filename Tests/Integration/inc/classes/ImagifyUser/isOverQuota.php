<?php

namespace Imagify\Tests\Integration\inc\classes\ImagifyUser;

use Brain\Monkey\Functions;
use Imagify;
use Imagify_User;

/**
 * @covers Imagify_User::is_over_quota
 * @group  ImagifyAPI
 */
class Test_IsOverQuota extends TestCase {
	private $originalPreviousQuotaOption;

	public function setUp() {
		parent::setUp();

		$this->originalPreviousQuotaOption = get_imagify_option( 'previous_quota_percent' );
	}

	public function tearDown() {
		parent::tearDown();

		// Restore the original option.
		update_imagify_option( 'previous_quota_percent', $this->originalPreviousQuotaOption );
	}

	public function testShouldReturnFalseWhenCouldNotFetchUserData() {
		update_imagify_option( 'api_key', $this->invalidApiKey );

		// Verify the static $user property is null.
		$this->assertNull( $this->getNonPublicPropertyValue( 'user', Imagify::class ) );

		Functions\expect( 'imagify_round_half_five' )->never();

		$this->assertFalse( ( new Imagify_User() )->is_over_quota() );
	}

	public function testShouldReturnFalseWhenPaidAccount() {
		update_imagify_option( 'api_key', $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) );

		// Verify the static $user property is null.
		$this->assertNull( $this->getNonPublicPropertyValue( 'user', Imagify::class ) );

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

	public function testShouldReturnFalseWhenFreeNotOverQuota() {
		update_imagify_option( 'api_key', $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) );

		// Verify the static $user property is null.
		$this->assertNull( $this->getNonPublicPropertyValue( 'user', Imagify::class ) );

		$imagifyUser = new Imagify_User();
		// Make sure the account is not over-quota.
		$imagifyUser->quota                        = 1000;
		$imagifyUser->consumed_current_month_quota = 200;
		$imagifyUser->extra_quota                  = 5000;
		$imagifyUser->extra_quota_consumed         = 300;

		$this->assertFalse( $imagifyUser->is_over_quota() );
	}

	public function testShouldReturnTrueWhenFreeOverQuota() {
		update_imagify_option( 'api_key', $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) );

		// Verify the static $user property is null.
		$this->assertNull( $this->getNonPublicPropertyValue( 'user', Imagify::class ) );

		$imagifyUser = new Imagify_User();
		// Make it over-quota.
		$imagifyUser->quota                        = 1000;
		$imagifyUser->consumed_current_month_quota = 1000;
		$imagifyUser->extra_quota                  = 5000;
		$imagifyUser->extra_quota_consumed         = 5000;

		$this->assertTrue( $imagifyUser->is_over_quota() );
	}
}
