<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

$this->print_template( 'notice-header', array(
	'classes' => array( 'error' ),
) );

$filesystem  = imagify_get_filesystem();
$backup_path = imagify_make_file_path_relative( get_imagify_backup_dir_path( true ) );

if ( $filesystem->exists( get_imagify_backup_dir_path() ) ) {
	/* translators: %s is a file path. */
	$message = __( 'The backup folder %s is not writable by the server, original images cannot be saved!', 'imagify' );
} else {
	/* translators: %s is a file path. */
	$message = __( 'The backup folder %s cannot be created. Is its parent directory writable by the server? Original images cannot be saved!', 'imagify' );
}

echo '<p>' . sprintf( $message, "<code>$backup_path</code>" ) . '</p>';

$this->print_template( 'notice-footer' );
