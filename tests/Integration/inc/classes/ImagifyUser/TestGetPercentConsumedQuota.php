<?php
namespace Imagify\Tests\Integration\inc\classes\ImagifyUser;

use Brain\Monkey\Functions;
use Imagify\Tests\Integration\TestCase;

use Imagify_Data;
use Imagify_User;
use WP_Error;

/**
 * Tests for Imagify_User->get_percent_consumed_quota().
 *
 * @group OldClasses
 */
class TestGetPercentConsumedQuota extends TestCase {

	/**
	 * Test Imagify_User->get_percent_consumed_quota() should return 0 when couldn’t fetch user account data.
	 */
	public function testShouldReturnZeroWhenCouldNotFetchUserData() {
		$wp_error = new WP_Error( 'error_id', 'Error Message' );

		Functions\when( 'get_imagify_user' )->justReturn( $wp_error );
		Functions\expect( 'imagify_round_half_five' )->never();

		$this->assertSame( ( new Imagify_User() )->get_percent_consumed_quota(), 0 );
	}

	/**
	 * Test Imagify_User->get_percent_consumed_quota() should return a quota when able to fetch user account data.
	 */
	public function testShouldReturnQuotaWhenFetchedUserData() {
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

		$this->assertSame( ( new Imagify_User() )->get_percent_consumed_quota(), 90.0 );

		$userData->consumed_current_month_quota = 500; // Current consumed quota 50%.

		Functions\when( 'get_imagify_user' )->justReturn( $userData );

		$this->assertSame( ( new Imagify_User() )->get_percent_consumed_quota(), 50.0 );
	}
}
