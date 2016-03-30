<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

if ( ! class_exists( 'C_NextGEN_Bootstrap' ) ) {
	return;
}

define( 'IMAGIFY_NGG_FILE'          	, __FILE__ );
define( 'IMAGIFY_NGG_PATH'       		, realpath( plugin_dir_path( IMAGIFY_NGG_FILE ) ) . '/' );
define( 'IMAGIFY_NGG_INC_PATH'   		, realpath( IMAGIFY_NGG_PATH . 'inc/' ) . '/' );
define( 'IMAGIFY_NGG_ADMIN_PATH' 		, realpath( IMAGIFY_NGG_INC_PATH . 'admin' ) . '/' );
define( 'IMAGIFY_NGG_COMMON_PATH'    	, realpath( IMAGIFY_NGG_INC_PATH . 'common' ) . '/' );
define( 'IMAGIFY_NGG_CLASSES_PATH'    	, realpath( IMAGIFY_NGG_INC_PATH . 'classes' ) . '/' );

require( IMAGIFY_NGG_CLASSES_PATH . 'class-db.php' );

if ( is_admin() ) {
	require( IMAGIFY_NGG_ADMIN_PATH . 'db.php' );
}