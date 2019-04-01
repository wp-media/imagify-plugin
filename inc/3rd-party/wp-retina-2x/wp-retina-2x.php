<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( ! class_exists( 'Meow_WR2X_Core' ) ) {
	return;
}

class_alias( '\\Imagify\\ThirdParty\\WPR2X\\Main', '\\Imagify_WP_Retina_2x' );
class_alias( '\\Imagify\\ThirdParty\\WPR2X\\Core', '\\Imagify_WP_Retina_2x_Core' );

\Imagify\ThirdParty\WPR2X\Main::get_instance()->init();
