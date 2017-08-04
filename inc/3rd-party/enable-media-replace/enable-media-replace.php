<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( function_exists( 'enable_media_replace' ) ) :

	require( dirname( __FILE__ ) . '/inc/classes/class-imagify-enable-media-replace.php' );

	add_filter( 'emr_unfiltered_get_attached_file', array( imagify_enable_media_replace(), 'init' ) );

endif;
