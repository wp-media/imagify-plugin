<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Get WP Direct filesystem object. Also define chmod constants if not done yet.
 *
 * @since  1.6.5
 * @author Grégory Viguier
 *
 * @return object A WP_Filesystem object.
 */
function imagify_get_filesystem() {
	return Imagify_Filesystem::get_instance();
}
