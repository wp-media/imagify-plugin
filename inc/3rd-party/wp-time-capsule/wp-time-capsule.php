<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( defined( 'WPTC_VERSION' ) ) {
	return;
}

Imagify_WP_Time_Capsule::get_instance()->init();
