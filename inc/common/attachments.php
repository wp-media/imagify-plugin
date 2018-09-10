<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_filter( 'wp_generate_attachment_metadata', '_imagify_optimize_attachment', IMAGIFY_INT_MAX, 2 );
/**
 * Auto-optimize when a new attachment is generated.
 *
 * @since 1.0
 * @since 1.5 Async job.
 * @see   Imagify_Admin_Ajax_Post::imagify_async_optimize_upload_new_media_callback()
 *
 * @param  array $metadata      An array of attachment meta data.
 * @param  int   $attachment_id Current attachment ID.
 * @return array
 */
function _imagify_optimize_attachment( $metadata, $attachment_id ) {

	if ( ! Imagify_Requirements::is_api_key_valid() || ! get_imagify_option( 'auto_optimize' ) ) {
		return $metadata;
	}

	/**
	 * Allow to prevent automatic optimization for a specific attachment.
	 *
	 * @since  1.6.12
	 * @author Grégory Viguier
	 *
	 * @param bool  $optimize      True to optimize, false otherwise.
	 * @param int   $attachment_id Attachment ID.
	 * @param array $metadata      An array of attachment meta data.
	 */
	$optimize = apply_filters( 'imagify_auto_optimize_attachment', true, $attachment_id, $metadata );

	if ( ! $optimize ) {
		return $metadata;
	}

	$context     = 'wp';
	$action      = 'imagify_async_optimize_upload_new_media';
	$_ajax_nonce = wp_create_nonce( 'new_media-' . $attachment_id );

	imagify_do_async_job( compact( 'action', '_ajax_nonce', 'metadata', 'attachment_id', 'context' ) );

	return $metadata;
}

add_action( 'delete_attachment', '_imagify_delete_backup_file' );
/**
 * Delete the backup file when an attachement is deleted.
 *
 * @since 1.0
 *
 * @param int $post_id Attachment ID.
 */
function _imagify_delete_backup_file( $post_id ) {
	get_imagify_attachment( 'wp', $post_id, 'delete_attachment' )->delete_backup();
}

add_action( 'shutdown', '_imagify_optimize_save_image_editor_file' );
/**
 * Optimize an attachment after being resized.
 *
 * @since 1.3.6
 * @since 1.4 Async job.
 */
function _imagify_optimize_save_image_editor_file() {
	if ( ! isset( $_POST['action'], $_POST['do'], $_POST['postid'] ) || 'image-editor' !== $_POST['action'] || 'open' === $_POST['do'] ) { // WPCS: CSRF ok.
		return;
	}

	$attachment_id = absint( $_POST['postid'] );

	if ( ! $attachment_id || ! Imagify_Requirements::is_api_key_valid() ) {
		return;
	}

	check_ajax_referer( 'image_editor-' . $attachment_id );

	$attachment = get_imagify_attachment( 'wp', $attachment_id, 'save_image_editor_file' );

	if ( ! $attachment->get_data() ) {
		return;
	}

	$body           = $_POST;
	$body['action'] = 'imagify_async_optimize_save_image_editor_file';

	imagify_do_async_job( $body );
}
