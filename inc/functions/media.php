<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Trigger a hook that should happen before a media is deleted.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param ProcessInterface $process An optimization process.
 */
function imagify_trigger_delete_media_hook( $process ) {
	/**
	 * Triggered bifore a media is deleted.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 *
	 * @param ProcessInterface $process An optimization process.
	 */
	do_action( 'imagify_delete_media', $process );
}
