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
 * Tell if an attachment has a supported mime type.
 * Was previously Imagify_AS3CF::is_mime_type_supported() since 1.6.6.
 *
 * @since  1.6.8
 * @author Grégory Viguier
 *
 * @param  int $attachment_id The attachment ID.
 * @return bool
 */
function imagify_is_attachment_mime_type_supported( $attachment_id ) {
	static $is = array( false );

	$attachment_id = absint( $attachment_id );

	if ( isset( $is[ $attachment_id ] ) ) {
		return $is[ $attachment_id ];
	}

	$mime_types = get_imagify_mime_type();
	$mime_types = array_flip( $mime_types );
	$mime_type  = (string) get_post_mime_type( $attachment_id );

	$is[ $attachment_id ] = isset( $mime_types[ $mime_type ] );

	return $is[ $attachment_id ];
}

/**
 * Get the path to the backups directory.
 *
 * @since  1.6.8
 * @author Grégory Viguier
 *
 * @param  bool $bypass_error True to return the path even if there is an error. This is used when we want to display this path in a message for example.
 * @return string|bool        Path to the backups directory. False on failure.
 */
function get_imagify_backup_dir_path( $bypass_error = false ) {
	static $backup_dir;

	if ( isset( $backup_dir ) ) {
		return $backup_dir;
	}

	$upload_basedir = get_imagify_upload_basedir( $bypass_error );

	if ( ! $upload_basedir ) {
		return false;
	}

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

	return $backup_dir;
}

/**
 * Tell if the folder containing the backups is writable.
 *
 * @since  1.6.8
 * @author Grégory Viguier
 *
 * @return bool
 */
function imagify_backup_dir_is_writable() {
	if ( ! get_imagify_backup_dir_path() ) {
		return false;
	}

	$filesystem     = imagify_get_filesystem();
	$has_backup_dir = wp_mkdir_p( get_imagify_backup_dir_path() );

	return $has_backup_dir && $filesystem->is_writable( get_imagify_backup_dir_path() );
}

/**
 * Get the backup path of a specific attachement.
 *
 * @since 1.0
 *
 * @param  string $file_path The file path.
 * @return string|bool       The backup path. False on failure.
 */
function get_imagify_attachment_backup_path( $file_path ) {
	$file_path      = wp_normalize_path( (string) $file_path );
	$upload_basedir = get_imagify_upload_basedir();
	$backup_dir     = get_imagify_backup_dir_path();

	if ( ! $file_path || ! $upload_basedir ) {
		return false;
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
 * @param  string $file_path A relative or absolute file path.
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
 * @since  1.6.8 Added the $bypass_error parameter.
 * @author Grégory Viguier
 *
 * @param  bool $bypass_error True to return the path even if there is an error. This is used when we want to display this path in a message for example.
 * @return string|bool        The path. False on failure.
 */
function get_imagify_upload_basedir( $bypass_error = false ) {
	static $upload_basedir;
	static $upload_basedir_or_error;

	if ( isset( $upload_basedir ) ) {
		return $bypass_error ? $upload_basedir : $upload_basedir_or_error;
	}

	$uploads        = wp_upload_dir();
	$upload_basedir = trailingslashit( wp_normalize_path( $uploads['basedir'] ) );

	if ( false !== $uploads['error'] ) {
		$upload_basedir_or_error = false;
	} else {
		$upload_basedir_or_error = $upload_basedir;
	}

	return $bypass_error ? $upload_basedir : $upload_basedir_or_error;
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
