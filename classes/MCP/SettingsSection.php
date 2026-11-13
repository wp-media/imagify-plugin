<?php
declare(strict_types=1);

namespace Imagify\MCP;

use Imagify\EventManagement\SubscriberInterface;

/**
 * Adds the MCP option to the settings page.
 *
 * @since 2.3.6
 */
class SettingsSection implements SubscriberInterface {

	const DOC_URL = 'https://imagify.io/documentation/imagify-mcp/';

	/**
	 * Returns the events this subscriber listens to.
	 *
	 * @return array<string, array<int, int|string>>
	 */
	public static function get_subscribed_events(): array {
		return [
			// @action imagify_settings_after_lossless
			'imagify_settings_after_lossless' => [ 'render', 20 ],
		];
	}

	/**
	 * Prints the MCP option.
	 *
	 * @return void
	 */
	public function render(): void {
		// Abilities API comes with WordPress 6.9, MCP can not work without it.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$options     = \Imagify_Options::get_instance();
		$is_enabled  = (bool) $options->get( 'mcp' );
		$option_name = $options->get_option_name();
		$doc_url     = self::DOC_URL;

		include IMAGIFY_PATH . 'views/part-settings-mcp.php';
	}
}
