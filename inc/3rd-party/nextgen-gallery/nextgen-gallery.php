<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

if ( ! class_exists( 'C_NextGEN_Bootstrap' ) || ! class_exists( 'Mixin' ) || ! get_site_option( 'ngg_options' ) ) {
	return;
}

define( 'IMAGIFY_NGG_FILE'          	, __FILE__ );
define( 'IMAGIFY_NGG_PATH'       		, realpath( plugin_dir_path( IMAGIFY_NGG_FILE ) ) . '/' );
define( 'IMAGIFY_NGG_INC_PATH'   		, realpath( IMAGIFY_NGG_PATH . 'inc/' ) . '/' );
define( 'IMAGIFY_NGG_ADMIN_PATH' 		, realpath( IMAGIFY_NGG_INC_PATH . 'admin' ) . '/' );
define( 'IMAGIFY_NGG_COMMON_PATH'    	, realpath( IMAGIFY_NGG_INC_PATH . 'common' ) . '/' );
define( 'IMAGIFY_NGG_FUNCTIONS_PATH'    , realpath( IMAGIFY_NGG_INC_PATH . 'functions' ) . '/' );
define( 'IMAGIFY_NGG_CLASSES_PATH'    	, realpath( IMAGIFY_NGG_INC_PATH . 'classes' ) . '/' );

require( IMAGIFY_NGG_CLASSES_PATH 	. 'class-ngg.php' );
require( IMAGIFY_NGG_CLASSES_PATH 	. 'class-db.php' );
require( IMAGIFY_NGG_CLASSES_PATH 	. 'class-attachment.php' );
require( IMAGIFY_NGG_FUNCTIONS_PATH . 'admin-stats.php' );
require( IMAGIFY_NGG_COMMON_PATH  	. 'attachments.php' );

if ( is_admin() ) {
	require( IMAGIFY_NGG_ADMIN_PATH . 'enqueue.php' );
	require( IMAGIFY_NGG_ADMIN_PATH . 'ajax.php' );
	require( IMAGIFY_NGG_ADMIN_PATH . 'db.php' );
	require( IMAGIFY_NGG_ADMIN_PATH . 'menu.php' );
	require( IMAGIFY_NGG_ADMIN_PATH . 'gallery.php' );
	require( IMAGIFY_NGG_ADMIN_PATH . 'bulk.php' );
	require( IMAGIFY_NGG_ADMIN_PATH . 'heartbeat.php' );
}