<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Renew a dismissed Imagify notice.
 *
 * @since 1.0
 *
 * @param  string $notice  A notice ID.
 * @param  int    $user_id A user ID.
 * @return void
 */
function imagify_renew_notice( $notice, $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	$notices = get_user_meta( $user_id, '_imagify_ignore_notices', true );
	$notices = $notices && is_array( $notices ) ? array_flip( $notices ) : array();

	if ( ! isset( $notices[ $notice ] ) ) {
		return;
	}

	unset( $notices[ $notice ] );
	$notices = array_flip( $notices );
	$notices = array_filter( $notices );
	$notices = array_values( $notices );

	update_user_meta( $user_id, '_imagify_ignore_notices', $notices );
}

/**
 * Dismiss an Imagify notice.
 *
 * @since 1.0
 *
 * @param  string $notice  A notice ID.
 * @param  int    $user_id A user ID.
 * @return void
 */
function imagify_dismiss_notice( $notice, $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	$notices = get_user_meta( $user_id, '_imagify_ignore_notices', true );
	$notices = $notices && is_array( $notices ) ? array_flip( $notices ) : array();

	if ( isset( $notices[ $notice ] ) ) {
		return;
	}

	$notices[ $notice ] = 1;
	$notices = array_flip( $notices );
	$notices = array_filter( $notices );
	$notices = array_values( $notices );

	update_user_meta( $user_id, '_imagify_ignore_notices', $notices );
}

/**
 * Tell if an Imagify notice is dismissed.
 *
 * @since  1.6.5
 * @author Grégory Viguier
 *
 * @param  string $notice  A notice ID.
 * @param  int    $user_id A user ID.
 * @return bool
 */
function imagify_notice_is_dismissed( $notice, $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	$notices = get_user_meta( $user_id, '_imagify_ignore_notices', true );
	$notices = $notices && is_array( $notices ) ? array_flip( $notices ) : array();

	return isset( $notices[ $notice ] );
}
