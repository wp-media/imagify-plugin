<?php
declare(strict_types=1);

namespace Imagify\Tools;

use Imagify\EventManagement\SubscriberInterface;

/**
 * Registers the AJAX handler for the "Reset Internal State" tool.
 *
 * @since 2.3
 */
class Subscriber implements SubscriberInterface {

	/**
	 * Nonce action used to secure the AJAX request.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'imagify_reset_internal_state';

	/**
	 * AJAX action name.
	 *
	 * @var string
	 */
	const AJAX_ACTION = 'imagify_reset_internal_state';

	/**
	 * Service that performs the actual reset.
	 *
	 * @var ResetInternalState
	 */
	private $reset_service;

	/**
	 * Constructor.
	 *
	 * @param ResetInternalState $reset_service Reset service.
	 */
	public function __construct( ResetInternalState $reset_service ) {
		$this->reset_service = $reset_service;
	}

	/**
	 * Returns the list of WordPress hooks this subscriber listens to.
	 *
	 * @return array<string, string|array{string, int}>
	 */
	public static function get_subscribed_events(): array {
		return [
			'wp_ajax_' . self::AJAX_ACTION => 'handle_ajax',
		];
	}

	/**
	 * Handle the AJAX request to reset Imagify's internal state.
	 *
	 * Requires:
	 *   - Valid nonce (imagify_reset_internal_state).
	 *   - Current user must be able to manage options.
	 *
	 * @since 2.3
	 *
	 * @return void
	 */
	public function handle_ajax(): void {
		if ( ! check_ajax_referer( self::NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token. Please refresh the page and try again.', 'imagify' ) ], 403 );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Sorry, you are not allowed to do that.', 'imagify' ) ], 403 );
			return;
		}

		$this->reset_service->reset();

		wp_send_json_success( [ 'message' => __( 'Imagify internal state has been reset successfully.', 'imagify' ) ] );
	}
}
