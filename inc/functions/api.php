<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Check if external requests are blocked for Imagify.
 *
 * @since 1.0
 *
 * return bool True if Imagify API can't be called
 */
function is_imagify_blocked() {
	if ( ! defined( 'WP_HTTP_BLOCK_EXTERNAL' ) || ! WP_HTTP_BLOCK_EXTERNAL ) {
		return false;
	}
	
	if ( defined( 'WP_ACCESSIBLE_HOSTS' ) ) {
		$accessible_hosts = explode( ',', WP_ACCESSIBLE_HOSTS );
		$accessible_hosts = array_map( 'trim', $accessible_hosts );
		
		if ( in_array( '*.imagify.io', $accessible_hosts ) ) {
			return false;	
		}
	}
	
	return true;
}

/**
 * Determine if the Imagify API is available by checking the API version.
 *
 * @since 1.0
 *
 * @return bool True if the Imagify API is available.
 */
function is_imagify_servers_up() {
	static $imagify_api_version = null;
	
	if ( null !== $imagify_api_version ) {
		return $imagify_api_version;
	}
	
	$transient_name       = 'imagify_check_api_version';
	$transient_expiration = 3 * MINUTE_IN_SECONDS;
	
	if ( get_site_transient( $transient_name ) ) {
		$imagify_api_version = true;
		return true;
	}
	
	if ( is_wp_error( get_imagify_api_version() ) ) {
		$imagify_api_version = false;
		set_site_transient( $transient_name, 0, $transient_expiration );
		
		return false;
	}
	
	$imagify_api_version = true;
	set_site_transient( $transient_name, 1, $transient_expiration );
	
	return true;
}