<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( defined( 'FLYWHEEL_CONFIG_DIR' ) ) :

	add_filter( 'imagify_site_root', 'imagify_flywheel_site_root', IMAGIFY_INT_MAX );
	/**
	 * Filter the path to the site’s root.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 *
	 * @param  string|null $root_path Path to the site's root. Default is null.
	 * @return string|null
	 */
	function imagify_flywheel_site_root( $root_path ) {
		if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
			return trailingslashit( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) );
		}

		$upload_basedir = imagify_get_filesystem()->get_upload_basedir( true );

		if ( strpos( $upload_basedir, '/wp-content/' ) === false ) {
			// Uh oooooh...
			return $root_path;
		}

		$upload_basedir = explode( '/wp-content/', $upload_basedir );
		$upload_basedir = reset( $upload_basedir );

		return $upload_basedir . '/';
	}

endif;
