<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\MCP\AdapterNotice;

use Imagify\MCP\AdapterNotice;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\MCP\AdapterNotice::get_subscribed_events().
 *
 * @covers \Imagify\MCP\AdapterNotice::get_subscribed_events
 * @group  MCP
 */
class Test_GetSubscribedEvents extends TestCase {

	/**
	 * Tests that the notice is only hooked on admin_notices.
	 */
	public function testReturnsAdminNoticesMapping(): void {
		$this->assertSame(
			[ 'admin_notices' => 'display' ],
			AdapterNotice::get_subscribed_events()
		);
	}
}
