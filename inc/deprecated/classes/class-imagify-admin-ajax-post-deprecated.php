<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class for deprecated methods from Imagify_Admin_Ajax_Post.
 *
 * @since  1.8.4
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_Admin_Ajax_Post_Deprecated {

	/**
	 * Optimize image on picture uploading with async request.
	 *
	 * @since  1.6.11
	 * @since  1.8.4 Deprecated
	 * @access public
	 * @author Julio Potier
	 * @see    _imagify_optimize_attachment()
	 * @deprecated
	 */
	public function imagify_async_optimize_upload_new_media_callback() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_auto_optimize_callback()' );

		if ( empty( $_POST['_ajax_nonce'] ) || empty( $_POST['attachment_id'] ) || empty( $_POST['metadata'] ) || empty( $_POST['context'] ) ) { // WPCS: CSRF ok.
			return;
		}

		$context       = imagify_sanitize_context( $_POST['context'] );
		$attachment_id = absint( $_POST['attachment_id'] );

		imagify_check_nonce( 'new_media-' . $attachment_id );
		imagify_check_user_capacity( 'auto-optimize' );

		$attachment = get_imagify_attachment( $context, $attachment_id, 'imagify_async_optimize_upload_new_media' );

		// Optimize it!!!!!
		$attachment->optimize( null, $_POST['metadata'] );
		die( 1 );
	}

	/**
	 * Optimize image on picture editing (resize, crop...) with async request.
	 *
	 * @since  1.6.11
	 * @since  1.8.4 Deprecated
	 * @access public
	 * @author Julio Potier
	 * @deprecated
	 */
	public function imagify_async_optimize_save_image_editor_file_callback() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_auto_optimize_callback()' );

		$attachment_id = ! empty( $_POST['postid'] ) ? absint( $_POST['postid'] ) : 0;

		if ( ! $attachment_id || empty( $_POST['do'] ) ) {
			return;
		}

		imagify_check_nonce( 'image_editor-' . $attachment_id );
		imagify_check_user_capacity( 'edit_post', $attachment_id );

		$attachment = get_imagify_attachment( 'wp', $attachment_id, 'wp_ajax_imagify_async_optimize_save_image_editor_file' );

		if ( ! $attachment->get_data() ) {
			return;
		}

		$optimization_level = $attachment->get_optimization_level();
		$metadata           = wp_get_attachment_metadata( $attachment_id );

		// Remove old optimization data.
		$attachment->delete_imagify_data();

		if ( 'restore' === $_POST['do'] ) {
			// Restore the backup file.
			$attachment->restore();

			// Get old metadata to regenerate all thumbnails.
			$metadata     = array( 'sizes' => array() );
			$backup_sizes = (array) get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );

			foreach ( $backup_sizes as $size_key => $size_data ) {
				$size_key = str_replace( '-origin', '' , $size_key );
				$metadata['sizes'][ $size_key ] = $size_data;
			}
		}

		// Optimize it!!!!!
		$attachment->optimize( $optimization_level, $metadata );
		die( 1 );
	}
}
