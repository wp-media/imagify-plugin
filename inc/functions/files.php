<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Get WP Direct filesystem object. Also define chmod constants if not done yet.
 *
 * @since 1.6.5
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
 * @param string $file The path to file.
 * @return bool
 */
function imagify_chmod_file( $file ) {
	return imagify_get_filesystem()->chmod( $file, FS_CHMOD_FILE );
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
 * @since  1.6.8
 * @author Grégory Viguier
 *
 * @param  string $file_path An absolute path.
 * @return string            A relative path. Can return the absolute path in case of a failure.
 */
function imagify_make_file_path_replative( $file_path ) {
	static $abspath;
	global $wp_plugin_paths;

	if ( ! isset( $abspath ) ) {
		$abspath = wp_normalize_path( ABSPATH );
	}

	$file_path = wp_normalize_path( $file_path );
	$pos       = strpos( $file_path, $abspath );

	if ( false === $pos && $wp_plugin_paths && is_array( $wp_plugin_paths ) ) {
		// The file is probably part of a symlinked plugin.
		arsort( $wp_plugin_paths );

		foreach ( $wp_plugin_paths as $dir => $realdir ) {
			if ( strpos( $file_path, $realdir ) === 0 ) {
				$file_path = wp_normalize_path( $dir . substr( $file_path, strlen( $realdir ) ) );
			}
		}

		$pos = strpos( $file_path, $abspath );
	}

	if ( false === $pos ) {
		// We're in trouble.
		return $file_path;
	}

	return substr_replace( $file_path, '', 0, $pos + strlen( $abspath ) );
}
