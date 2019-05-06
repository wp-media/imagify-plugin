<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Deprecated class that handles compatibility with Regenerate Thumbnails plugin.
 *
 * @since  1.9
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_Regenerate_Thumbnails_Deprecated {

	/**
	 * Action used for the ajax callback.
	 *
	 * @var    string
	 * @since  1.7.1
	 * @since  1.9 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 */
	const ACTION = 'imagify_regenerate_thumbnails';

	/**
	 * List of the attachments to regenerate.
	 *
	 * @var    array An array of Imagify attachments. The array keys are the attachment IDs.
	 * @since  1.7.1
	 * @since  1.9 Deprecated.
	 * @access protected
	 * @author Grégory Viguier
	 * @deprecated
	 */
	protected $attachments = [];

	/**
	 * Optimize the newly regenerated thumbnails.
	 *
	 * @since  1.7.1
	 * @since  1.9 Deprecated.
	 * @access public
	 * @author Grégory Viguier
	 * @deprecated
	 */
	public function regenerate_thumbnails_callback() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9' );

		if ( empty( $_POST['_ajax_nonce'] ) || empty( $_POST['attachment_id'] ) || empty( $_POST['context'] ) ) { // WPCS: CSRF ok.
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		if ( empty( $_POST['sizes'] ) || ! is_array( $_POST['sizes'] ) ) { // WPCS: CSRF ok.
			imagify_die( __( 'No thumbnail sizes selected', 'imagify' ) );
		}

		$attachment_id = absint( $_POST['attachment_id'] );
		$context       = imagify_sanitize_context( $_POST['context'] ); // WPCS: CSRF ok.

		imagify_check_nonce( static::get_nonce_name( $attachment_id, $context ) );
		imagify_check_user_capacity( 'manual-optimize', $attachment_id );

		$attachment = get_imagify_attachment( $context, $attachment_id, static::ACTION );

		if ( ! $attachment->is_valid() || ! $attachment->is_image() ) {
			wp_send_json_error();
		}

		// Optimize.
		$attachment->reoptimize_thumbnails( wp_unslash( $_POST['sizes'] ) );

		// Put the optimized original file back.
		$this->put_optimized_file_back( $attachment_id );

		wp_send_json_success();
	}

	/**
	 * Set the Imagify attachment.
	 *
	 * @since  1.7.1
	 * @since  1.9 Deprecated.
	 * @access protected
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  int $attachment_id Attachment ID.
	 * @return object|false       An Imagify attachment object. False on failure.
	 */
	protected function set_attachment( $attachment_id ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9', '\\Imagify\\ThirdParty\\RegenerateThumbnails\\Main::get_instance()->set_process()' );

		if ( ! $attachment_id || ! Imagify_Requirements::is_api_key_valid() ) {
			return false;
		}

		$attachment = get_imagify_attachment( 'wp', $attachment_id, 'regenerate_thumbnails' );

		if ( ! $attachment->is_valid() || ! $attachment->is_image() || ! $attachment->is_optimized() ) {
			return false;
		}

		// This attachment can be optimized.
		$this->attachments[ $attachment_id ] = $attachment;
		return $this->attachments[ $attachment_id ];
	}

	/**
	 * Unset the Imagify attachment.
	 *
	 * @since  1.7.1
	 * @since  1.9 Deprecated.
	 * @access protected
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	protected function unset_attachment( $attachment_id ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9', '\\Imagify\\ThirdParty\\RegenerateThumbnails\\Main::get_instance()->unset_process()' );

		unset( $this->attachments[ $attachment_id ] );
	}

	/**
	 * Get the Imagify attachment.
	 *
	 * @since  1.7.1
	 * @since  1.9 Deprecated.
	 * @access protected
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  int $attachment_id Attachment ID.
	 * @return object|false       An Imagify attachment object. False on failure.
	 */
	protected function get_attachment( $attachment_id ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9', '\\Imagify\\ThirdParty\\RegenerateThumbnails\\Main::get_instance()->get_process()' );

		return ! empty( $this->attachments[ $attachment_id ] ) ? $this->attachments[ $attachment_id ] : false;
	}

	/**
	 * Get the name of the nonce used for the ajax callback.
	 *
	 * @since  1.7.1
	 * @since  1.9 Deprecated.
	 * @access public
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  int    $media_id The media ID.
	 * @param  string $context  The context.
	 * @return string
	 */
	public static function get_nonce_name( $media_id, $context ) {
		_deprecated_function( get_called_class() . '::' . __FUNCTION__ . '()', '1.9' );

		return static::ACTION . '-' . $media_id . '-' . $context;
	}
}
