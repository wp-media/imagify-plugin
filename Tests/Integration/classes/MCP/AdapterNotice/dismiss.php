<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\MCP\AdapterNotice;

use Imagify\Notices\Notices;
use WPMedia\PHPUnit\Integration\AjaxTestCase;

/**
 * Tests that the Imagify dismiss endpoint handles the MCP Adapter notice.
 *
 * The AJAX action shares its handler with the `admin-post.php` dismiss link.
 *
 * @covers \Imagify\Notices\Notices::admin_post_dismiss_notice
 * @group  MCP
 */
class Test_Dismiss extends AjaxTestCase {

	/**
	 * AJAX action.
	 *
	 * @var string
	 */
	protected $action = 'imagify_dismiss_notice';

	/**
	 * Registers the dismiss hooks, which are only added in the admin.
	 */
	public function set_up() {
		parent::set_up();

		Notices::get_instance()->init();
	}

	/**
	 * Cleans up the request superglobals.
	 */
	public function tear_down() {
		unset( $_GET['notice'], $_GET['_wpnonce'], $_REQUEST['notice'], $_REQUEST['_wpnonce'] );

		parent::tear_down();
	}

	/**
	 * Tests the dismiss request for the MCP Adapter notice.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   User role and nonce validity.
	 * @param array $expected Response success and dismissal state.
	 */
	public function testShouldDismissExpected( $config, $expected ): void {
		$user_id    = self::factory()->user->create( [ 'role' => $config['role'] ] );
		$other_user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$_GET['notice']   = 'mcp-adapter';
		$_GET['_wpnonce'] = $config['valid_nonce'] ? wp_create_nonce( Notices::DISMISS_NONCE_ACTION ) : 'invalid-nonce';

		$response = $this->callAjaxAction();

		$this->assertSame( $expected['success'], $response->success );
		$this->assertSame( $expected['dismissed'], Notices::notice_is_dismissed( 'mcp-adapter', $user_id ) );
		$this->assertFalse( Notices::notice_is_dismissed( 'mcp-adapter', $other_user ) );
	}
}
