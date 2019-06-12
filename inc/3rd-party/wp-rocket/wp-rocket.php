<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( defined( 'WP_ROCKET_VERSION' ) ) :

	\Imagify\ThirdParty\WPRocket\Main::get_instance()->init();

endif;
