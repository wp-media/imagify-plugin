<?php
namespace Imagify\Tests\Unit\inc\classes\ImagifyUser;

use Imagify\Tests\Unit\TestCase;
use Imagify\User\User;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests for \Imagify\User\User->init_user().
 *
 * @covers \Imagify\User\User::init_user
 * @group  ImagifyAPI
 */
class Test_GetUserData extends TestCase {
	private $user;

	protected function setUp(): void {
		parent::setUp();

		$this->user = Mockery::mock(User::class)->makePartial();
	}

	/**
	 * Test \Imagify\User\User->init_user().
	 */
	public function testEnsureInitUserIsCalled() {
		$userData = (object) [
			'id'                           => 14,
			'email'                        => 'imagify@example.com',
			'plan_id'                      => '1',
			'plan_label'                   => 'free',
			'quota'                        => 1000,
			'extra_quota'                  => 0,
			'extra_quota_consumed'         => 0,
			'consumed_current_month_quota' => 10,
			'next_date_update'             => '',
			'is_active'                    => 1,
			'is_monthly'                   => true,
		];

		Functions\when( 'get_imagify_user' )->justReturn( $userData );

		Functions\when( 'is_wp_error' )->justReturn( false );

		$this->assertEquals(14, $this->user->get_id());
		$this->assertEquals('imagify@example.com', $this->user->get_email(), 'Email should be initialized and returned correctly.');
		$this->assertTrue($this->user->is_monthly, 'is_monthly should be initialized to true.');
	}
}
