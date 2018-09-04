<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( function_exists( 'fn_lc_fix_ssl_upload_url' ) && defined( 'SLC_VERSION' ) && version_compare( SLC_VERSION, '2.2.8' ) < 0 ) :

	/**
	 * Fixes a bug in Screets Live Chat plugin (prior version 2.2.8), preventing wp_get_upload_dir() to work properly.
	 */
	remove_filter( 'upload_dir', 'fn_lc_fix_ssl_upload_url' );
	add_filter( 'upload_dir',    'imagify_screets_lc_fix_ssl_upload_url' );
	/**
	 * Filters the uploads directory data to force https URLs.
	 *
	 * @since 1.6.7
	 * @author Grégory Viguier
	 *
	 * @param  array $uploads Array of upload directory data with keys of 'path', 'url', 'subdir, 'basedir', 'baseurl', and 'error'.
	 * @return array
	 */
	function imagify_screets_lc_fix_ssl_upload_url( $uploads ) {
		if ( false !== $uploads['error'] || ! is_ssl() ) {
			return $uploads;
		}

		$uploads['url']     = str_replace( 'http://', 'https://', $uploads['url'] );
		$uploads['baseurl'] = str_replace( 'http://', 'https://', $uploads['baseurl'] );

		return $uploads;
	}

endif;
