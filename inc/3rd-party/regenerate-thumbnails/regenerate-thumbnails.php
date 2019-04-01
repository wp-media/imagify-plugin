<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( ! class_exists( 'RegenerateThumbnails_Regenerator' ) || ! function_exists( 'RegenerateThumbnails' ) ) {
	return;
}

Imagify_Regenerate_Thumbnails::get_instance()->init();
