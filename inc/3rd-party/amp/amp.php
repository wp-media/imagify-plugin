<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Compatibility with AMP plugin from WordPress.com VIP.
 */
if ( function_exists( 'is_amp_endpoint' ) ) :

	add_filter( 'imagify_allow_picture_tags_for_webp', 'imagify_amp_disable_picture_on_endpoint' );
	/**
	 * Do not use <picture> tags in AMP pages.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 *
	 * @param  bool $allow True to allow the use of <picture> tags (default). False to prevent their use.
	 * @return bool
	 */
	function imagify_amp_disable_picture_on_endpoint( $allow ) {
		return $allow && ! is_amp_endpoint();
	};

endif;
