<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( class_exists( 'WpeCommon' ) ) :

	add_filter( 'imagify_unoptimized_attachment_limit', '_imagify_wengine_unoptimized_attachment_limit' );
	/**
	 * Conflict with WP Engine: it seems that WP Engine limits SQL queries size.
	 *
	 * @since  1.4.7
	 * @author Jonathan Buttigieg
	 */
	function _imagify_wengine_unoptimized_attachment_limit() {
		return 2500;
	}

endif;
