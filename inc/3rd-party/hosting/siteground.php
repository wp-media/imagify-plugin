<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_filter( 'http_request_args', 'imagify_siteground_change_user_agent', 10, 2 );
/**
 * Filter the arguments used in a HTTP request to change the User Agent for requests "to self", to prevent firewalls to be triggered.
 *
 * @since  1.6.13
 * @author Grégory Viguier
 *
 * @param  array  $r   An array of HTTP request arguments.
 * @param  string $url The request URL.
 * @return array
 */
function imagify_siteground_change_user_agent( $r, $url ) {
	static $user_agent;
	static $site_url;

	$url = wp_parse_url( $url );

	if ( empty( $url['path'] ) ) {
		return $r;
	}

	$paths = array(
		'/wp-admin/admin-ajax.php',
		'/wp-admin/admin-post.php',
		'/wp-cron.php',
	);

	if ( ! isset( $site_url ) ) {
		$site_url = wp_parse_url( site_url( '/' ) );
	}

	// Limit to requests to self.
	if ( false === strpos( $url['path'], $site_url['path'] ) && false === strpos( $site_url['path'], $url['path'] ) ) {
		return $r;
	}

	// Limit to requests to admin-ajax.php, admin-post.php, and wp-cron.php.
	foreach ( $paths as $i => $path ) {
		if ( false !== strpos( $url['path'], $path ) ) {
			$paths = false;
			break;
		}
	}

	if ( $paths ) {
		return $r;
	}

	// Randomize the User-Agent.
	if ( ! isset( $user_agent ) ) {
		$user_agent = wp_generate_password( 12, false );
		/**
		 * Filter the User-Agent used for requests "to self".
		 *
		 * @since  1.7.1
		 * @author Grégory Viguier
		 *
		 * @param string $user_agent The User-Agent.
		 * @param array  $r          An array of HTTP request arguments.
		 * @param array  $url        The request URL, parsed.
		 */
		$user_agent = apply_filters( 'imagify_user_agent_for_internal_requests', $user_agent, $r, $url );
	}

	$r['user-agent'] = $user_agent;

	return $r;
}
