<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\MCP\AdapterNotice;

use Imagify\Notices\Notices;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests for \Imagify\MCP\AdapterNotice::display(), hooked on `admin_notices`.
 *
 * @covers \Imagify\MCP\AdapterNotice::display
 * @covers \Imagify\MCP\AdapterNotice::should_display
 * @group  MCP
 */
class Test_Display extends TestCase {

	/**
	 * Whether to use the Imagify API for these tests.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Sets up the environment: the notice is meaningless when an adapter is loaded or without the Abilities API.
	 */
	public function set_up() {
		parent::set_up();

		if ( class_exists( 'WP\MCP\Core\McpAdapter' ) ) {
			$this->markTestSkipped( 'An MCP Adapter is loaded, so the migration notice is never displayed.' );
		}

		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'The Abilities API (WordPress 6.9+) is not available.' );
		}
	}

	/**
	 * Tests that an administrator holding MCP Adapter sessions sees the notice.
	 */
	public function testShouldDisplayNoticeForAdministratorWithSessions(): void {
		$user_id = $this->createUser( 'administrator' );
		update_user_meta( $user_id, 'mcp_adapter_sessions', [ 'session-1' ] );

		$output = $this->getNoticesOutput();

		$this->assertStringContainsString(
			'Imagify no longer bundles the MCP Adapter, which is now available as a standalone plugin on WordPress.org. To keep using MCP with Imagify, please install and activate the',
			$output
		);
		$this->assertStringContainsString( 'plugin-install.php?s=mcp-adapter&#038;tab=search&#038;type=term', $output );
		$this->assertStringContainsString( 'notice=mcp-adapter', $output );
		$this->assertStringContainsString( 'imagify_dismiss_notice', $output );
	}

	/**
	 * Tests that an OAuth refresh token alone is enough to see the notice.
	 */
	public function testShouldDisplayNoticeForAdministratorWithOnlyAnOAuthRefreshToken(): void {
		$user_id = $this->createUser( 'administrator' );
		update_user_meta( $user_id, 'mcp_refresh_jti_abc123', 'x' );

		$this->assertStringContainsString( 'Imagify no longer bundles the MCP Adapter', $this->getNoticesOutput() );
	}

	/**
	 * Tests that an administrator who never used MCP sees nothing.
	 */
	public function testShouldNotDisplayNoticeWithoutMcpUsage(): void {
		$this->createUser( 'administrator' );

		$this->assertStringNotContainsString( 'MCP Adapter', $this->getNoticesOutput() );
	}

	/**
	 * Tests that a user who cannot install plugins sees nothing.
	 */
	public function testShouldNotDisplayNoticeForEditor(): void {
		$user_id = $this->createUser( 'editor' );
		update_user_meta( $user_id, 'mcp_adapter_sessions', [ 'session-1' ] );

		$this->assertStringNotContainsString( 'MCP Adapter', $this->getNoticesOutput() );
	}

	/**
	 * Tests that a dismissed notice stays hidden.
	 */
	public function testShouldNotDisplayNoticeOnceDismissed(): void {
		$user_id = $this->createUser( 'administrator' );
		update_user_meta( $user_id, 'mcp_adapter_sessions', [ 'session-1' ] );
		Notices::dismiss_notice( 'mcp-adapter', $user_id );

		$this->assertStringNotContainsString( 'MCP Adapter', $this->getNoticesOutput() );
	}

	/**
	 * Creates a user and sets it as the current one.
	 *
	 * @param string $role User role.
	 * @return int User ID.
	 */
	private function createUser( string $role ): int {
		$user_id = self::factory()->user->create( [ 'role' => $role ] );

		wp_set_current_user( $user_id );

		return $user_id;
	}

	/**
	 * Returns what the `admin_notices` action prints for the current user.
	 *
	 * @return string
	 */
	private function getNoticesOutput(): string {
		ob_start();
		do_action( 'admin_notices' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.

		return (string) ob_get_clean();
	}
}
