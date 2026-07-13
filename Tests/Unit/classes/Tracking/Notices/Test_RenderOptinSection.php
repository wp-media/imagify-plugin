<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tracking\Notices;

use Brain\Monkey\Functions;
use Imagify\Dependencies\WPMedia\Mixpanel\Optin;
use Imagify\Tests\Unit\TestCase;
use Imagify\Tracking\Notices;
use Mockery;

/**
 * Tests for \Imagify\Tracking\Notices::render_optin_section().
 *
 * @covers \Imagify\Tracking\Notices::render_optin_section
 * @covers \Imagify\Tracking\Notices::collect_data
 * @group  Tracking
 */
class Test_RenderOptinSection extends TestCase {

	/**
	 * Define constants and stub WP functions needed by the view template.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! defined( 'IMAGIFY_PATH' ) ) {
			define( 'IMAGIFY_PATH', IMAGIFY_PLUGIN_ROOT );
		}
		if ( ! defined( 'IMAGIFY_VERSION' ) ) {
			define( 'IMAGIFY_VERSION', '2.3.0-test' );
		}
	}

	/**
	 * Tests that render_optin_section() outputs the analytics toggle markup.
	 */
	public function testOutputsAnalyticsToggle(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'is_enabled' )->once()->andReturn( false );

		Functions\when( 'get_bloginfo' )->justReturn( '7.0' );
		Functions\when( 'get_imagify_option' )->justReturn( 1 );
		Functions\when( 'imagify_get_optimization_level_label' )->justReturn( 'Smart' );
		Functions\when( 'get_imagify_user' )->justReturn( (object) [ 'plan_label' => 'growth' ] );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html_e' )->alias( static function ( string $text ): void { echo $text; } );
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_attr_e' )->alias( static function ( string $text ): void { echo $text; } );
		Functions\when( 'wp_create_nonce' )->justReturn( 'test-nonce' );
		Functions\when( 'checked' )->justReturn( '' );

		ob_start();
		( new Notices( $optin ) )->render_optin_section();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'imagify-analytics-optin', $output );
		$this->assertStringContainsString( 'imagify-analytics-enabled', $output );
		$this->assertStringContainsString( 'test-nonce', $output );
	}

	/**
	 * Tests that render_optin_section() marks the checkbox as checked when opt-in is enabled.
	 */
	public function testCheckboxIsCheckedWhenEnabled(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'is_enabled' )->once()->andReturn( true );

		Functions\when( 'get_bloginfo' )->justReturn( '7.0' );
		Functions\when( 'get_imagify_option' )->justReturn( 0 );
		Functions\when( 'imagify_get_optimization_level_label' )->justReturn( 'Lossless' );
		Functions\when( 'get_imagify_user' )->justReturn( new \WP_Error() );
		Functions\when( 'is_wp_error' )->justReturn( true );
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html_e' )->alias( static function ( string $text ): void { echo $text; } );
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_attr_e' )->alias( static function ( string $text ): void { echo $text; } );
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce-abc' );
		Functions\when( 'checked' )->alias( static function (): void { echo ' checked="checked"'; } );

		ob_start();
		( new Notices( $optin ) )->render_optin_section();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'checked="checked"', $output );
		$this->assertStringContainsString( 'N/A', $output );
	}
}
