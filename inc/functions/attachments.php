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
 * Get a file mime type.
 *
 * @since  1.6.9
 * @author Grégory Viguier
 *
 * @param  string $file_path A file path (prefered) or a filename.
 * @return string|bool       A mime type. False on failure: the last test is limited to mime types supported by Imagify.
 */
function imagify_get_mime_type_from_file( $file_path ) {
	if ( function_exists( 'exif_imagetype' ) ) {
		$image_type = @exif_imagetype( $file_path );

		if ( false !== $image_type ) {
			return image_type_to_mime_type( $image_type );
		}
	}

	if ( function_exists( 'getimagesize' ) ) {
		$image_type = @getimagesize( $file_path );

		if ( isset( $image_type[2] ) ) {
			return image_type_to_mime_type( $image_type[2] );
		}
	}

	$image_type = wp_check_filetype( $file_path, array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'gif'          => 'image/gif',
	) );

	return $image_type['type'];
}

/**
 * Tell if an attachment has a supported mime type.
 * Was previously Imagify_AS3CF::is_mime_type_supported() since 1.6.6.
 * Ironically, this function is used in Imagify::is_mime_type_supported() since 1.6.9.
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
 * Tell if the attachment has the required WP metadata.
 *
 * @since  1.6.12
 * @author Grégory Viguier
 *
 * @param  int $attachment_id The attachment ID.
 * @return bool
 */
function imagify_attachment_has_required_metadata( $attachment_id ) {
	return get_attached_file( $attachment_id, true ) && wp_get_attachment_metadata( $attachment_id, true );
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
 * Get size information for all currently registered thumbnail sizes.
 *
 * @since  1.5.10
 * @since  1.6.10 For consistency, revamped the function like WP does with wp_generate_attachment_metadata().
 *                Removed the filter, added crop value to each size.
 * @author Grégory Viguier
 *
 * @return array Data for all currently registered thumbnail sizes (width, height, crop, name).
 */
function get_imagify_thumbnail_sizes() {
	// All image size names.
	$intermediate_image_sizes = get_intermediate_image_sizes();
	$intermediate_image_sizes = array_flip( $intermediate_image_sizes );
	// Additional image size attributes.
	$additional_image_sizes   = wp_get_additional_image_sizes();

	// Create the full array with sizes and crop info.
	foreach ( $intermediate_image_sizes as $size_name => $s ) {
		$intermediate_image_sizes[ $size_name ] = array(
			'width'  => '',
			'height' => '',
			'crop'   => false,
			'name'   => $size_name,
		);

		if ( isset( $additional_image_sizes[ $size_name ]['width'] ) ) {
			// For theme-added sizes.
			$intermediate_image_sizes[ $size_name ]['width'] = (int) $additional_image_sizes[ $size_name ]['width'];
		} else {
			// For default sizes set in options.
			$intermediate_image_sizes[ $size_name ]['width'] = (int) get_option( "{$size_name}_size_w" );
		}

		if ( isset( $additional_image_sizes[ $size_name ]['height'] ) ) {
			// For theme-added sizes.
			$intermediate_image_sizes[ $size_name ]['height'] = (int) $additional_image_sizes[ $size_name ]['height'];
		} else {
			// For default sizes set in options.
			$intermediate_image_sizes[ $size_name ]['height'] = (int) get_option( "{$size_name}_size_h" );
		}

		if ( isset( $additional_image_sizes[ $size_name ]['crop'] ) ) {
			// For theme-added sizes.
			$intermediate_image_sizes[ $size_name ]['crop'] = (int) $additional_image_sizes[ $size_name ]['crop'];
		} else {
			// For default sizes set in options.
			$intermediate_image_sizes[ $size_name ]['crop'] = (int) get_option( "{$size_name}_crop" );
		}
	}

	return $intermediate_image_sizes;
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

/**
 * Get the maximal number of unoptimized attachments to fetch.
 *
 * @since  1.6.14
 * @author Grégory Viguier
 *
 * @return int
 */
function imagify_get_unoptimized_attachment_limit() {
	/**
	 * Filter the unoptimized attachments limit query.
	 *
	 * @since 1.4.4
	 *
	 * @param int $limit The limit (-1 for unlimited).
	 */
	$limit = (int) apply_filters( 'imagify_unoptimized_attachment_limit', 10000 );

	return -1 === $limit ? PHP_INT_MAX : abs( $limit );
}
