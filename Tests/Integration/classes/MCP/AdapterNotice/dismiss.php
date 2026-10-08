<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\MCP\AdapterNotice;

use Imagify\Notices\Notices;
use WPMedia\PHPUnit\Integration\AjaxTestCase;

/**
 * Tests that the shared Imagify dismiss endpoint handles the MCP Adapter notice.
 *
 * @covers \Imagify\Notices\Notices::admin_post_dismiss_notice
 * @covers \Imagify\Notices\Notices::dismiss_notice
 * @covers \Imagify\Notices\Notices::notice_is_dismissed
 *
 * @uses   imagify_check_nonce()
 * @uses   imagify_die()
 *
 * @group  MCP
 */
class Test_Dismiss extends AjaxTestCase {

	/**
	 * AJAX action handled by the dismiss endpoint, shared with the `admin-post.php` link.
	 *
	 * @var string
	 */
	protected $action = 'imagify_dismiss_notice';

	/**
	 * Sets up the dismiss hooks, which are only registered in the admin.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		Notices::get_instance()->init();
	}

	/**
	 * Cleans up the request superglobals.
	 *
	 * @return void
	 */
	public function tear_down() {
		unset( $_GET['notice'], $_GET['_wpnonce'], $_REQUEST['notice'], $_REQUEST['_wpnonce'] );

		parent::tear_down();
	}

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
	 * Tests that the dismiss link stores the notice for the clicking user only.
	 */
	public function testDismissLinkStoresNoticeForCurrentUserOnly(): void {
		$user_id    = $this->actAs( 'administrator' );
		$other_user = self::factory()->user->create( [ 'role' => 'administrator' ] );

		$this->prepareRequest( 'mcp-adapter', wp_create_nonce( Notices::DISMISS_NONCE_ACTION ) );

		$response = $this->callAjaxAction();

		$this->assertTrue( $response->success );
		$this->assertContains( 'mcp-adapter', get_user_meta( $user_id, Notices::DISMISS_META_NAME, true ) );
		$this->assertSame( '', get_user_meta( $other_user, Notices::DISMISS_META_NAME, true ) );
	}

	/**
	 * Tests that an invalid nonce does not dismiss the notice.
	 */
	public function testInvalidNonceDoesNotDismissNotice(): void {
		$user_id = $this->actAs( 'administrator' );

		$this->prepareRequest( 'mcp-adapter', 'invalid-nonce' );

		$response = $this->callAjaxAction();

		$this->assertFalse( $response->success );
		$this->assertFalse( Notices::notice_is_dismissed( 'mcp-adapter', $user_id ) );
	}

	/**
	 * Tests that a user without the Imagify capability cannot dismiss the notice.
	 */
	public function testUserWithoutCapabilityDoesNotDismissNotice(): void {
		$user_id = $this->actAs( 'subscriber' );

		$this->prepareRequest( 'mcp-adapter', wp_create_nonce( Notices::DISMISS_NONCE_ACTION ) );

		$response = $this->callAjaxAction();

		$this->assertFalse( $response->success );
		$this->assertFalse( Notices::notice_is_dismissed( 'mcp-adapter', $user_id ) );
	}

	/**
	 * Creates a user with the given role and sets it as the current one.
	 *
	 * @param string $role User role.
	 * @return int User ID.
	 */
	private function actAs( string $role ): int {
		$user_id = self::factory()->user->create( [ 'role' => $role ] );

		wp_set_current_user( $user_id );

		return $user_id;
	}

	/**
	 * Builds the request the dismiss link makes.
	 *
	 * @param string $notice Notice identifier.
	 * @param string $nonce  Nonce sent along.
	 * @return void
	 */
	private function prepareRequest( string $notice, string $nonce ): void {
		$_GET['notice']   = $notice;
		$_GET['_wpnonce'] = $nonce;
	}
}
