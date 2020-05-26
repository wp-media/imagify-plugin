<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that contains the deprecated methods of Imagify_Auto_Optimization.
 *
 * @since 1.9.10
 */
abstract class Imagify_Auto_Optimization_Deprecated {

	/**
	 * With WP 5.3+, prevent auto-optimization inside wp_generate_attachment_metadata() because it triggers a wp_update_attachment_metadata() for each thumbnail size.
	 *
	 * @since 1.9.8
	 * @since 1.9.10 Deprecated.
	 * @see   wp_generate_attachment_metadata()
	 * @see   wp_create_image_subsizes()
	 *
	 * @param  int    $threshold     The threshold value in pixels. Default 2560.
	 * @param  array  $imagesize     Indexed array of the image width and height (in that order).
	 * @param  string $file          Full path to the uploaded image file.
	 * @param  int    $attachment_id Attachment post ID.
	 * @return int                   The threshold value in pixels.
	 */
	public function prevent_auto_optimization_when_generating_thumbnails( $threshold, $imagesize, $file, $attachment_id ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9.10' );

		static::prevent_optimization_internally( $attachment_id );
		return $threshold;
	}

	/**
	 * With WP 5.3+, allow auto-optimization back after wp_generate_attachment_metadata().
	 *
	 * @since 1.9.8
	 * @since 1.9.10 Deprecated.
	 * @see   $this->prevent_auto_optimization_when_generating_thumbnails()
	 *
	 * @param  array  $metadata      An array of attachment meta data.
	 * @param  int    $attachment_id Current attachment ID.
	 * @param  string $context       Additional context. Can be 'create' when metadata was initially created for new attachment or 'update' when the metadata was updated.
	 * @return array                 An array of attachment meta data.
	 */
	public function allow_auto_optimization_when_generating_thumbnails( $metadata, $attachment_id, $context = null ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9.10' );

		if ( ! empty( $context ) && 'create' !== $context ) {
			return $metadata;
		}

		// Fired from wp_generate_attachment_metadata(): $context is empty (WP < 5.3) or equal to 'create' (>P >= 5.3).
		static::allow_optimization_internally( $attachment_id );
		return $metadata;
	}
}
