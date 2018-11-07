<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

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
