<?php
use Imagify\ThirdParty\FormidablePro;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( class_exists( 'FrmProEddController' ) ) :

	class_alias( '\\Imagify\\ThirdParty\\FormidablePro\\Main', '\\Imagify_Formidable_Pro' );

	FormidablePro\Main::get_instance()->init();

endif;
