<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\MCP\AdapterNotice;

use Imagify\Notices\Notices;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests that the shared Imagify dismiss mechanism handles the MCP Adapter notice.
 *
 * @covers \Imagify\Notices\Notices::dismiss_notice
 * @covers \Imagify\Notices\Notices::notice_is_dismissed
 * @group  MCP
 */
class Test_Dismiss extends TestCase {

	/**
	 * Whether to use the Imagify API for these tests.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Tests that the dismissal is stored per user.
	 */
	public function testDismissIsStoredPerUser(): void {
		$dismissed_by = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$other_user   = self::factory()->user->create( [ 'role' => 'administrator' ] );

		$this->assertFalse( Notices::notice_is_dismissed( 'mcp-adapter', $dismissed_by ) );

		Notices::dismiss_notice( 'mcp-adapter', $dismissed_by );

		$this->assertTrue( Notices::notice_is_dismissed( 'mcp-adapter', $dismissed_by ) );
		$this->assertFalse( Notices::notice_is_dismissed( 'mcp-adapter', $other_user ) );
	}

	/**
	 * Tests that the dismiss endpoint accepts the notice identifier.
	 */
	public function testNoticeIdIsAcceptedByTheDismissEndpoint(): void {
		$method = $this->get_reflective_method( 'get_notice_ids', Notices::class );

		$this->assertContains( 'mcp-adapter', $method->invoke( Notices::get_instance() ) );
	}
}
