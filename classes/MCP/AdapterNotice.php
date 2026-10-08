<?php
declare(strict_types=1);

namespace Imagify\MCP;

use Imagify\EventManagement\SubscriberInterface;
use Imagify\Notices\Notices;

/**
 * Asks existing MCP users to install the standalone MCP Adapter plugin.
 *
 * Imagify no longer bundles the MCP Adapter. The notice is only shown to users
 * who have already used MCP, while no MCP Adapter is loaded. It is dismissed
 * through the shared Imagify dismiss endpoint (see `Notices::$notice_ids`).
 *
 * @since 2.3.5
 */
class AdapterNotice implements SubscriberInterface {

	/**
	 * Notice identifier, as registered in `Notices::$notice_ids`.
	 *
	 * @var string
	 */
	const NOTICE_ID = 'mcp-adapter';

	/**
	 * User meta key (legacy, network-global) holding the MCP Adapter sessions.
	 *
	 * @var string
	 */
	const SESSIONS_META_KEY = 'mcp_adapter_sessions';

	/**
	 * User meta key prefix of the OAuth refresh tokens issued to MCP clients.
	 *
	 * @var string
	 */
	const REFRESH_META_PREFIX = 'mcp_refresh_jti_';

	/**
	 * Returns the list of events this subscriber wants to listen to.
	 *
	 * @return array<string, string>
	 */
	public static function get_subscribed_events(): array {
		return [
			// @action admin_notices
			'admin_notices' => 'display',
		];
	}

	/**
	 * Prints the notice when it applies to the current user.
	 *
	 * @return void
	 */
	public function display(): void {
		if ( ! $this->should_display() ) {
			return;
		}

		$install_url = admin_url( 'plugin-install.php?s=mcp-adapter&tab=search&type=term' );
		$dismiss_url = get_imagify_admin_url( 'dismiss-notice', self::NOTICE_ID );

		include IMAGIFY_PATH . 'views/notice-mcp-adapter.php';
	}

	/**
	 * Tells whether the notice must be displayed to the current user.
	 *
	 * @return bool
	 */
	public function should_display(): bool {
		if ( ! $this->is_abilities_api_available() ) {
			return false;
		}

		if ( ! current_user_can( 'install_plugins' ) || ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			return false;
		}

		if ( $this->is_adapter_loaded() ) {
			return false;
		}

		$user_id = (int) get_current_user_id();

		if ( ! $user_id || ! $this->is_mcp_user( $user_id ) ) {
			return false;
		}

		return ! Notices::notice_is_dismissed( self::NOTICE_ID, $user_id );
	}

	/**
	 * Tells whether the WordPress Abilities API (WP 6.9+) is available.
	 *
	 * Checks the same functions as the MCP OAuth boot gate in `imagify_init()`.
	 *
	 * @return bool
	 */
	protected function is_abilities_api_available(): bool {
		return function_exists( 'wp_register_ability' )
			&& function_exists( 'wp_get_ability' )
			&& function_exists( 'wp_get_abilities' )
			&& function_exists( 'wp_register_ability_category' );
	}

	/**
	 * Tells whether an MCP Adapter is loaded, whichever plugin provides it.
	 *
	 * The class is referenced by string so no adapter symbol is needed here.
	 *
	 * @return bool
	 */
	protected function is_adapter_loaded(): bool {
		return class_exists( 'WP\MCP\Core\McpAdapter' );
	}

	/**
	 * Tells whether the user has already used MCP.
	 *
	 * A user has used MCP when they hold an OAuth refresh token, or a non-empty
	 * list of MCP Adapter sessions (legacy network-global key, or the one scoped
	 * to the current site).
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private function is_mcp_user( int $user_id ): bool {
		$meta = get_user_meta( $user_id );

		if ( ! is_array( $meta ) ) {
			return false;
		}

		$sessions_keys = [
			self::SESSIONS_META_KEY,
			self::SESSIONS_META_KEY . '_' . get_current_blog_id(),
		];

		foreach ( array_keys( $meta ) as $key ) {
			$key = (string) $key;

			if ( 0 === strpos( $key, self::REFRESH_META_PREFIX ) ) {
				return true;
			}

			if ( in_array( $key, $sessions_keys, true ) && ! empty( get_user_meta( $user_id, $key, true ) ) ) {
				return true;
			}
		}

		return false;
	}
}
