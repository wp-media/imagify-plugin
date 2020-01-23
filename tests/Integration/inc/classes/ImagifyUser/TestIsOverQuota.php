<?php
namespace Imagify\tests\Integration\inc\classes\ImagifyUser;

use Brain\Monkey\Functions;
use Imagify\tests\Integration\TestCase;

use Imagify_Data;
use Imagify_User;
use WP_Error;

/**
 * Tests for Imagify_User->is_over_quota().
 *
 * @covers Imagify_User::is_over_quota
 * @group  ImagifyAPI
 */
class TestIsOverQuota extends TestCase {
	/**
	 * Test Imagify_User->is_over_quota() should return false when couldn’t fetch user account data.
	 */
	public function testShouldReturnFalseWhenCouldNotFetchUserData() {
		$wp_error = new WP_Error( 'error_id', 'Error Message' );

		Functions\when( 'get_imagify_user' )->justReturn( $wp_error );

		$this->assertFalse( ( new Imagify_User() )->is_over_quota() );
	}

	/**
	 * Test Imagify_User->is_over_quota() should return false when paid account.
	 */
	public function testShouldReturnFalseWhenPaidAccount() {
		$userData = (object) [
			'id'                           => 1,
			'email'                        => 'imagify@example.com',
			'plan_id'                      => '2',
			'plan_label'                   => 'whatever',
			'quota'                        => 456,
			'extra_quota'                  => 0,
			'extra_quota_consumed'         => 0,
			'consumed_current_month_quota' => 123,
			'next_date_update'             => '',
			'is_active'                    => 1,
		];

		Functions\when( 'get_imagify_user' )->justReturn( $userData );

		$this->assertFalse( ( new Imagify_User() )->is_over_quota() );
	}

	/**
	 * Test Imagify_User->is_over_quota() should return false when free and not over quota.
	 */
	public function testShouldReturnFalseWhenFreeNotOverQuota() {
		$userData = (object) [
			'id'                           => 1,
			'email'                        => 'imagify@example.com',
			'plan_id'                      => '1',
			'plan_label'                   => 'free',
			'quota'                        => 1000,
			'extra_quota'                  => 0,
			'extra_quota_consumed'         => 0,
			'consumed_current_month_quota' => 900, // Current consumed quota 90%.
			'next_date_update'             => '',
			'is_active'                    => 1,
		];

		Functions\when( 'get_imagify_user' )->justReturn( $userData );

		Imagify_Data::get_instance()->set( 'previous_quota_percent', 90.0 ); // Previous quota was 90%.

		$this->assertFalse( ( new Imagify_User() )->is_over_quota() );
	}

	/**
	 * Test Imagify_User->is_over_quota() should return true when free and over quota.
	 */
	public function testShouldReturnTrueWhenFreeOverQuota() {
		$userData = (object) [
			'id'                           => 1,
			'email'                        => 'imagify@example.com',
			'plan_id'                      => '1',
			'plan_label'                   => 'free',
			'quota'                        => 1000,
			'extra_quota'                  => 0,
			'extra_quota_consumed'         => 0,
			'consumed_current_month_quota' => 1000, // Current consumed quota 100%.
			'next_date_update'             => '',
			'is_active'                    => 1,
		];

		Functions\when( 'get_imagify_user' )->justReturn( $userData );

		Imagify_Data::get_instance()->set( 'previous_quota_percent', 100.0 ); // Previous quota was 100%.

		$this->assertTrue( ( new Imagify_User() )->is_over_quota() );
	}
}
