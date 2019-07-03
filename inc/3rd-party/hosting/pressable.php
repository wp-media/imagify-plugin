<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( defined( 'IS_PRESSABLE' ) ) :

	add_filter( 'imagify_site_root', 'imagify_pressable_site_root', IMAGIFY_INT_MAX );
	/**
	 * Filter the path to the site's root.
	 *
	 * @since  1.9.4
	 * @author Grégory Viguier
	 *
	 * @param  string|null $root_path Path to the site's root. Default is null.
	 * @return string
	 */
	function imagify_pressable_site_root( $root_path ) {
		$upload_basedir = trim( wp_normalize_path( WP_CONTENT_DIR ), '/' );
		$upload_basedir = explode( '/', $upload_basedir );
		$upload_basedir = reset( $upload_basedir );

		return '/' . $upload_basedir . '/';
	}

endif;
