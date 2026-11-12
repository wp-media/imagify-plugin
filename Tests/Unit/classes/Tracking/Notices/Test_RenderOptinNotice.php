<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tracking\Notices;

use Brain\Monkey\Functions;
use Imagify\Dependencies\WPMedia\Mixpanel\Optin;
use Imagify\Tests\Unit\TestCase;
use Imagify\Tracking\Notices;
use Mockery;

/**
 * Tests for \Imagify\Tracking\Notices::render_optin_notice().
 *
 * @covers \Imagify\Tracking\Notices::render_optin_notice
 * @group  Tracking
 */
class Test_RenderOptinNotice extends TestCase {

	/**
	 * Tests that the method returns early when the current user lacks capability.
	 */
	public function testReturnsEarlyWhenUserCannotManageOptions(): void {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'imagify_is_screen' )->never();

		$optin = Mockery::mock( Optin::class );
		$optin->shouldNotReceive( 'is_enabled' );

		ob_start();
		( new Notices( $optin ) )->render_optin_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * Tests that the method returns early when not on the Imagify settings screen.
	 */
	public function testReturnsEarlyWhenNotOnSettingsScreen(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'imagify_is_screen' )->justReturn( false );
		Functions\expect( 'get_option' )->never();

		$optin = Mockery::mock( Optin::class );
		$optin->shouldNotReceive( 'is_enabled' );

		ob_start();
		( new Notices( $optin ) )->render_optin_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * Tests that the method returns early when the notice has already been answered.
	 */
	public function testReturnsEarlyWhenNoticeAlreadyDisplayed(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'imagify_is_screen' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( 1 );

		$optin = Mockery::mock( Optin::class );
		$optin->shouldNotReceive( 'is_enabled' );

		ob_start();
		( new Notices( $optin ) )->render_optin_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * Tests that the method returns early when the user already opted in.
	 */
	public function testReturnsEarlyWhenAlreadyOptedIn(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'imagify_is_screen' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( false );

		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'is_enabled' )->once()->andReturn( true );

		ob_start();
		( new Notices( $optin ) )->render_optin_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * Tests that the notice is rendered when all conditions are met.
	 */
	public function testRendersNoticeWhenAllConditionsMet(): void {
		if ( ! defined( 'IMAGIFY_PATH' ) ) {
			define( 'IMAGIFY_PATH', IMAGIFY_PLUGIN_ROOT );
		}
		if ( ! defined( 'IMAGIFY_VERSION' ) ) {
			define( 'IMAGIFY_VERSION', '2.3.0-test' );
		}

		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'imagify_is_screen' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( false );

		Functions\when( 'get_bloginfo' )->justReturn( '7.0' );
		Functions\when( 'get_imagify_option' )->justReturn( 1 );
		Functions\when( 'imagify_get_optimization_level_label' )->justReturn( 'Smart' );
		Functions\when( 'get_imagify_user' )->justReturn( (object) [ 'plan_label' => 'growth' ] );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html_e' )->alias( static function ( string $text ): void { echo $text; } );
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'admin_url' )->returnArg();
		Functions\when( 'wp_nonce_url' )->returnArg();

		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'is_enabled' )->once()->andReturn( false );

		ob_start();
		( new Notices( $optin ) )->render_optin_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'imagify-analytics-optin-notice', $output );
	}
}
