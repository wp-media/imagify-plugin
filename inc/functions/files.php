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
