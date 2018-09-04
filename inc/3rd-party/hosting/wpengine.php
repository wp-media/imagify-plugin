<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( class_exists( 'WpeCommon' ) ) :

	add_filter( 'imagify_unoptimized_attachment_limit', '_imagify_wpengine_unoptimized_attachment_limit' );
	add_filter( 'imagify_count_saving_data_limit',      '_imagify_wpengine_unoptimized_attachment_limit' );
	/**
	 * Change the limit for the number of posts: WP Engine limits SQL queries size to 2048 octets (16384 characters).
	 *
	 * @since  1.4.7
	 * @since  1.6.7 Renamed (and deprecated) _imagify_wengine_unoptimized_attachment_limit() into _imagify_wpengine_unoptimized_attachment_limit().
	 * @author Jonathan Buttigieg
	 *
	 * @return int
	 */
	function _imagify_wpengine_unoptimized_attachment_limit() {
		return 2500;
	}

endif;
