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

/**
 * Optimize a resized attachment
 *
 * @since 1.3.6
 */
add_action( 'shutdown', '_imagify_optimize_save_image_editor_file' );
function _imagify_optimize_save_image_editor_file() {
	if ( isset( $_POST['action'], $_POST['do'], $_POST['postid'] )
	   && 'image-editor' === $_POST['action']
	   && check_ajax_referer( 'image_editor-' . $_POST['postid'] )
	   && get_post_meta( $_POST['postid'], '_imagify_data', true )
	) {
		$attachment_id      = $_POST['postid'];
		$optimization_level = get_post_meta( $attachment_id, '_imagify_optimization_level', true );
		$attachment         = new Imagify_Attachment( $attachment_id );
		$metadata			= wp_get_attachment_metadata( $attachment_id );
		
		// Remove old optimization data
		delete_post_meta( $attachment_id, '_imagify_data' );
		delete_post_meta( $attachment_id, '_imagify_status' );
		delete_post_meta( $attachment_id, '_imagify_optimization_level' );

		if ( 'restore' === $_POST['do'] ) {
			// Restore the backup file
			$attachment->restore();
			
			// Get old metadata to regenerate all thumbnails
			$metadata 	  = array( 'sizes' => array() );
			$backup_sizes = (array) get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );
			
			foreach ( $backup_sizes as $size_key => $size_data ) {
				$size_key = str_replace( '-origin', '' , $size_key );
				$metadata['sizes'][ $size_key ] = $size_data;
			}
		}

		// Optimize it!!!!!
		$attachment->optimize( $optimization_level, $metadata );
	}
}