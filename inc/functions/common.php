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
	static $edit_attachment_cap;

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
		case 'optimize':
			// This is a generic capacity: don't use it unless you have no other choices!
			if ( ! isset( $edit_attachment_cap ) ) {
				$edit_attachment_cap = get_post_type_object( 'attachment' );
				$edit_attachment_cap = $edit_attachment_cap ? $edit_attachment_cap->cap->edit_posts : 'edit_posts';
			}

			$capacity = $edit_attachment_cap;
			break;
		case 'manual-optimize':
			// Must be used with an Attachment ID.
			$capacity = 'edit_post';
			break;
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
 * Tell if the current user has the required ability to operate Imagify.
 *
 * @since  1.6.11
 * @author Grégory Viguier
 *
 * @param  string $describer Capacity describer. Possible values are 'manage', 'bulk-optimize', 'manual-optimize', and 'auto-optimize'.
 * @param  int    $post_id   A post ID (a gallery ID for NGG).
 * @return bool
 */
function imagify_current_user_can( $describer = 'manage', $post_id = null ) {
	static $can_upload;

	$post_id  = $post_id ? $post_id : null;
	$capacity = imagify_get_capacity( $describer );
	$user_can = false;

	if ( 'manage' !== $describer && 'bulk-optimize' !== $describer ) {
		// Describers that are not 'manage' and 'bulk-optimize' need an additional test for 'upload_files'.
		if ( ! isset( $can_upload ) ) {
			$can_upload = current_user_can( 'upload_files' );
		}

		if ( $can_upload ) {
			if ( 'upload_files' === $capacity ) {
				// We already know it's true.
				$user_can = true;
			} else {
				$user_can = current_user_can( $capacity, $post_id );
			}
		}
	} else {
		$user_can = current_user_can( $capacity );
	}

	/**
	 * Filter the current user ability to operate Imagify.
	 *
	 * @since 1.6.11
	 *
	 * @param bool   $user_can  Tell if the current user has the required ability to operate Imagify.
	 * @param string $capacity  The user capacity.
	 * @param string $describer Capacity describer. Possible values are 'manage', 'bulk-optimize', 'manual-optimize', and 'auto-optimize'.
	 * @param int    $post_id   A post ID (a gallery ID for NGG).
	 */
	return apply_filters( 'imagify_current_user_can', $user_can, $capacity, $describer, $post_id );
}

/**
 * Sanitize an optimization context.
 *
 * @since  1.6.11
 * @author Grégory Viguier
 *
 * @param  string $context The context.
 * @return string
 */
function imagify_sanitize_context( $context ) {
	$context = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $context );
	return $context ? $context : 'wp';
}
