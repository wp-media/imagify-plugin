<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

if ( class_exists( 'WpeCommon' ) ) :

/**
 * Conflict with WP Engine: it seems that WP Engine limits SQL queries size.
 *
 * @since  1.4.7
 * @author Jonathan Buttigieg
 */
add_filter( 'imagify_unoptimized_attachment_limit', '_imagify_wengine_unoptimized_attachment_limit' );
function _imagify_wengine_unoptimized_attachment_limit() {
	return 2500;
}

endif;