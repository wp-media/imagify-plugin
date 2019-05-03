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
 * Delete the backup file and the webp files when an attachement is deleted.
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
	 * Delete the webp versions and the backup file.
	 */
	$process->delete_webp_files();
	$process->delete_backup();
}

add_filter( 'ext2type', 'imagify_add_webp_type' );
/**
 * Add the webp extension to wp_get_ext_types().
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
