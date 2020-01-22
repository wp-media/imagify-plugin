<?php
namespace Imagify\Tests\Unit\inc\classes\ImagifyUser;

use Brain\Monkey\Functions;
use Imagify\Tests\Unit\TestCase;

use Imagify_User;

/**
 * Tests for Imagify_User->get_error().
 *
 * @group OldClasses
 */
class TestGetError extends TestCase {
	/**
	 * Test Imagify_User->get_error() should return false when succesfully fetched user account data.
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

		$this->assertFalse( ( new Imagify_User() )->get_error() );
	}

	/**
	 * Test Imagify_User->get_error() should return a WP_Error object when couldn’t fetch user account data.
	 */
	public function testShouldReturnErrorWhenCouldNotFetchUserData() {
		$wp_error = $this->getMockBuilder( '\WP_Error' )
			->disableOriginalConstructor()
			->setConstructorArgs( [ 'error_id', 'Error Message' ] )
			->getMock();

		Functions\when( 'get_imagify_user' )->justReturn( $wp_error );

		$this->assertSame( ( new Imagify_User() )->get_error(), $wp_error );
	}
}
