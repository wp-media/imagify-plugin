<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Set the default file permissions using FS_CHMOD_FILE from WP
 *
 * @since 1.2
 *
 * @param string $file The path to file 
 * @return bool
 **/
function imagify_chmod_file( $file ) {
	if ( ! defined( 'FS_CHMOD_FILE' ) ) {
		define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
	}
	return @chmod( $file, FS_CHMOD_FILE );
}