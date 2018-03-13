<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Get WP Direct filesystem object. Also define chmod constants if not done yet.
 *
 * @since  1.6.5
 * @author Grégory Viguier
 *
 * @return object A `$wp_filesystem` object.
 */
function imagify_get_filesystem() {
	static $filesystem;

	if ( $filesystem ) {
		return $filesystem;
	}

	require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
	require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );

	$filesystem = new WP_Filesystem_Direct( new StdClass() );

	// Set the permission constants if not already set.
	if ( ! defined( 'FS_CHMOD_DIR' ) ) {
		define( 'FS_CHMOD_DIR', ( fileperms( ABSPATH ) & 0777 | 0755 ) );
	}
	if ( ! defined( 'FS_CHMOD_FILE' ) ) {
		define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
	}

	return $filesystem;
}


/**
 * Set the default file permissions using FS_CHMOD_FILE from WP.
 *
 * @since 1.2
 * @since 1.6.5 Use WP Filesystem.
 *
 * @param  string $file_path The path to file.
 * @return bool
 */
function imagify_chmod_file( $file_path ) {
	return imagify_get_filesystem()->chmod( $file_path, FS_CHMOD_FILE );
}

/**
 * Get a file mime type.
 *
 * @since  1.6.9
 * @since  1.7 Doesn't use exif_imagetype() nor getimagesize() anymore.
 * @author Grégory Viguier
 *
 * @param  string $file_path A file path (prefered) or a filename.
 * @return string|bool       A mime type. False on failure: the last test is limited to mime types supported by Imagify.
 */
function imagify_get_mime_type_from_file( $file_path ) {
	if ( ! $file_path ) {
		return false;
	}

	$file_type = wp_check_filetype( $file_path, imagify_get_mime_types() );

	return $file_type['type'];
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
	static $offset;

	if ( ! $file_path ) {
		return current_time( 'mysql' );
	}

	$date = imagify_get_filesystem()->mtime( $file_path );

	if ( ! $date ) {
		return current_time( 'mysql' );
	}

	if ( ! isset( $offset ) ) {
		$offset = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
	}

	return gmdate( 'Y-m-d H:i:s', $date + $offset );
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
	static $abspath;

	if ( isset( $abspath ) ) {
		return $abspath;
	}

	$abspath = wp_normalize_path( ABSPATH );

	// Make sure ABSPATH is not messed up: it could be defined as a relative path for example (yeah, I know, but we've seen it).
	$test_file = wp_normalize_path( IMAGIFY_FILE );
	$pos       = strpos( $test_file, $abspath );

	if ( 0 < $pos ) {
		// ABSPATH has a wrong value.
		$abspath = substr( $test_file, 0, $pos ) . $abspath;

	} elseif ( false === $pos && class_exists( 'ReflectionClass' ) ) {
		// Imagify is symlinked (dude, you look for trouble).
		$reflector = new ReflectionClass( 'Requests' );
		$test_file = $reflector->getFileName();
		$pos       = strpos( $test_file, $abspath );

		if ( 0 < $pos ) {
			// ABSPATH has a wrong value.
			$abspath = substr( $test_file, 0, $pos ) . $abspath;
		}
	}

	$abspath = '/' . trim( $abspath, '/' ) . '/';

	return $abspath;
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
	static $abspath;
	global $wp_plugin_paths;

	if ( ! $file_path ) {
		return false;
	}

	if ( ! isset( $abspath ) ) {
		$abspath = wp_normalize_path( ABSPATH );
	}

	$file_path = wp_normalize_path( $file_path );
	$base      = $base ? trailingslashit( wp_normalize_path( $base ) ) : $abspath;
	$pos       = strpos( $file_path, $base );

	if ( false === $pos && $wp_plugin_paths && is_array( $wp_plugin_paths ) ) {
		// The file is probably part of a symlinked plugin.
		arsort( $wp_plugin_paths );

		foreach ( $wp_plugin_paths as $dir => $realdir ) {
			if ( strpos( $file_path, $realdir ) === 0 ) {
				$file_path = wp_normalize_path( $dir . substr( $file_path, strlen( $realdir ) ) );
			}
		}

		$pos = strpos( $file_path, $base );
	}

	if ( false === $pos ) {
		// We're in trouble.
		return $file_path;
	}

	return substr_replace( $file_path, '', 0, $pos + strlen( $base ) );
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
	static $abspath;
	static $plugin_paths = array();
	global $wp_plugin_paths;

	if ( ! isset( $abspath ) ) {
		$abspath = strtolower( wp_normalize_path( trailingslashit( imagify_get_abspath() ) ) );
	}

	$lower_file_path = strtolower( wp_normalize_path( trailingslashit( realpath( $file_path ) ) ) );

	if ( strpos( $lower_file_path, $abspath ) !== 0 ) {
		return true;
	}

	if ( $wp_plugin_paths && is_array( $wp_plugin_paths ) ) {
		if ( ! $plugin_paths ) {
			foreach ( $wp_plugin_paths as $dir => $realdir ) {
				$dir = strtolower( wp_normalize_path( trailingslashit( $dir ) ) );
				$plugin_paths[ $dir ] = strtolower( wp_normalize_path( trailingslashit( $realdir ) ) );
			}
		}

		$lower_file_path = strtolower( wp_normalize_path( trailingslashit( $file_path ) ) );

		foreach ( $plugin_paths as $dir => $realdir ) {
			if ( strpos( $lower_file_path, $dir ) === 0 ) {
				return true;
			}
		}
	}

	return false;
}
