<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_action( 'delete_attachment', 'imagify_cleanup_after_media_deletion' );
/**
 * Delete the backup file and the webp files when an attachement is deleted.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param int $post_id Attachment ID.
 */
function imagify_cleanup_after_media_deletion( $post_id ) {
	$process = imagify_get_optimization_process( $post_id, 'wp' );

	/**
	 * The optimization data has already been deleted by WP (post metas).
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
