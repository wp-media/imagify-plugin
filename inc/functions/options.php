<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * A wrapper to easily get imagify option.
 *
 * @since 1.0
 * @since 1.7 The $default parameter was removed.
 *
 * @param  string $key The option name.
 * @return mixed       The option value.
 */
function get_imagify_option( $key ) {
	return Imagify_Options::get_instance()->get( $key );
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
 * Autoload network options and put them in cache.
 *
 * @since  1.7
 * @author Grégory Viguier
 * @source SecuPress
 *
 * @param array        $option_names A list of option names to cache.
 * @param string|array $prefixes     A prefix for the option names. Handy for transients for example (`_site_transient_` and `_site_transient_timeout_`).
 */
function imagify_load_network_options( $option_names, $prefixes = '' ) {
	global $wpdb;

	$prefixes = (array) $prefixes;

	if ( ! $option_names || count( $option_names ) * count( $prefixes ) === 1 ) {
		return;
	}

	// Get values.
	$not_exist    = array();
	$option_names = array_flip( array_flip( $option_names ) );
	$options      = '';

	foreach ( $prefixes as $prefix ) {
		$options .= "'$prefix" . implode( "','$prefix", esc_sql( $option_names ) ) . "',";
	}

	$options = rtrim( $options, ',' );

	if ( is_multisite() ) {
		$network_id     = function_exists( 'get_current_network_id' ) ? get_current_network_id() : (int) $wpdb->siteid;
		$cache_prefix   = "$network_id:";
		$notoptions_key = "$network_id:notoptions";
		$cache_group    = 'site-options';
		$results        = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key as name, meta_value as value FROM $wpdb->sitemeta WHERE meta_key IN ( $options ) AND site_id = %d", $network_id ), OBJECT_K ); // WPCS: unprepared SQL ok.
	} else {
		$cache_prefix   = '';
		$notoptions_key = 'notoptions';
		$cache_group    = 'options';
		$results        = $wpdb->get_results( "SELECT option_name as name, option_value as value FROM $wpdb->options WHERE option_name IN ( $options )", OBJECT_K ); // WPCS: unprepared SQL ok.
	}

	foreach ( $prefixes as $prefix ) {
		foreach ( $option_names as $option_name ) {
			$option_name = $prefix . $option_name;

			if ( isset( $results[ $option_name ] ) ) {
				// Cache the value.
				$value = $results[ $option_name ]->value;
				$value = maybe_unserialize( $value );
				wp_cache_set( "$cache_prefix$option_name", $value, $cache_group );
			} else {
				// No value.
				$not_exist[ $option_name ] = true;
			}
		}
	}

	if ( ! $not_exist ) {
		return;
	}

	// Cache the options that don't exist in the DB.
	$notoptions = wp_cache_get( $notoptions_key, $cache_group );
	$notoptions = is_array( $notoptions ) ? $notoptions : array();
	$notoptions = array_merge( $notoptions, $not_exist );

	wp_cache_set( $notoptions_key, $notoptions, $cache_group );
}
