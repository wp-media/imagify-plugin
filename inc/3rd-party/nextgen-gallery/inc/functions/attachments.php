<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Get the backup path of a specific attachement.
 *
 * @since 1.6.8
 * @author Grégory Viguier
 *
 * @param  string $file_path The file path.
 * @return string|bool       The backup path. False on failure.
 */
function get_imagify_ngg_attachment_backup_path( $file_path ) {
	$file_path = wp_normalize_path( (string) $file_path );

	if ( ! $file_path ) {
		return false;
	}

	return $file_path . '_backup';
}
