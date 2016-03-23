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

/*
 * Retrieve file path for an attachment based on filename.
 *
 * @since 1.4.5
 *
 * @param  int           $filename   The filename.
 * @return string|false  $file_path  The file path to where the attached file should be, false otherwise.
 */
function get_imagify_attached_file( $filename ) {		
	$file_path = false;
	
	// If the file is relative, prepend upload dir.
	if ( $filename && 0 !== strpos( $filename, '/' ) && ! preg_match( '|^.:\\\|', $filename ) && ( ( $uploads = wp_upload_dir() ) && false === $uploads['error'] ) ) {
		$file_path = $uploads['basedir'] . "/$filename";
	}
	
	return $file_path;
}

/*
 * Retrieve the URL for an attachment based on filename.
 *
 * @since 1.4.5
 *
 * @param  int           $filename  The filename.
 * @return string|false  $url       Attachment URL, otherwise false.
 */
function get_imagify_attachment_url( $filename ) {	
	$url = '';
	
	// Get upload directory.
	if ( ( $uploads = wp_upload_dir() ) && false === $uploads['error'] ) {
		// Check that the upload base exists in the file location.
		if ( 0 === strpos( $filename, $uploads['basedir'] ) ) {
			// Replace file location with url location.
			$url = str_replace( $uploads['basedir'], $uploads['baseurl'], $filename );
		} elseif ( false !== strpos( $filename, 'wp-content/uploads' ) ) {
			// Get the directory name relative to the basedir (back compat for pre-2.7 uploads)
			$url = trailingslashit( $uploads['baseurl'] . '/' . _wp_get_attachment_relative_path( $filename ) ) . basename( $filename );
		} else {
			// It's a newly-uploaded file, therefore $file is relative to the basedir.
			$url = $uploads['baseurl'] . "/$filename";
		}
	}
	
	return $url;
}