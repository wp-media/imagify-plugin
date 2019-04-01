<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Tell if WP Offload S3 compatibility is loaded.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @return bool
 */
function imagify_load_as3cf_compat() {
	if ( function_exists( 'as3cf_init' ) ) {
		// WP Offload S3 Lite.
		$version = ! empty( $GLOBALS['aws_meta']['amazon-s3-and-cloudfront']['version'] ) ? $GLOBALS['aws_meta']['amazon-s3-and-cloudfront']['version'] : false;

		if ( ! $version ) {
			return false;
		}

		if ( ! function_exists( 'amazon_web_services_init' ) && version_compare( $version, '1.3' ) < 0 ) {
			// Old version, plugin Amazon Web Services is required.
			return false;
		}

		return true;
	}

	if ( function_exists( 'as3cf_pro_init' ) ) {
		// WP Offload S3 Pro.
		$version = ! empty( $GLOBALS['aws_meta']['amazon-s3-and-cloudfront-pro']['version'] ) ? $GLOBALS['aws_meta']['amazon-s3-and-cloudfront-pro']['version'] : false;

		if ( ! $version ) {
			return false;
		}

		if ( ! function_exists( 'amazon_web_services_init' ) && version_compare( $version, '1.6' ) < 0 ) {
			// Old version, plugin Amazon Web Services is required.
			return false;
		}

		return true;
	}

	return false;
}

if ( is_admin() && imagify_load_as3cf_compat() ) :

	add_action( 'imagify_loaded', array( Imagify_AS3CF::get_instance(), 'init' ), 1 );

endif;
