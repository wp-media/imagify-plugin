<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tools\Subscriber;

use Imagify\Tests\Unit\TestCase;
use Imagify\Tools\Subscriber;
use Imagify\Tools\ResetInternalState;
use Mockery;
use Brain\Monkey\Functions;

/**
 * Tests for \Imagify\Tools\Subscriber::reset_internal_state().
 *
 * @covers \Imagify\Tools\Subscriber::reset_internal_state
 * @group  Tools
 */
class Test_ResetInternalState extends TestCase {

	/**
	 * Tests that reset_internal_state() calls reset() and sends JSON success for an authorized user.
	 */
	public function testCallsResetAndSendsSuccessForAuthorizedUser(): void {
		$context = Mockery::mock( 'stdClass' );
		$context->shouldReceive( 'current_user_can' )->with( 'manage' )->andReturn( true );

		Functions\when( 'imagify_check_nonce' )->justReturn();
		Functions\when( 'imagify_get_context' )->justReturn( $context );
		Functions\when( 'wp_send_json_success' )->justReturn();
		Functions\when( '__' )->returnArg();

		$reset_service = Mockery::mock( ResetInternalState::class );
		$reset_service->shouldReceive( 'reset' )->once();

		( new Subscriber( $reset_service ) )->reset_internal_state();
	}

	/**
	 * Tests that reset_internal_state() calls imagify_die() and skips reset() for unauthorized users.
	 */
	public function testCallsImageDieAndSkipsResetForUnauthorizedUser(): void {
		$context = Mockery::mock( 'stdClass' );
		$context->shouldReceive( 'current_user_can' )->with( 'manage' )->andReturn( false );

		Functions\when( 'imagify_check_nonce' )->justReturn();
		Functions\when( 'imagify_get_context' )->justReturn( $context );
		Functions\when( 'imagify_die' )->justReturn();

		$reset_service = Mockery::mock( ResetInternalState::class );
		$reset_service->shouldNotReceive( 'reset' );

		( new Subscriber( $reset_service ) )->reset_internal_state();
	}
}
