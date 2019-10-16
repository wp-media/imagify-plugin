<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( function_exists( 'enable_media_replace' ) || class_exists( '\\EnableMediaReplace\\EnableMediaReplacePlugin' ) ) :

	class_alias( '\\Imagify\\ThirdParty\\EnableMediaReplace\\Main', '\\Imagify_Enable_Media_Replace' );

	add_filter( 'emr_unfiltered_get_attached_file', [ \Imagify\ThirdParty\EnableMediaReplace\Main::get_instance(), 'init' ] );

endif;
