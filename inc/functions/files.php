<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Get WP Direct filesystem object. Also define chmod constants if not done yet.
 *
 * @since  1.6.5
 * @author Grégory Viguier
 *
 * @return object A WP_Filesystem object.
 */
function imagify_get_filesystem() {
	return Imagify_Filesystem::get_instance();
}

/**
 * Set a file permissions using FS_CHMOD_FILE.
 *
 * @since 1.2
 * @since 1.6.5 Use WP Filesystem.
 *
 * @param  string $file_path Path to the file.
 * @return bool              True on success, false on failure.
 */
function imagify_chmod_file( $file_path ) {
	return imagify_get_filesystem()->chmod_file( $file_path );
}

/**
 * Get a file mime type.
 *
 * @since  1.6.9
 * @since  1.7 Doesn't use exif_imagetype() nor getimagesize() anymore.
 * @author Grégory Viguier
 *
 * @param  string $file_path A file path (prefered) or a filename.
 * @return string|bool       A mime type. False on failure: the test is limited to mime types supported by Imagify.
 */
function imagify_get_mime_type_from_file( $file_path ) {
	return imagify_get_filesystem()->get_mime_type( $file_path );
}

/**
 * Get a file modification date, formated as "mysql". Fallback to current date.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @param  string $file_path The file path.
 * @return string            The date.
 */
function imagify_get_file_date( $file_path ) {
	return imagify_get_filesystem()->get_date( $file_path );
}

/**
 * Get a clean value of ABSPATH that can be used in string replacements.
 *
 * @since  1.6.8
 * @author Grégory Viguier
 *
 * @return string The path to WordPress' root folder.
 */
function imagify_get_abspath() {
	return imagify_get_filesystem()->get_abspath();
}

/**
 * Make an absolute path relative to WordPress' root folder.
 * Also works for files from registered symlinked plugins.
 *
 * @since  1.6.10
 * @since  1.7 The parameter $base is added.
 * @author Grégory Viguier
 *
 * @param  string $file_path An absolute path.
 * @param  string $base      A base path to use instead of ABSPATH.
 * @return string|bool       A relative path. Can return the absolute path or false in case of a failure.
 */
function imagify_make_file_path_relative( $file_path, $base = '' ) {
	return imagify_get_filesystem()->make_path_relative( $file_path, $base );
}

/**
 * Tell if a file is symlinked.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @param  string $file_path An absolute path.
 * @return bool
 */
function imagify_file_is_symlinked( $file_path ) {
	return imagify_get_filesystem()->is_symlinked( $file_path );
}
