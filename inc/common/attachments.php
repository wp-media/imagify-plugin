<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Auto-optimize when a new attachment is generated
 *
 * @since 1.0
 */
add_filter( 'wp_generate_attachment_metadata', '_imagify_optimize_attachment', PHP_INT_MAX, 2 );
function _imagify_optimize_attachment( $metadata, $attachment_id ) {
	$api_key = get_imagify_option( 'api_key', false );
	
	if ( ! empty( $api_key ) && get_imagify_option( 'auto_optimize', false ) ) {
		$attachment = new Imagify_Attachment( $attachment_id );
		
		// Optimize it!!!!!
		$attachment->optimize( null, $metadata );
	}

	return $metadata;
}


/**
 * Delete the backup file when an attachement is deleted.
 *
 * @since 1.0
 */
add_action( 'delete_attachment', '_imagify_delete_backup_file' );
function _imagify_delete_backup_file( $post_id ) {
	$attachment  = new Imagify_Attachment( $post_id );
	$attachment->delete_backup();
}