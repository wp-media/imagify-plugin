<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( function_exists( 'enable_media_replace' ) ) :

	add_filter( 'emr_unfiltered_get_attached_file', array( Imagify_Enable_Media_Replace::get_instance(), 'init' ) );

endif;
