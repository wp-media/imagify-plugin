<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tracking\Notices;

use Brain\Monkey\Functions;
use Imagify\Dependencies\WPMedia\Mixpanel\Optin;
use Imagify\Tests\Unit\TestCase;
use Imagify\Tracking\Notices;
use Mockery;

/**
 * Tests for \Imagify\Tracking\Notices::render_thankyou_notice().
 *
 * @covers \Imagify\Tracking\Notices::render_thankyou_notice
 * @group  Tracking
 */
class Test_RenderThankyouNotice extends TestCase {

	/**
	 * Tests that the method returns early when the current user lacks capability.
	 */
	public function testReturnsEarlyWhenUserCannotManageOptions(): void {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'get_transient' )->never();

		$optin = Mockery::mock( Optin::class );

		ob_start();
		( new Notices( $optin ) )->render_thankyou_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * Tests that the method returns early when not on the Imagify settings screen.
	 */
	public function testReturnsEarlyWhenNotOnSettingsScreen(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'imagify_is_screen' )->justReturn( false );
		Functions\expect( 'get_transient' )->never();

		$optin = Mockery::mock( Optin::class );

		ob_start();
		( new Notices( $optin ) )->render_thankyou_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * Tests that the method returns early and never calls delete_transient when the transient is absent.
	 */
	public function testReturnsEarlyWhenTransientIsNotSet(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'imagify_is_screen' )->justReturn( true );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\expect( 'delete_transient' )->never();

		$optin = Mockery::mock( Optin::class );

		ob_start();
		( new Notices( $optin ) )->render_thankyou_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * Tests that delete_transient is called when the transient exists.
	 */
	public function testDeletesTransientWhenPresent(): void {
		if ( ! defined( 'IMAGIFY_PATH' ) ) {
			define( 'IMAGIFY_PATH', IMAGIFY_PLUGIN_ROOT );
		}
		if ( ! defined( 'IMAGIFY_VERSION' ) ) {
			define( 'IMAGIFY_VERSION', '2.3.0-test' );
		}

		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'imagify_is_screen' )->justReturn( true );
		Functions\when( 'get_transient' )->justReturn( 1 );
		Functions\expect( 'delete_transient' )
			->once()
			->with( Notices::THANKYOU_TRANSIENT );

		Functions\when( 'get_bloginfo' )->justReturn( '7.0' );
		Functions\when( 'get_imagify_option' )->justReturn( 1 );
		Functions\when( 'imagify_get_optimization_level_label' )->justReturn( 'Smart' );
		Functions\when( 'get_imagify_user' )->justReturn( (object) [ 'plan_label' => 'growth' ] );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html_e' )->alias( static function ( string $text ): void { echo $text; } );
		Functions\when( 'esc_html' )->returnArg();

		$optin = Mockery::mock( Optin::class );

		ob_start();
		( new Notices( $optin ) )->render_thankyou_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'notice-success', $output );
	}
}
