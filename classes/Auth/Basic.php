<?php
namespace Imagify\Auth;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that allows the use of Basic Auth for internal requests.
 * If this doesn’t work automatically, define the constants IMAGIFY_AUTH_USER and IMAGIFY_AUTH_PASSWORD.
 *
 * @since  1.9.5
 * @author Grégory Viguier
 */
class Basic {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * Class init: launch hooks.
	 *
	 * @since  1.9.5
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		add_filter( 'imagify_background_process_url', [ $this, 'get_auth_url' ] );
		add_filter( 'imagify_async_job_url',          [ $this, 'get_auth_url' ] );
		add_filter( 'imagify_internal_request_url',   [ $this, 'get_auth_url' ] );
		add_filter( 'cron_request',                   [ $this, 'cron_request_args' ] );
	}

	/**
	 * If the site uses basic authentication, add the required user and password to the given URL.
	 *
	 * @since  1.9.5
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $url An URL.
	 * @return string
	 */
	public function get_auth_url( $url ) {
		if ( ! $url || ! is_string( $url ) ) {
			// Invalid.
			return '';
		}

		if ( preg_match( '%.+?//(.+?):(.+?)@%', $url ) ) {
			// Credentials already in the URL.
			return $url;
		}

		if ( defined( 'IMAGIFY_AUTH_USER' ) && defined( 'IMAGIFY_AUTH_PASSWORD' ) && IMAGIFY_AUTH_USER && IMAGIFY_AUTH_PASSWORD ) {
			$user = IMAGIFY_AUTH_USER;
			$pass = IMAGIFY_AUTH_PASSWORD;
		} else {
			$auth_type = ! empty( $_SERVER['AUTH_TYPE'] ) ? strtolower( wp_unslash( $_SERVER['AUTH_TYPE'] ) ) : '';

			if ( 'basic' === $auth_type && ! empty( $_SERVER['PHP_AUTH_USER'] ) && ! empty( $_SERVER['PHP_AUTH_PW'] ) ) {
				$user = sanitize_text_field( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) );
				$pass = sanitize_text_field( wp_unslash( $_SERVER['PHP_AUTH_PW'] ) );
			}
		}

		if ( empty( $user ) || empty( $pass ) ) {
			// No credentials.
			return $url;
		}

		return preg_replace( '%^(.+?//)(.+?)$%', '$1' . rawurlencode( $user ) . ':' . rawurlencode( $pass ) . '@$2', $url );
	}

	/**
	 * If the site uses basic authentication, add the required user and password to the given URL.
	 *
	 * @since  1.9.5
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $args {
	 *     An array of cron request URL arguments.
	 *
	 *     @type string $url  The cron request URL.
	 *     @type int    $key  The 22 digit GMT microtime.
	 *     @type array  $args {
	 *         An array of cron request arguments.
	 *
	 *         @type int  $timeout   The request timeout in seconds. Default .01 seconds.
	 *         @type bool $blocking  Whether to set blocking for the request. Default false.
	 *         @type bool $sslverify Whether SSL should be verified for the request. Default false.
	 *     }
	 * }
	 * @return array
	 */
	public function cron_request_args( $args ) {
		if ( ! empty( $args['url'] ) ) {
			$args['url'] = $this->get_auth_url( $args['url'] );
		}

		return $args;
	}
}
