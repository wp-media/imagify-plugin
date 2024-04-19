<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_action( 'delete_attachment', 'imagify_trigger_delete_attachment_hook' );
/**
 * Trigger a common Imagify hook when an attachment is deleted.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param int $post_id Attachment ID.
 */
function imagify_trigger_delete_attachment_hook( $post_id ) {
	$process = imagify_get_optimization_process( $post_id, 'wp' );

	if ( ! $process->is_valid() ) {
		return;
	}

	imagify_trigger_delete_media_hook( $process );
}

add_action( 'imagify_delete_media', 'imagify_cleanup_after_media_deletion' );
/**
 * Delete the backup file and the next-gen files when an attachement is deleted.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param ProcessInterface $process An optimization process.
 */
function imagify_cleanup_after_media_deletion( $process ) {
	if ( 'wp' !== $process->get_media()->get_context() ) {
		return;
	}

	/**
	 * The optimization data will be automatically deleted by WP (post metas).
	 * Delete the Nextgen versions and the backup file.
	 */
	$process->delete_nextgen_files( false, true );

	$process->delete_backup();
}

add_filter( 'ext2type', 'imagify_add_avif_type' );
/**
 * Add the AVIF extension to wp_get_ext_types().
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  array $ext2type Multi-dimensional array with extensions for a default set of file types.
 * @return array
 */
function imagify_add_avif_type( $ext2type ) {
	if ( ! in_array( 'avif', $ext2type['image'], true ) ) {
		$ext2type['image'][] = 'avif';
	}
	return $ext2type;
}

/**
 * Set WP’s "big images threshold" to Imagify’s resizing value.
 *
 * @since  1.9.8
 * @since  WP 5.3
 * @author Grégory Viguier
 */
add_filter( 'big_image_size_threshold', [ imagify_get_context( 'wp' ), 'get_resizing_threshold' ], IMAGIFY_INT_MAX );

/**
 * Add filters to manage images formats that will be generated
 *
 * @return array
 */
function imagify_nextgen_images_formats() {
	$value   = get_imagify_option( 'optimization_format' );
	$formats = [];

	if ( 'off' !== $value ) {
		$formats[ $value ] = $value;
	}

	$default = $formats;

	/**
	 * Filters the array of next gen formats to generate with Imagify
	 *
	 * @since 2.2
	 *
	 * @param array $formats Array of image formats
	 */
	$formats = apply_filters( 'imagify_nextgen_images_formats', $formats );

	if ( ! is_array( $formats ) ) {
		$formats = $default;
	}

	return $formats;
}
