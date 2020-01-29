<?php
namespace Imagify\tests\Unit\inc\classes\ImagifyUser;

use Brain\Monkey\Functions;
use Imagify\tests\Unit\TestCase;
use Mockery;
use WP_Error;

use Imagify_Data;
use Imagify_User;

/**
 * Tests for Imagify_User->is_over_quota().
 *
 * @covers Imagify_User::is_over_quota
 * @group  ImagifyAPI
 */
class Test_IsOverQuota extends TestCase {
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

		$this->createMocks( $userData, 90 );

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

		$this->createMocks( $userData, 100 );

		$this->assertTrue( ( new Imagify_User() )->is_over_quota() );
	}

	private function createMocks( $userData, $dataPreviousQuotaPercent ) {
		Functions\when( 'get_imagify_user' )->justReturn( $userData );
		Functions\expect( 'imagify_round_half_five' )
			->once()
			->with( 0 ) // extra_quota_consumed.
			->andReturn( 0.0 );

		$imagify_data_mock = Mockery::mock( Imagify_Data::class );
		// Change the Imagify_Data::$_instance to the mock.
		$this->setPropertyValue( '_instance', Imagify_Data::class, $imagify_data_mock );

		$imagify_data_mock->shouldReceive( 'get' )
			->atMost()
			->times( 1 )
			->with( 'previous_quota_percent' )
			->andReturn( $dataPreviousQuotaPercent );

		$imagify_data_mock->expects( 'set' )
			->never();
	}
}
