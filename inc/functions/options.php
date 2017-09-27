<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * A wrapper to easily get imagify option.
 *
 * @since 1.0
 *
 * @param  string $option  The option name.
 * @param  bool   $default The default value of option.
 * @return mixed  The option value.
 */
function get_imagify_option( $option, $default = false ) {
	static $basename;
	/**
	 * Pre-filter any Imagify option before read.
	 *
	 * @since 1.0
	 *
	 * @param mixed $value   Value to return instead of the option value. Default null to skip it.
	 * @param mixed $default The default value. Default false.
	 */
	$value = apply_filters( 'pre_get_imagify_option_' . $option, null, $default );

	if ( isset( $value ) ) {
		return $value;
	}

	if ( ! isset( $basename ) ) {
		$basename = plugin_basename( IMAGIFY_FILE );
	}

	$options = imagify_is_active_for_network() ? get_site_option( IMAGIFY_SETTINGS_SLUG ) : get_option( IMAGIFY_SETTINGS_SLUG );
	$value   = isset( $options[ $option ] ) ? $options[ $option ] : $default;

	if ( 'api_key' === $option && defined( 'IMAGIFY_API_KEY' ) && IMAGIFY_API_KEY ) {
		$value = IMAGIFY_API_KEY;
	}

	/**
	 * Filter any Imagify option after read.
	 *
	 * @since 1.0
	 *
	 * @param mixed $value   Value of the option.
	 * @param mixed $default The default value. Default false.
	*/
	return apply_filters( 'get_imagify_option_' . $option, $value, $default );
}

/**
 * Update an Imagify option.
 *
 * @since  1.6
 * @author Remy Perona
 *
 * @param  string $key    The option name.
 * @param  string $value  The value of the option.
 * @return void
 */
function update_imagify_option( $key, $value ) {
	$options = get_option( IMAGIFY_SETTINGS_SLUG );
	$options = is_array( $options ) ? $options : array();

	$options[ $key ] = $value;

	update_option( IMAGIFY_SETTINGS_SLUG, $options );
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

	if ( ! get_imagify_option( 'api_key' ) ) {
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
