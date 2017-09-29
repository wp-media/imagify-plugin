<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Get user capacity to operate Imagify.
 *
 * @since  1.6.5
 * @since  1.6.11 Uses a string as describer for the first argument.
 * @author Grégory Viguier
 *
 * @param  string $describer Capacity describer. Possible values are 'manage', 'bulk-optimize', 'manual-optimize', and 'auto-optimize'.
 * @return string
 */
function imagify_get_capacity( $describer = 'manage' ) {
	// Back compat.
	if ( ! is_string( $describer ) ) {
		if ( $describer || ! is_multisite() ) {
			$describer = 'bulk-optimize';
		} else {
			$describer = 'manage';
		}
	}

	switch ( $describer ) {
		case 'manage':
			$capacity = imagify_is_active_for_network() ? 'manage_network_options' : 'manage_options';
			break;
		case 'bulk-optimize':
			$capacity = 'manage_options';
			break;
		case 'manual-optimize':
		case 'auto-optimize':
			$capacity = 'upload_files';
			break;
		default:
			$capacity = $describer;
	}

	/**
	 * Filter the user capacity used to operate Imagify.
	 *
	 * @since 1.0
	 * @since 1.6.5  Added $force_mono parameter.
	 * @since 1.6.11 Replaced $force_mono by $describer.
	 *
	 * @param string $capacity  The user capacity.
	 * @param string $describer Capacity describer. Possible values are 'manage', 'bulk-optimize', 'manual-optimize', and 'auto-optimize'.
	 */
	return apply_filters( 'imagify_capacity', $capacity, $describer );
}

/**
 * Tell if the current user as the required capacity to operate Imagify.
 *
 * @since  1.6.11
 * @author Grégory Viguier
 *
 * @param  string $describer Capacity describer. Possible values are 'manage', 'bulk-optimize', 'manual-optimize', and 'auto-optimize'.
 * @param  int    $post_id   A post ID.
 * @return bool
 */
function imagify_current_user_can( $describer = 'manage', $post_id = null ) {
	return current_user_can( imagify_get_capacity( $describer ), $post_id );
}
