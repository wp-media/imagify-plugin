<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( ! class_exists( 'RegenerateThumbnails' ) || ! function_exists( 'RegenerateThumbnails' ) ) {
	return;
}

class_alias( '\\Imagify\\ThirdParty\\RegenerateThumbnails\\Main', '\\Imagify_Regenerate_Thumbnails' );

add_action( 'init', [ \Imagify\ThirdParty\RegenerateThumbnails\Main::get_instance(), 'init' ], 20 );
