<?php
/*
Plugin Name: Imagify
Plugin URI: https://wordpress.org/plugins/imagify/
Description: Dramaticaly reduce image file sizes without losing quality, make your website load faster, boost your SEO and save money on your bandwith using Imagify, the new most advanced image optimization tool.
Version: 1.5.3
Author: WP Media
Author URI: http://wp-media.me
Licence: GPLv2

Text Domain: imagify
Domain Path: languages

Copyright 2015 WP Media
*/

defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

// Imagify defines
define( 'IMAGIFY_VERSION'	 			 , '1.5.3' );
define( 'IMAGIFY_SLUG'		 			 , 'imagify' );
define( 'IMAGIFY_SETTINGS_SLUG'			 , IMAGIFY_SLUG . '_settings' );
define( 'IMAGIFY_WEB_MAIN'	 			 , 'https://imagify.io' );
define( 'IMAGIFY_APP_MAIN'	 			 , 'https://app.imagify.io' );
define( 'IMAGIFY_FILE'            		 , __FILE__ );
define( 'IMAGIFY_PATH'       			 , realpath( plugin_dir_path( IMAGIFY_FILE ) ) . '/' );
define( 'IMAGIFY_INC_PATH'   			 , realpath( IMAGIFY_PATH . 'inc/' ) . '/' );
define( 'IMAGIFY_API_PATH'   			 , realpath( IMAGIFY_INC_PATH . 'api/' ) . '/' );
define( 'IMAGIFY_ADMIN_PATH' 			 , realpath( IMAGIFY_INC_PATH . 'admin' ) . '/' );
define( 'IMAGIFY_ADMIN_UI_PATH'     	 , realpath( IMAGIFY_ADMIN_PATH . 'ui' ) . '/' );
define( 'IMAGIFY_COMMON_PATH'    		 , realpath( IMAGIFY_INC_PATH . 'common' ) . '/' );
define( 'IMAGIFY_FUNCTIONS_PATH'    	 , realpath( IMAGIFY_INC_PATH . 'functions' ) . '/' );
define( 'IMAGIFY_CLASSES_PATH'    		 , realpath( IMAGIFY_INC_PATH . 'classes' ) . '/' );
define( 'IMAGIFY_CLASSES_ABSTRACTS_PATH' , realpath( IMAGIFY_CLASSES_PATH . 'abstracts' ) . '/' );
define( 'IMAGIFY_3RD_PARTY_PATH'  		 , realpath( IMAGIFY_INC_PATH . '3rd-party' ) . '/' );
define( 'IMAGIFY_URL'               	 , plugin_dir_url( IMAGIFY_FILE ) );
define( 'IMAGIFY_INC_URL'         		 , IMAGIFY_URL . 'inc/' );
define( 'IMAGIFY_ADMIN_URL'         	 , IMAGIFY_INC_URL . 'admin/' );
define( 'IMAGIFY_ASSETS_URL'      		 , IMAGIFY_URL . 'assets/' );
define( 'IMAGIFY_ASSETS_JS_URL'     	 , IMAGIFY_ASSETS_URL . 'js/' );
define( 'IMAGIFY_ASSETS_CSS_URL'    	 , IMAGIFY_ASSETS_URL . 'css/' );
define( 'IMAGIFY_ASSETS_IMG_URL'    	 , IMAGIFY_ASSETS_URL . 'images/' );
define( 'IMAGIFY_MAX_BYTES'  			 , 5242880 );

/*
 * Tell WP what to do when plugin is loaded
 *
 * @since 1.0
 */
add_action( 'plugins_loaded', '_imagify_init' );
function _imagify_init() {
    // Load translations
    load_plugin_textdomain( 'imagify', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    // Nothing to do if autosave
    if ( defined( 'DOING_AUTOSAVE' ) ) {
        return;
    }

    require( IMAGIFY_INC_PATH 				 . 'compat.php' );
    require( IMAGIFY_FUNCTIONS_PATH 		 . 'options.php' );
    require( IMAGIFY_API_PATH 				 . 'imagify.php' );
    require( IMAGIFY_FUNCTIONS_PATH 		 . 'formatting.php' );
    require( IMAGIFY_FUNCTIONS_PATH 		 . 'files.php' );
    require( IMAGIFY_FUNCTIONS_PATH 		 . 'admin.php' );
    require( IMAGIFY_FUNCTIONS_PATH 		 . 'api.php' );
    require( IMAGIFY_FUNCTIONS_PATH 		 . 'attachments.php' );
    require( IMAGIFY_FUNCTIONS_PATH 		 . 'process.php' );
    require( IMAGIFY_CLASSES_ABSTRACTS_PATH  . 'abstract-db.php' );
    require( IMAGIFY_CLASSES_ABSTRACTS_PATH  . 'abstract-attachment.php' );
    require( IMAGIFY_CLASSES_PATH 			 . 'class-user.php' );
    require( IMAGIFY_CLASSES_PATH 			 . 'class-attachment.php' );
    require( IMAGIFY_FUNCTIONS_PATH 		 . 'admin-ui.php' );
    require( IMAGIFY_FUNCTIONS_PATH 		 . 'admin-stats.php' );
    require( IMAGIFY_FUNCTIONS_PATH 		 . 'i18n.php' );
    require( IMAGIFY_COMMON_PATH 			 . 'attachments.php' );
    require( IMAGIFY_COMMON_PATH 			 . 'admin-bar.php' );
    require( IMAGIFY_COMMON_PATH 			 . 'cron.php' );
    require( IMAGIFY_3RD_PARTY_PATH 		 . '3rd-party.php' );

    if ( is_admin() ) {
        require( IMAGIFY_ADMIN_PATH 	. 'upgrader.php' );
        require( IMAGIFY_ADMIN_PATH 	. 'heartbeat.php' );
        require( IMAGIFY_ADMIN_PATH 	. 'ajax.php' );
        require( IMAGIFY_ADMIN_PATH 	. 'options.php' );
        require( IMAGIFY_ADMIN_PATH  	. 'menu.php' );
        require( IMAGIFY_ADMIN_PATH  	. 'plugins.php' );
        require( IMAGIFY_ADMIN_PATH  	. 'upload.php' );
        require( IMAGIFY_ADMIN_PATH  	. 'media.php' );
        require( IMAGIFY_ADMIN_PATH  	. 'enqueue.php' );
        require( IMAGIFY_ADMIN_PATH  	. 'meta-boxes.php' );
        require( IMAGIFY_ADMIN_UI_PATH  . 'options.php' );
        require( IMAGIFY_ADMIN_UI_PATH  . 'bulk.php' );
        require( IMAGIFY_ADMIN_UI_PATH  . 'notices.php' );
    }
			
    /**
	 * Fires when Imagify is correctly loaded
	 *
	 * @since 1.0
	*/
	do_action( 'imagify_loaded' );
}