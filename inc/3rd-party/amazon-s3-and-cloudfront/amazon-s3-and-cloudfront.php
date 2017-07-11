<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( is_admin() && ( function_exists( 'as3cf_init' ) || function_exists( 'as3cf_pro_init' ) ) ) :

	require( dirname( __FILE__ ) . '/inc/classes/class-imagify-as3cf.php' );
	require( dirname( __FILE__ ) . '/inc/classes/class-imagify-as3cf-attachment.php' );

	add_action( 'imagify_loaded', array( imagify_as3cf(), 'init' ), 1 );

endif;
