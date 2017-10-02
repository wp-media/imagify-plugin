<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Get user capacity to operate Imagify within NGG galleries.
 * It is meant to be used to filter 'imagify_capacity'.
 *
 * @since  1.6.11
 * @author Grégory Viguier
 *
 * @param string $capacity  The user capacity.
 * @param string $describer Capacity describer. Possible values are 'manage', 'bulk-optimize', 'manual-optimize', and 'auto-optimize'.
 * @return string
 */
function imagify_get_ngg_capacity( $capacity = 'edit_post', $describer = 'manual-optimize' ) {
	if ( 'manual-optimize' === $describer ) {
		return 'NextGEN Manage gallery';
	}

	return $capacity;
}
