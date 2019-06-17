<?php
use Imagify\ThirdParty\NGG;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( ! class_exists( 'C_NextGEN_Bootstrap' ) || ! class_exists( 'Mixin' ) || ! get_site_option( 'ngg_options' ) ) {
	return;
}

class_alias( '\\Imagify\\ThirdParty\\NGG\\Main', '\\Imagify_NGG' );
class_alias( '\\Imagify\\ThirdParty\\NGG\\DB', '\\Imagify_NGG_DB' );
class_alias( '\\Imagify\\ThirdParty\\NGG\\NGGStorage', '\\Imagify_NGG_Storage' );

require IMAGIFY_PATH . 'inc/3rd-party/nextgen-gallery/inc/functions/admin-stats.php';
require IMAGIFY_PATH . 'inc/3rd-party/nextgen-gallery/inc/functions/attachments.php';
require IMAGIFY_PATH . 'inc/3rd-party/nextgen-gallery/inc/functions/common.php';
require IMAGIFY_PATH . 'inc/3rd-party/nextgen-gallery/inc/common/attachments.php';

NGG\Main::get_instance()->init();
NGG\DB::get_instance()->init();

if ( is_admin() ) {
	require IMAGIFY_PATH . 'inc/3rd-party/nextgen-gallery/inc/admin/enqueue.php';
	require IMAGIFY_PATH . 'inc/3rd-party/nextgen-gallery/inc/admin/menu.php';
	require IMAGIFY_PATH . 'inc/3rd-party/nextgen-gallery/inc/admin/gallery.php';
	require IMAGIFY_PATH . 'inc/3rd-party/nextgen-gallery/inc/admin/bulk.php';
}
