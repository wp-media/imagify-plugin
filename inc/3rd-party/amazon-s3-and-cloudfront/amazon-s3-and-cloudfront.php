<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( function_exists( 'as3cf_init' ) && is_admin() ) :

	require( dirname( __FILE__ ) . '/inc/classes/class-imagify-as3cf.php' );
	require( dirname( __FILE__ ) . '/inc/classes/class-imagify-as3cf-attachment.php' );

	add_action( 'imagify_loaded', 'imagify_as3cf', 1 );

endif;
