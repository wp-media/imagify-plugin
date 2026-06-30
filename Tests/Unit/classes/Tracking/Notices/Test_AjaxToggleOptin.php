<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tracking\Notices;

use Brain\Monkey\Functions;
use Imagify\Dependencies\WPMedia\Mixpanel\Optin;
use Imagify\Tests\Unit\TestCase;
use Imagify\Tracking\Notices;
use Mockery;

/**
 * Tests for \Imagify\Tracking\Notices::ajax_toggle_optin().
 *
 * @covers \Imagify\Tracking\Notices::ajax_toggle_optin
 * @group  Tracking
 */
class Test_AjaxToggleOptin extends TestCase {

	/**
	 * Tests that enabling sends JSON success and sets the thank-you transient.
	 */
	public function testEnableCallsOptinAndSetsTransient(): void {
		$_POST['value'] = '1';

		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'enable' )->once();

		Functions\when( 'check_ajax_referer' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\expect( 'set_transient' )
			->once()
			->with( Notices::THANKYOU_TRANSIENT, 1, 60 );
		Functions\when( 'wp_send_json_success' )->justReturn();

		( new Notices( $optin ) )->ajax_toggle_optin();
	}

	/**
	 * Tests that disabling calls disable() and does NOT set the transient.
	 */
	public function testDisableCallsOptinDisableWithoutTransient(): void {
		$_POST['value'] = '0';

		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'disable' )->once();

		Functions\when( 'check_ajax_referer' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\expect( 'set_transient' )->never();
		Functions\when( 'wp_send_json_success' )->justReturn();

		( new Notices( $optin ) )->ajax_toggle_optin();
	}

	/**
	 * Tests that an unauthorized user receives a JSON error and no opt-in method is called.
	 */
	public function testUnauthorizedUserReceivesJsonError(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldNotReceive( 'enable' );
		$optin->shouldNotReceive( 'disable' );

		Functions\when( 'check_ajax_referer' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( '__' )->returnArg();
		// Simulate wp_send_json_error's real behaviour (it calls wp_die and never returns).
		Functions\when( 'wp_send_json_error' )->alias(
			static function (): never {
				throw new \RuntimeException( 'wp_send_json_error: terminated' );
			}
		);

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'wp_send_json_error: terminated' );

		( new Notices( $optin ) )->ajax_toggle_optin();
	}
}
