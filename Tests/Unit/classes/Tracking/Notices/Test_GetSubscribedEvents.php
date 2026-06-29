<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tracking\Notices;

use Imagify\Tests\Unit\TestCase;
use Imagify\Tracking\Notices;

/**
 * Tests for \Imagify\Tracking\Notices::get_subscribed_events().
 *
 * @covers \Imagify\Tracking\Notices::get_subscribed_events
 * @group  Tracking
 */
class Test_GetSubscribedEvents extends TestCase {

	/**
	 * Tests that all three hooks are registered.
	 *
	 * @dataProvider provideExpectedHooks
	 */
	public function testRegistersHook( string $hook ): void {
		$this->assertArrayHasKey( $hook, Notices::get_subscribed_events() );
	}

	/**
	 * @return array<string, array<int, string>>
	 */
	public function provideExpectedHooks(): array {
		return [
			'settings action' => [ 'imagify_settings_after_lossless' ],
			'ajax action'     => [ 'wp_ajax_imagify_toggle_tracking_optin' ],
		];
	}

	/**
	 * Tests that each hook maps to the expected method name.
	 *
	 * @dataProvider provideHookMethodPairs
	 */
	public function testHookMapsToCorrectMethod( string $hook, string $method ): void {
		$events = Notices::get_subscribed_events();

		$this->assertSame( $method, $events[ $hook ] );
	}

	/**
	 * @return array<string, array<int, string>>
	 */
	public function provideHookMethodPairs(): array {
		return [
			'settings -> render' => [ 'imagify_settings_after_lossless', 'render_optin_section' ],
			'ajax -> toggle'     => [ 'wp_ajax_imagify_toggle_tracking_optin', 'ajax_toggle_optin' ],
		];
	}
}
