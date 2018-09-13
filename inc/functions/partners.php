<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Get the partner ID stored in the database.
 *
 * @since  1.6.14
 * @author Grégory Viguier
 *
 * @return string|bool The partner ID. False otherwise.
 */
function imagify_get_partner() {
	if ( class_exists( 'Imagify_Partner' ) ) {
		return Imagify_Partner::get_stored_partner();
	}

	$partner = get_option( 'imagifyp_id' );

	if ( $partner && is_string( $partner ) ) {
		$partner = preg_replace( '@[^a-z0-9_-]@', '', strtolower( $partner ) );
	}

	return $partner ? $partner : false;
}

/**
 * Delete the partner ID stored in the database.
 *
 * @since  1.6.14
 * @author Grégory Viguier
 */
function imagify_delete_partner() {
	if ( class_exists( 'Imagify_Partner' ) ) {
		Imagify_Partner::delete_stored_partner();
	} elseif ( false !== get_option( 'imagifyp_id' ) ) {
		delete_option( 'imagifyp_id' );
	}
}
