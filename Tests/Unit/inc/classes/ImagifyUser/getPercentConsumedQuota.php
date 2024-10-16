<?php
namespace Imagify\Tests\Unit\inc\classes\ImagifyUser;

use Brain\Monkey\Functions;
use Imagify\Tests\Unit\TestCase;
use Imagify\User\User;
use Mockery;
use WP_Error;

use Imagify_Data;

/**
 * Tests for \Imagify\User\User->get_percent_consumed_quota().
 *
 * @covers \Imagify\User\User::get_percent_consumed_quota
 * @group  ImagifyAPI
 */
class Test_GetPercentConsumedQuota extends TestCase {

	/**
	 * Test \Imagify\User\User->get_percent_consumed_quota() should return 0 when couldn’t fetch user account data.
	 */
	public function testShouldReturnZeroWhenCouldNotFetchUserData() {
		$wp_error = new WP_Error( 'error_id', 'Error Message' );

		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'get_imagify_user' )->justReturn( $wp_error );
		Functions\when( 'set_transient')->justReturn();
		Functions\expect( 'imagify_round_half_five' )->never();

		$this->assertSame( ( new User() )->get_percent_consumed_quota(), 0 );
	}

	/**
	 * Test \Imagify\User\User->get_percent_consumed_quota() should return a quota when able to fetch user account data.
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
			'is_monthly'                   => true,
		];

		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'get_imagify_user' )->justReturn( $userData );
		Functions\when( 'set_transient')->justReturn();
		Functions\expect( 'imagify_round_half_five' )
			->twice()
			->with( 0 ) // extra_quota_consumed.
			->andReturn( 0.0 );

		$imagify_data_mock = Mockery::mock( Imagify_Data::class );
		// Change the Imagify_Data::$_instance to the mock.
		$this->setPropertyValue( '_instance', Imagify_Data::class, $imagify_data_mock );

		$imagify_data_mock->shouldReceive( 'get' )
			->atMost()
			->times( 1 )
			->with( 'previous_quota_percent' )
			->andReturn( 90 ); // Previous consumed quota was 90%.

		$imagify_data_mock->expects( 'set' )
			->never();

		$this->assertSame( ( new User() )->get_percent_consumed_quota(), 90.0 );

		$userData->consumed_current_month_quota = 500; // Current consumed quota 50%.

		Functions\when( 'get_imagify_user' )->justReturn( $userData );

		$imagify_data_mock->shouldReceive( 'get' )
			->never();

		$this->assertSame( ( new User() )->get_percent_consumed_quota(), 50.0 );
	}
}
