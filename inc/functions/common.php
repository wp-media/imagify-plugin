<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Get user capacity to operate Imagify.
 *
 * @since  1.6.5
 * @author Grégory Viguier
 *
 * @param  bool $force_mono Force capacity for mono-site.
 * @return string
 */
function imagify_get_capacity( $force_mono = false ) {
	if ( $force_mono || ! is_multisite() ) {
		$capacity = 'manage_options';
	} else {
		$capacity = imagify_is_active_for_network() ? 'manage_network_options' : 'manage_options';
	}

	/**
	 * Filter the user capacity used to operate Imagify.
	 *
	 * @since 1.0
	 * @since 1.6.5 Added $force_mono parameter.
	 *
	 * @param string $capacity   The user capacity.
	 * @param bool   $force_mono Force capacity for mono-site.
	 */
	return apply_filters( 'imagify_capacity', $capacity, $force_mono );
}

/**
 * Check for nonce.
 *
 * @since  1.6.10
 * @author Grégory Viguier
 *
 * @param string      $action Action nonce.
 * @param string|bool $query_arg Optional. Key to check for the nonce in `$_REQUEST`. If false, `$_REQUEST` values will be evaluated for '_ajax_nonce', and '_wpnonce' (in that order). Default false.
 */
function imagify_check_nonce( $action, $query_arg = false ) {
	if ( ! check_ajax_referer( $action, $query_arg, false ) ) {
		imagify_die();
	}
}

/**
 * Check for user capacity.
 *
 * @since  1.6.10
 * @author Grégory Viguier
 *
 * @param string $capacity a user capacity.
 */
function imagify_check_user_capacity( $capacity = null ) {
	if ( ! isset( $capacity ) ) {
		$capacity = imagify_get_capacity();
	}

	if ( ! current_user_can( $capacity ) ) {
		imagify_die();
	}
}

/**
 * Die Today.
 *
 * @since  1.6.10
 * @author Grégory Viguier
 *
 * @param string $message A message to display.
 */
function imagify_die( $message = null ) {
	if ( ! isset( $message ) ) {
		/* translators: This sentense already exists in WordPress. */
		$message = __( 'Sorry, you are not allowed to do that.', 'imagify' );
	} elseif ( is_wp_error( $response ) ) {
		$message = imagify_translate_api_message( $message->get_error_message() );
	}

	if ( is_array( $message ) ) {
		if ( ! empty( $message['error'] ) ) {
			$message['error']  = imagify_translate_api_message( $message['error'] );
		} elseif ( ! empty( $message['detail'] ) ) {
			$message['detail'] = imagify_translate_api_message( $message['detail'] );
		}
	}

	if ( defined( 'DOING_AJAX' ) ) {
		wp_send_json_error( $message );
	}

	if ( is_array( $message ) ) {
		if ( ! empty( $message['error'] ) ) {
			$message = $message['error'];
		} elseif ( ! empty( $message['detail'] ) ) {
			$message = $message['detail'];
		} else {
			$message = reset( $message );
		}
	}

	if ( wp_get_referer() ) {
		$message .= '</p><p>';
		$message .= sprintf( '<a href="%s">%s</a>',
			esc_url( remove_query_arg( 'updated', wp_get_referer() ) ),
			/* translators: This sentense already exists in WordPress. */
			__( 'Go back', 'imagify' )
		);
	}

	/* translators: %s is the plugin name. */
	wp_die( $message, sprintf( __( '%s Failure Notice', 'imagify' ), 'Imagify' ), 403 );
}

/**
 * Redirect if not an ajax request.
 *
 * @since  1.6.10
 * @author Grégory Viguier
 *
 * @param string       $message     A message to display in an admin notice once redirected.
 * @param array|string $args_or_url An array of query args to add to the redirection URL. If a string, the complete URL.
 */
function imagify_maybe_redirect( $message = false, $args_or_url = array() ) {
	if ( defined( 'DOING_AJAX' ) ) {
		return;
	}

	if ( $args_or_url && is_array( $args_or_url ) ) {
		$redirect = add_query_arg( $args_or_url, wp_get_referer() );
	} elseif ( $args_or_url && is_string( $args_or_url ) ) {
		$redirect = $args_or_url;
	} else {
		$redirect = wp_get_referer();
	}

	/**
	 * Filter the URL to redirect to.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @param string $redirect The URL to redirect to.
	 */
	$redirect = apply_filters( 'imagify_redirect_to', $redirect );

	wp_safe_redirect( esc_url_raw( $redirect ) );
	die();
}
