<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_filter( 'http_request_args', 'imagify_siteground_change_user_agent', 10, 2 );
/**
 * Filter the arguments used in a HTTP request to change the User Agent for requests "to self".
 * SiteGround blocks (error 403) HTTP requests with a User-Agent containing the site's domain.
 * The problem is that, for a given site at https://example.com, WordPress uses the UA `WordPress/4.8.2; https://example.com`.
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
	$site_url = site_url( '/' );

	if ( false === strpos( $url, $site_url ) || false === strpos( $r['user-agent'], $_SERVER['HTTP_HOST'] ) ) {
		return $r;
	}

	if ( ! isset( $user_agent ) ) {
		$user_agent = wp_generate_password( 12, false );
	}

	$r['user-agent'] = $user_agent;

	return $r;
}
