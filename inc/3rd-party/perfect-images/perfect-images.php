<?php

use Imagify\ThirdParty\PerfectImages\PerfectImages;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( ! class_exists( 'Meow_WR2X_Core' ) ) {
	return;
}

PerfectImages::get_instance()->init();
