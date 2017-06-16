<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( function_exists( 'emr_delete_current_files' ) ) :

	add_action( 'enable-media-replace-upload-done', '_imagify_optimize_enable_media_replace' );
	/**
	 * Re-Optimize an attachment after replace it with Enable Media Replace.
	 *
	 * @since 1.0
	 *
	 * @param string $guid A post guid.
	 */
	function _imagify_optimize_enable_media_replace( $guid ) {
		global $wpdb;
		$attachment_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $guid ) );

		// Stop if the attachment wasn't optimized yet by Imagify.
		if ( ! get_post_meta( $attachment_id, '_imagify_data', true ) ) {
			return;
		}

		$optimization_level = get_post_meta( $attachment_id, '_imagify_optimization_level', true );
		$class_name         = get_imagify_attachment_class_name( 'wp', $attachment_id, 'enable-media-replace-upload-done' );
		$attachment         = new $class_name( $attachment_id );

		// Remove old optimization data.
		$attachment->delete_imagify_data();

		// Optimize it!!!!!
		$attachment->optimize( $optimization_level );
	}

endif;
