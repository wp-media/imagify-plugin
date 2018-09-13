<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( ! class_exists( 'C_NextGEN_Bootstrap' ) || ! class_exists( 'Mixin' ) || ! get_site_option( 'ngg_options' ) ) {
	return;
}

define( 'IMAGIFY_NGG_FILE'          , __FILE__ );
define( 'IMAGIFY_NGG_PATH'          , realpath( plugin_dir_path( IMAGIFY_NGG_FILE ) ) . '/' );
define( 'IMAGIFY_NGG_INC_PATH'      , realpath( IMAGIFY_NGG_PATH . 'inc/' ) . '/' );
define( 'IMAGIFY_NGG_ADMIN_PATH'    , realpath( IMAGIFY_NGG_INC_PATH . 'admin' ) . '/' );
define( 'IMAGIFY_NGG_COMMON_PATH'   , realpath( IMAGIFY_NGG_INC_PATH . 'common' ) . '/' );
define( 'IMAGIFY_NGG_FUNCTIONS_PATH', realpath( IMAGIFY_NGG_INC_PATH . 'functions' ) . '/' );

require IMAGIFY_NGG_FUNCTIONS_PATH . 'admin-stats.php';
require IMAGIFY_NGG_FUNCTIONS_PATH . 'attachments.php';
require IMAGIFY_NGG_FUNCTIONS_PATH . 'common.php';
require IMAGIFY_NGG_COMMON_PATH . 'attachments.php';

Imagify_NGG::get_instance()->init();
Imagify_NGG_DB::get_instance()->init();
Imagify_NGG_Dynamic_Thumbnails_Background_Process::get_instance()->init();

if ( is_admin() ) {
	require IMAGIFY_NGG_ADMIN_PATH . 'enqueue.php';
	require IMAGIFY_NGG_ADMIN_PATH . 'ajax.php';
	require IMAGIFY_NGG_ADMIN_PATH . 'menu.php';
	require IMAGIFY_NGG_ADMIN_PATH . 'gallery.php';
	require IMAGIFY_NGG_ADMIN_PATH . 'bulk.php';
	require IMAGIFY_NGG_ADMIN_PATH . 'heartbeat.php';
}
