<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$this->print_template( 'notice-header', array(
	'classes' => array( 'error' ),
) );

$backup_path = $this->filesystem->make_path_relative( get_imagify_backup_dir_path( true ) );

if ( $this->filesystem->exists( get_imagify_backup_dir_path() ) ) {
	/* translators: %s is a file path. */
	$message = __( 'The backup folder %s is not writable by the server, original images cannot be saved!', 'imagify' );
} else {
	/* translators: %s is a file path. */
	$message = __( 'The backup folder %s cannot be created. Is its parent directory writable by the server? Original images cannot be saved!', 'imagify' );
}

echo '<p>' . sprintf( $message, "<code>$backup_path</code>" ) . '</p>';

$this->print_template( 'notice-footer' );
