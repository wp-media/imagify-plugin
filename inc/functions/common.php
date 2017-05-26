<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Get user capacity to operate Imagify.
 *
 * @since 1.6.5
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
