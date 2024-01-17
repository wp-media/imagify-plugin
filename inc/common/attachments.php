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
 * Delete the backup file and the WebP files when an attachement is deleted.
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
	 * Delete the WebP versions and the backup file.
	 */
	$process->delete_webp_files();
	$process->delete_backup();
}

add_filter( 'ext2type', 'imagify_add_webp_type' );
/**
 * Add the WebP extension to wp_get_ext_types().
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  array $ext2type Multi-dimensional array with extensions for a default set of file types.
 * @return array
 */
function imagify_add_webp_type( $ext2type ) {
	if ( ! in_array( 'webp', $ext2type['image'], true ) ) {
		$ext2type['image'][] = 'webp';
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
 * @param array $formats The format values. Default values are 'webp' and 'avif'.
 *
 * @return array;
 */
function imagify_nextgen_images_format( array $formats ) {
	// If no formats is passed, bail early and default to webp.
	if ( empty( $formats ) ) {
		return [ 'webp' ];
	}

	if ( isset( $formats['webp'], $formats['avif'] )
		&& ( $formats['avif'] && $formats['webp'] )
	) {
		return [ 'avif', 'webp' ];
	}

	return $formats;
}

/**
 * Filter to get the image format to generate.
*/
add_filter( 'imagify_nextgen_images_formats', 'imagify_nextgen_images_format' );
