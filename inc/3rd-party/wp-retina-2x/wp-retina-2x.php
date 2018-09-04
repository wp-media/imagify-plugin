<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( ! class_exists( 'Meow_WR2X_Core' ) ) {
	return;
}

Imagify_WP_Retina_2x::get_instance()->init();
