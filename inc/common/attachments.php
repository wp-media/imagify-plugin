<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Auto-optimize when a new attachment is generated
 *
 * @since 1.0
 * @since 1.5 Async job
 */
add_filter( 'wp_generate_attachment_metadata', '_imagify_optimize_attachment', PHP_INT_MAX, 2 );
function _imagify_optimize_attachment( $metadata, $attachment_id ) {
	$api_key = get_imagify_option( 'api_key', false );

	if ( ! empty( $api_key ) && get_imagify_option( 'auto_optimize', false ) ) {
		$context	 = 'wp';
		$action      = 'imagify_async_optimize_upload_new_media';
		$_ajax_nonce = wp_create_nonce( 'new_media-' . $attachment_id );

		imagify_do_async_job( compact( 'action', '_ajax_nonce', 'metadata', 'attachment_id', 'context' ) );
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
 * @since 1.4 Async job
 */
add_action( 'shutdown', '_imagify_optimize_save_image_editor_file' );
function _imagify_optimize_save_image_editor_file() {
	if ( isset( $_POST['action'], $_POST['do'], $_POST['postid'] )
		&& 'image-editor' === $_POST['action']
		&& check_ajax_referer( 'image_editor-' . $_POST['postid'] )
		&& get_post_meta( $_POST['postid'], '_imagify_data', true )
		&& 'open' != $_POST['do']
	) {

		$body           = $_POST;
		$body['action'] = 'imagify_async_optimize_save_image_editor_file';

		imagify_do_async_job( $body );
	}
}