<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * A wrapper to easily get imagify option.
 *
 * @since 1.0
 *
 * @param  string $key     The option name.
 * @param  mixed  $default The default value of the option. Leave empty to use the internal default value (see Imagify_Options::$default_values).
 * @return mixed           The option value.
 */
function get_imagify_option( $key, $default = null ) {
	return Imagify_Options::get_instance()->get( $key, $default );
}

/**
 * Update an Imagify option.
 *
 * @since  1.6
 * @author Remy Perona
 *
 * @param string $key   The option name.
 * @param mixed  $value The value of the option.
 */
function update_imagify_option( $key, $value ) {
	Imagify_Options::get_instance()->set( $key, $value );
}

/**
 * Determine if the Imagify API key is valid.
 *
 * @since 1.0
 *
 * @return bool True if the API key is valid.
 */
function imagify_valid_key() {
	static $is_valid;

	if ( isset( $is_valid ) ) {
		return $is_valid;
	}

	if ( ! Imagify_Options::get_instance()->get( 'api_key' ) ) {
		$is_valid = false;
		return $is_valid;
	}

	if ( get_site_transient( 'imagify_check_licence_1' ) ) {
		$is_valid = true;
		return $is_valid;
	}

	if ( is_wp_error( get_imagify_user() ) ) {
		$is_valid = false;
		return $is_valid;
	}

	$is_valid = true;
	set_site_transient( 'imagify_check_licence_1', $is_valid, YEAR_IN_SECONDS );

	return $is_valid;
}
