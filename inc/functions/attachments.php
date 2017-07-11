<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Get all mime type which could be optimized by Imagify.
 *
 * @since 1.3
 *
 * @return array $mime_type  The mime type.
 */
function get_imagify_mime_type() {
	return array(
		'image/jpeg',
		'image/png',
		'image/gif',
	);
}

/**
 * Get the backup path of a specific attachement.
 *
 * @since 1.0
 *
 * @param  int $file_path The file path.
 * @return string|bool    The backup path. False on failure.
 */
function get_imagify_attachment_backup_path( $file_path ) {
	static $backup_dir;

	$file_path      = wp_normalize_path( (string) $file_path );
	$upload_basedir = get_imagify_upload_basedir();

	if ( ! $file_path || ! $upload_basedir ) {
		return false;
	}

	if ( ! isset( $backup_dir ) ) {
		$backup_dir = $upload_basedir . 'backup/';

		/**
		 * Filter the backup directory path.
		 *
		 * @since 1.0
		 *
		 * @param string $backup_dir The backup directory path.
		*/
		$backup_dir = apply_filters( 'imagify_backup_directory', $backup_dir );
		$backup_dir = trailingslashit( wp_normalize_path( $backup_dir ) );
	}

	return str_replace( $upload_basedir, $backup_dir, $file_path );
}

/**
 * Retrieve file path for an attachment based on filename.
 *
 * @since 1.4.5
 *
 * @param  int $file_path The file path.
 * @return string|false   The file path to where the attached file should be, false otherwise.
 */
function get_imagify_attached_file( $file_path ) {
	$file_path      = wp_normalize_path( (string) $file_path );
	$upload_basedir = get_imagify_upload_basedir();

	if ( ! $file_path || ! $upload_basedir ) {
		return false;
	}

	// The file path is absolute.
	if ( strpos( $file_path, '/' ) === 0 || preg_match( '|^.:\\\|', $file_path ) ) {
		return false;
	}

	// Prepend upload dir.
	return $upload_basedir . $file_path;
}

/**
 * Retrieve the URL for an attachment based on file path.
 *
 * @since 1.4.5
 *
 * @param  string $file_path A relative of absolute file path.
 * @return string|bool       File URL, otherwise false.
 */
function get_imagify_attachment_url( $file_path ) {
	$file_path      = wp_normalize_path( (string) $file_path );
	$upload_basedir = get_imagify_upload_basedir();

	if ( ! $file_path || ! $upload_basedir ) {
		return false;
	}

	$upload_baseurl = get_imagify_upload_baseurl();

	// Check that the upload base exists in the (absolute) file location.
	if ( 0 === strpos( $file_path, $upload_basedir ) ) {
		// Replace file location with url location.
		return str_replace( $upload_basedir, $upload_baseurl, $file_path );
	}

	if ( false !== strpos( '/' . $file_path, '/wp-content/uploads/' ) ) {
		// Get the directory name relative to the basedir (back compat for pre-2.7 uploads).
		return trailingslashit( $upload_baseurl . _wp_get_attachment_relative_path( $file_path ) ) . basename( $file_path );
	}

	// It's a newly-uploaded file, therefore $file is relative to the basedir.
	return $upload_baseurl . $file_path;
}

/**
 * Get size information for all currently-registered thumbnail sizes.
 *
 * @since 1.5.10
 * @author Jonathan Buttigieg
 *
 * @return array Data for all currently-registered thumbnail sizes.
 */
function get_imagify_thumbnail_sizes() {
	global $_wp_additional_image_sizes, $wp_version;

	$sizes                        = array();
	$all_intermediate_image_sizes = get_intermediate_image_sizes();
	$intermediate_image_sizes     = apply_filters( 'image_size_names_choose', $all_intermediate_image_sizes );
	$all_intermediate_image_sizes = array_combine( $all_intermediate_image_sizes, $all_intermediate_image_sizes );
	$intermediate_image_sizes     = array_merge( $all_intermediate_image_sizes, $intermediate_image_sizes );
	$wp_image_sizes               = array( 'thumbnail' => 1, 'medium' => 1, 'large' => 1 );

	if ( version_compare( $wp_version, '4.4-beta3' ) >= 0 ) {
		$wp_image_sizes['medium_large'] = 1;
	}

	// Create the full array with sizes and crop info.
	foreach ( $intermediate_image_sizes as $size => $size_name ) {
		if ( isset( $wp_image_sizes[ $size ] ) && ! is_int( $size ) ) {
			$sizes[ $size ] = array(
				'width'  => get_option( $size . '_size_w' ),
				'height' => get_option( $size . '_size_h' ),
				'name'   => $size_name,
			);
		} elseif ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
			$sizes[ $size ] = array(
				'width'  => $_wp_additional_image_sizes[ $size ]['width'],
				'height' => $_wp_additional_image_sizes[ $size ]['height'],
				'name'   => $size_name,
			);
		}
	}

	return $sizes;
}

/**
 * A simple helper to get the upload basedir.
 *
 * @since  1.6.7
 * @author Grégory Viguier
 *
 * @return string|bool The path. False on failure.
 */
function get_imagify_upload_basedir() {
	static $upload_basedir;

	if ( isset( $upload_basedir ) ) {
		return $upload_basedir;
	}

	$uploads = wp_upload_dir();

	if ( false !== $uploads['error'] ) {
		$upload_basedir = false;
		return $upload_basedir;
	}

	$upload_basedir = trailingslashit( wp_normalize_path( $uploads['basedir'] ) );

	return $upload_basedir;
}

/**
 * A simple helper to get the upload baseurl.
 *
 * @since  1.6.7
 * @author Grégory Viguier
 *
 * @return string|bool The path. False on failure.
 */
function get_imagify_upload_baseurl() {
	static $upload_baseurl;

	if ( isset( $upload_baseurl ) ) {
		return $upload_baseurl;
	}

	$uploads = wp_upload_dir();

	if ( false !== $uploads['error'] ) {
		$upload_baseurl = false;
		return $upload_baseurl;
	}

	$upload_baseurl = trailingslashit( $uploads['baseurl'] );

	return $upload_baseurl;
}
