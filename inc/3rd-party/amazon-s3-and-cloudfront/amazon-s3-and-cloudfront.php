<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( is_admin() && ( function_exists( 'as3cf_init' ) || function_exists( 'as3cf_pro_init' ) ) ) :

	add_action( 'imagify_loaded', array( Imagify_AS3CF::get_instance(), 'init' ), 1 );

endif;
