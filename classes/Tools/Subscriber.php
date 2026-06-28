<?php
declare(strict_types=1);

namespace Imagify\Tools;

use Imagify\EventManagement\SubscriberInterface;

/**
 * Registers the AJAX action for the one-click internal-state reset.
 */
class Subscriber implements SubscriberInterface {

	/**
	 * ResetInternalState service.
	 *
	 * @var ResetInternalState
	 */
	private $reset;

	/**
	 * Constructor.
	 *
	 * @param ResetInternalState $reset ResetInternalState service.
	 */
	public function __construct( ResetInternalState $reset ) {
		$this->reset = $reset;
	}

	/**
	 * Returns the events this subscriber listens to.
	 *
	 * Note: the settings-section template is rendered directly via print_template()
	 * in page-settings.php — no imagify_settings_tools hook is registered here.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			// @action
			'wp_ajax_imagify_reset_internal_state' => 'reset_internal_state',
		];
	}

	/**
	 * Handles the AJAX request to reset Imagify internal state.
	 *
	 * Verifies the nonce and capability before delegating to ResetInternalState.
	 *
	 * @return void
	 */
	public function reset_internal_state(): void {
		imagify_check_nonce( 'imagify_reset_internal_state' );

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			imagify_die();
			return;
		}

		$this->reset->reset();

		wp_send_json_success( [ 'message' => __( 'Imagify internal state has been reset successfully.', 'imagify' ) ] );
	}
}
