<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Run an async job to optimize images in background.
 *
 * @param array $body Contains the usual $_POST.
 *
 * @since 1.4
 */
function imagify_do_async_job( $body ) {
	$args = [
		'timeout'   => 0.01,
		'blocking'  => false,
		'body'      => $body,
		'cookies'   => isset( $_COOKIE ) && is_array( $_COOKIE ) ? $_COOKIE : [],
		'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
	];

	/**
	 * Filter the arguments used to launch an async job.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param array $args An array of arguments passed to wp_remote_post().
	 */
	$args = apply_filters( 'imagify_do_async_job_args', $args );

	/**
	 * It can be a XML-RPC request. The problem is that XML-RPC doesn't use cookies.
	 */
	if ( defined( 'XMLRPC_REQUEST' ) && get_current_user_id() ) {
		/**
		 * In old WP versions, the field "option_name" in the wp_options table was limited to 64 characters.
		 * From 64, remove 19 characters for "_transient_timeout_" = 45.
		 * Then remove 12 characters for "imagify_rpc_" (transient name) = 33.
		 * Luckily, a md5 is 32 characters long.
		 */
		$rpc_id = md5( maybe_serialize( $body ) );

		// Send the request to our RPC bridge instead.
		$args['body']['imagify_rpc_action'] = $args['body']['action'];
		$args['body']['action']             = 'imagify_rpc';
		$args['body']['imagify_rpc_id']     = $rpc_id;
		$args['body']['imagify_rpc_nonce']  = wp_create_nonce( 'imagify_rpc_' . $rpc_id );

		// Since we can't send cookies to keep the user logged in, store the user ID in a transient.
		set_transient( 'imagify_rpc_' . $rpc_id, get_current_user_id(), 30 );
	}

	$url = admin_url( 'admin-ajax.php' );

	/**
	 * Filter the URL to use for async jobs.
	 *
	 * @since  1.9.5
	 * @author Grégory Viguier
	 *
	 * @param string $url An URL.
	 * @param array  $args      An array of arguments passed to wp_remote_post().
	 */
	$url = apply_filters( 'imagify_async_job_url', $url, $args );

	wp_remote_post( $url, $args );
}
