<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Create a CURLFile object.
 *
 * @since PHP 5.5
 *
 * @param 	string $filename  Path to the file which will be uploaded.
 * @param 	string $mimetype  Mimetype of the file.
 * @param 	string $postname  Name of the file to be used in the upload data.
 * @return string 			  The CURLFile object.
 */
if ( ! function_exists( 'curl_file_create' ) ) {
    function curl_file_create( $filename, $mimetype = '', $postname = '' ) {
        return "@$filename;filename="
            . ( $postname ? $postname : basename( $filename ) )
            . ( $mimetype ? ";type=$mimetype" : '' );
    }
}