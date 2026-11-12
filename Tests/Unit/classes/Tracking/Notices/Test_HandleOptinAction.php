<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tracking\Notices;

use Brain\Monkey\Functions;
use Imagify\Dependencies\WPMedia\Mixpanel\Optin;
use Imagify\Tests\Unit\TestCase;
use Imagify\Tracking\Notices;
use Mockery;

/**
 * Tests for \Imagify\Tracking\Notices::handle_optin_action().
 *
 * The method ends with `exit`, mirroring the standard `admin-post.php` handler
 * pattern. `wp_safe_redirect()` is aliased to throw so the (untestable) `exit`
 * statement is never actually reached — the same technique already used in
 * Test_AjaxToggleOptin for `wp_send_json_error()`.
 *
 * @covers \Imagify\Tracking\Notices::handle_optin_action
 * @group  Tracking
 */
class Test_HandleOptinAction extends TestCase {

	/**
	 * Registers the `wp_safe_redirect` termination alias and returns the expectation to assert on.
	 */
	private function expectRedirectAndTerminate(): void {
		Functions\when( 'wp_get_referer' )->justReturn( 'http://example.com/wp-admin/options-general.php?page=imagify' );
		Functions\when( 'wp_safe_redirect' )->alias(
			static function (): never {
				throw new \RuntimeException( 'wp_safe_redirect: terminated' );
			}
		);

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'wp_safe_redirect: terminated' );
	}

	/**
	 * Tests that an unauthorized user is redirected without any option being changed.
	 */
	public function testUnauthorizedUserIsRedirectedWithoutChanges(): void {
		Functions\when( 'check_admin_referer' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'update_option' )->never();
		$this->expectRedirectAndTerminate();

		$optin = Mockery::mock( Optin::class );
		$optin->shouldNotReceive( 'enable' );

		( new Notices( $optin ) )->handle_optin_action();
	}

	/**
	 * Tests that accepting ("yes") enables the opt-in, sets the thank-you transient, and marks the notice as displayed.
	 */
	public function testAcceptEnablesOptinSetsTransientAndMarksDisplayed(): void {
		$_GET['value'] = 'yes';

		Functions\when( 'check_admin_referer' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );

		Functions\expect( 'set_transient' )
			->once()
			->with( Notices::THANKYOU_TRANSIENT, 1, 60 );

		Functions\expect( 'update_option' )
			->once()
			->with( Notices::NOTICE_DISPLAYED_OPTION, 1 );

		$this->expectRedirectAndTerminate();

		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'enable' )->once();

		try {
			( new Notices( $optin ) )->handle_optin_action();
		} finally {
			unset( $_GET['value'] );
		}
	}

	/**
	 * Tests that declining ("no") does not enable the opt-in but still marks the notice as displayed.
	 */
	public function testDeclineDoesNotEnableOptinButMarksDisplayed(): void {
		$_GET['value'] = 'no';

		Functions\when( 'check_admin_referer' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );

		Functions\expect( 'set_transient' )->never();

		Functions\expect( 'update_option' )
			->once()
			->with( Notices::NOTICE_DISPLAYED_OPTION, 1 );

		$this->expectRedirectAndTerminate();

		$optin = Mockery::mock( Optin::class );
		$optin->shouldNotReceive( 'enable' );

		try {
			( new Notices( $optin ) )->handle_optin_action();
		} finally {
			unset( $_GET['value'] );
		}
	}
}
