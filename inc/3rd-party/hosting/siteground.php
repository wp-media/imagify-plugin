<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_filter( 'http_request_args', 'imagify_siteground_change_user_agent', 10, 2 );
/**
 * Filter the arguments used in a HTTP request to change the User Agent for requests "to self", to prevent firewalls to be triggered.
 * The User-Agent is randomized on SiteGround only, other hosts keep the request's own User-Agent unless a filter changes it.
 *
 * @since  1.6.13
 * @author Grégory Viguier
 *
 * @param  array  $r   An array of HTTP request arguments.
 * @param  string $url The request URL.
 * @return array
 */
function imagify_siteground_change_user_agent( $r, $url ) {
	$url = wp_parse_url( $url );

	if ( ! is_array( $url ) || empty( $url['path'] ) || empty( $url['host'] ) ) {
		return $r;
	}

	$paths = [
		'/wp-admin/admin-ajax.php',
		'/wp-admin/admin-post.php',
		'/wp-cron.php',
	];

	$site_url = wp_parse_url( site_url( '/' ) );

	if ( ! is_array( $site_url ) || empty( $site_url['host'] ) || empty( $site_url['path'] ) ) {
		return $r;
	}

	// Limit to requests to self.
	if ( strtolower( $url['host'] ) !== strtolower( $site_url['host'] ) ) {
		return $r;
	}

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

	static $siteground_user_agent;

	$user_agent = isset( $r['user-agent'] ) ? $r['user-agent'] : '';

	// Randomize the User-Agent on SiteGround.
	if ( _imagify_is_siteground() ) {
		if ( ! isset( $siteground_user_agent ) ) {
			$siteground_user_agent = wp_generate_password( 12, false );
		}

		$user_agent = $siteground_user_agent;
	}

	/**
	 * Filter the User-Agent used for requests "to self".
	 * Off SiteGround, the default value is the User-Agent of the request.
	 *
	 * @since  1.7.1
	 * @author Grégory Viguier
	 *
	 * @param string $user_agent The User-Agent.
	 * @param array  $r          An array of HTTP request arguments.
	 * @param array  $url        The request URL, parsed.
	 */
	$user_agent = apply_filters( 'imagify_user_agent_for_internal_requests', $user_agent, $r, $url );

	if ( ! is_string( $user_agent ) ) {
		return $r;
	}

	// Strip control characters to prevent header injection.
	$user_agent = trim( preg_replace( '/[\x00-\x1F\x7F]/', '', $user_agent ) );

	if ( '' !== $user_agent ) {
		$r['user-agent'] = $user_agent;
	}

	return $r;
}

/**
 * Tell if the site is hosted on SiteGround.
 * Both markers are server filesystem paths. Bail when open_basedir is set, since the root cannot be probed.
 *
 * @since 2.3.6
 *
 * @return bool
 */
function _imagify_is_siteground() {
	$open_basedir = ini_get( 'open_basedir' );
	$detected     = false;

	if ( empty( $open_basedir ) ) {
		$detected = file_exists( '/etc/yum.repos.d/baseos.repo' ) && file_exists( '/Z' );
	}

	/**
	 * Filter whether the site is hosted on SiteGround.
	 *
	 * @since 2.3.6
	 *
	 * @param bool $is_siteground True if SiteGround has been detected.
	 */
	return (bool) apply_filters( 'imagify_is_siteground', $detected );
}
