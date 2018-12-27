<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class for deprecated methods from Imagify_AS3CF.
 *
 * @since  1.7
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_AS3CF_Deprecated {

	/** ----------------------------------------------------------------------------------------- */
	/** AUTOMATIC OPTIMIZATION: OPTIMIZE AFTER S3 HAS DONE ITS WORK ============================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Filter the generated attachment meta data.
	 * This is used when a new attachment has just been uploaded (or not, when wp_generate_attachment_metadata() is used).
	 * We use it to tell the difference later in wp_update_attachment_metadata().
	 *
	 * @since  1.6.6
	 * @since  1.8.4 Deprecated
	 * @author Grégory Viguier
	 * @see    $this->do_async_job()
	 * @deprecated
	 *
	 * @param  array $metadata      An array of attachment meta data.
	 * @param  int   $attachment_id Current attachment ID.
	 * @return array
	 */
	public function store_upload_ids( $metadata, $attachment_id ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4', 'Imagify_Auto_Optimization::get_instance()->store_upload_ids( $attachment_id )' );

		if ( imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			$this->uploads[ $attachment_id ] = 1;
		}

		return $metadata;
	}

	/**
	 * After an image (maybe) being sent to S3, launch an async optimization.
	 *
	 * @since  1.6.6
	 * @since  1.8.4 Deprecated
	 * @author Grégory Viguier
	 * @see    $this->store_upload_ids()
	 * @deprecated
	 *
	 * @param  array $metadata      An array of attachment meta data.
	 * @param  int   $attachment_id Current attachment ID.
	 * @return array
	 */
	public function do_async_job( $metadata, $attachment_id ) {
		static $auto_optimize;

		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4', 'Imagify_Auto_Optimization::get_instance()->do_auto_optimization( $meta_id, $attachment_id, $meta_key, $metadata )' );

		$is_new_upload = ! empty( $this->uploads[ $attachment_id ] );
		unset( $this->uploads[ $attachment_id ] );

		if ( ! $metadata || ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			return $metadata;
		}

		if ( ! isset( $auto_optimize ) ) {
			$auto_optimize = Imagify_Requirements::is_api_key_valid() && get_imagify_option( 'auto_optimize' );
		}

		if ( $is_new_upload ) {
			// It's a new upload.
			if ( ! $auto_optimize ) {
				// Auto-optimization is disabled.
				return $metadata;
			}

			/** This filter is documented in inc/common/attachments.php. */
			$optimize = apply_filters( 'imagify_auto_optimize_attachment', true, $attachment_id, $metadata );

			if ( ! $optimize ) {
				return $metadata;
			}
		}

		if ( ! $is_new_upload ) {
			$attachment = get_imagify_attachment( self::CONTEXT, $attachment_id, 'as3cf_async_job' );

			if ( ! $attachment->get_data() ) {
				// It's not a new upload and the attachment is not optimized yet.
				return $metadata;
			}
		}

		$data = array();

		// Some specifics for the image editor.
		if ( isset( $_POST['action'], $_POST['do'], $_POST['postid'] ) && 'image-editor' === $_POST['action'] && (int) $_POST['postid'] === $attachment_id ) { // WPCS: CSRF ok.
			check_ajax_referer( 'image_editor-' . $_POST['postid'] );
			$data = $_POST;
		}

		imagify_do_async_job( array(
			'action'      => 'imagify_async_optimize_as3cf',
			'_ajax_nonce' => wp_create_nonce( 'imagify_async_optimize_as3cf' ),
			'post_id'     => $attachment_id,
			'metadata'    => $metadata,
			'data'        => $data,
		) );

		return $metadata;
	}

	/**
	 * Once an image has been sent to S3, optimize it and send it again.
	 *
	 * @since  1.6.6
	 * @since  1.8.4 Deprecated
	 * @author Grégory Viguier
	 * @deprecated
	 */
	public function optimize() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_auto_optimize_callback()' );

		check_ajax_referer( 'imagify_async_optimize_as3cf' );

		if ( empty( $_POST['post_id'] ) || ! imagify_current_user_can( 'auto-optimize' ) ) {
			die();
		}

		$attachment_id = absint( $_POST['post_id'] );

		if ( ! $attachment_id || empty( $_POST['metadata'] ) || ! is_array( $_POST['metadata'] ) || empty( $_POST['metadata']['sizes'] ) ) {
			die();
		}

		if ( ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			die();
		}

		$optimization_level = null;
		$attachment         = get_imagify_attachment( self::CONTEXT, $attachment_id, 'as3cf_optimize' );

		// Some specifics for the image editor.
		if ( ! empty( $_POST['data']['do'] ) ) {
			$optimization_level = $attachment->get_optimization_level();

			// Remove old optimization data.
			$attachment->delete_imagify_data();

			if ( 'restore' === $_POST['data']['do'] ) {
				// Restore the backup file.
				$attachment->restore();
			}
		}

		// Optimize it.
		$attachment->optimize( $optimization_level, $_POST['metadata'] );
	}
}
