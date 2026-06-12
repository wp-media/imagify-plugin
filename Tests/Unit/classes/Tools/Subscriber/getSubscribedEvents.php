<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tools\Subscriber;

use Imagify\Tests\Unit\TestCase;
use Imagify\Tools\Subscriber;
use Imagify\Tools\ResetInternalState;
use Mockery;

/**
 * Tests for \Imagify\Tools\Subscriber::get_subscribed_events().
 *
 * @covers \Imagify\Tools\Subscriber::get_subscribed_events
 * @group  Tools
 */
class Test_GetSubscribedEvents extends TestCase {

	/**
	 * Tests that get_subscribed_events() registers the AJAX reset action.
	 */
	public function testRegistersAjaxResetAction(): void {
		$events = Subscriber::get_subscribed_events();

		$this->assertArrayHasKey( 'wp_ajax_imagify_reset_internal_state', $events );
		$this->assertSame( 'handle_reset', $events['wp_ajax_imagify_reset_internal_state'] );
	}

	/**
	 * Tests that get_subscribed_events() does NOT include the imagify_settings_tools hook.
	 *
	 * The settings section is rendered directly via print_template() — no hook needed.
	 */
	public function testDoesNotContainSettingsToolsHook(): void {
		$events = Subscriber::get_subscribed_events();

		$this->assertArrayNotHasKey( 'imagify_settings_tools', $events );
	}

	/**
	 * Tests that get_subscribed_events() returns exactly one event entry.
	 */
	public function testReturnsExactlyOneEvent(): void {
		$events = Subscriber::get_subscribed_events();

		$this->assertCount( 1, $events );
	}
}
