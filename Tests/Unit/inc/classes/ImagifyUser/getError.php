<?php
namespace Imagify\Tests\Unit\inc\classes\ImagifyUser;

use Brain\Monkey\Functions;
use Imagify\Tests\Unit\TestCase;
use Imagify\User\User;
use WP_Error;

/**
 * Tests for \Imagify\User\User->get_error().
 *
 * @covers \Imagify\User\User::get_error
 * @group  ImagifyAPI
 */
class Test_GetError extends TestCase {
	/**
	 * Test \Imagify\User\User->get_error() should return false when succesfully fetched user account data.
	 */
	public function testShouldReturnFalseWhenFetchedUserData() {
		$userData = (object) [
			'id'                           => 1,
			'email'                        => 'imagify@example.com',
			'plan_id'                      => '1',
			'plan_label'                   => 'free',
			'quota'                        => 456,
			'extra_quota'                  => 0,
			'extra_quota_consumed'         => 0,
			'consumed_current_month_quota' => 123,
			'next_date_update'             => '',
			'is_active'                    => 1,
		];

		Functions\when( 'get_imagify_user' )->justReturn( $userData );

		$this->assertFalse( ( new User() )->get_error() );
	}

	/**
	 * Test \Imagify\User\User->get_error() should return a WP_Error object when couldn’t fetch user account data.
	 */
	public function testShouldReturnErrorWhenCouldNotFetchUserData() {
		$wp_error = new WP_Error( 'error_id', 'Error Message' );

		Functions\when( 'get_imagify_user' )->justReturn( $wp_error );

		$this->assertSame( $wp_error, ( new User() )->get_error() );
	}
}
