<?php

use Imagify\ThirdParty\PerfectImages\PerfectImages;

if ( ! class_exists( 'Meow_WR2X_Core' ) ) {
	return;
}

PerfectImages::get_instance()->init();
