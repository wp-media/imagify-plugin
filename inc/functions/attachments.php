<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/*
 * Get all mime type which could be optimized by Imagify.
 *
 * @since 1.3
 *
 * @return array $mime_type  The mime type.
 */
function get_imagify_mime_type() {
	$mime_type = array(
		'image/jpeg', 
		'image/png', 
		'image/gif' 	
	);
	
	return $mime_type;
}

/*
 * Get the backup path of a specific attachement.
 *
 * @since 1.0
 *
 * @param  int    $file_path    The attachment path.
 * @return string $backup_path  The backup path.
 */
function get_imagify_attachment_backup_path( $file_path ) {
	$upload_dir       = wp_upload_dir();
	$upload_basedir   = trailingslashit( $upload_dir['basedir'] );
	$backup_dir 	  = $upload_basedir . 'backup/';
	
	/**
	 * Filter the backup directory path
	 *
	 * @since 1.0
	 *
	 * @param string $backup_dir The backup directory path
	*/
	$backup_dir  = apply_filters( 'imagify_backup_directory', $backup_dir );	
	$backup_dir  = trailingslashit( $backup_dir );
	
	$backup_path = str_replace( $upload_basedir, $backup_dir, $file_path );
	return $backup_path;
}

/**
 * Run an async job to optimize images in background
 *
 * @param $body (array) Contains the usual $_POST
 *
 * @since 1.4
 **/
function imagify_do_async_job( $body ) {
	if ( isset( $body['transient_id'] ) ) {
		set_transient( 'imagify-async-in-progress-' . $body['transient_id'], true );
	}

	$args = array(
		'timeout'   => 0.01,
		'blocking'  => false,
		'body'      => $body,
		'cookies'   => $_COOKIE,
		'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
	);

	wp_remote_post( admin_url( 'admin-ajax.php' ), $args );
}