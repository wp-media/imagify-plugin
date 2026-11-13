<?php
declare(strict_types=1);

namespace Imagify\Traits;

/**
 * Reads sanitized text parameters from the submitted request.
 *
 * Nonce verification is the caller's job: AJAX callbacks call imagify_check_nonce(), and
 * some of them build the nonce action from the very values read here (media ID, context).
 *
 * @since 2.3.6
 */
trait RequestParamTrait {
	/**
	 * Get a sanitized text parameter from the submitted request.
	 *
	 * @since 2.3.6
	 *
	 * @param string $method    The method used: 'GET', or 'POST'.
	 * @param string $parameter The name of the parameter to look for.
	 *
	 * @return string An empty string if the parameter is missing or not a string.
	 */
	private function get_request_param( string $method, string $parameter ): string {
		if ( 'POST' === $method ) {
			return isset( $_POST[ $parameter ] ) ? sanitize_text_field( wp_unslash( $_POST[ $parameter ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by the calling AJAX callback.
		}

		return isset( $_GET[ $parameter ] ) ? sanitize_text_field( wp_unslash( $_GET[ $parameter ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified by the calling AJAX callback.
	}
}
