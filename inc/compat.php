<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( ! function_exists( 'curl_file_create' ) ) :

	/**
	 * Create a CURLFile object.
	 *
	 * @since 1.0
	 * @since PHP 5.5
	 *
	 * @param  string $filename Path to the file which will be uploaded.
	 * @param  string $mimetype Mimetype of the file.
	 * @param  string $postname Name of the file to be used in the upload data.
	 * @return string           The CURLFile object.
	 */
	function curl_file_create( $filename, $mimetype = '', $postname = '' ) {
		return "@$filename;filename="
			. ( $postname ? $postname : basename( $filename ) )
			. ( $mimetype ? ";type=$mimetype" : '' );
	}

endif;
