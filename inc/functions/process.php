<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Process an image with Imagify.
 *
 * @since 1.0
 *
 * @param  string $file_path Absolute path to the image file.
 * @param  array  $args      An array that can contain:
 *                           bool $backup              Force a backup of the original file.
 *                           int  $optimization_level  The optimization level (2=ultra, 1=aggressive, 0=normal).
 *                           bool $keep_exif           To keep exif data or not.
 * @return obj|array         Error message | Optimized image data.
 */
function do_imagify( $file_path, $args = array() ) {
	$args = array_merge( array(
		'backup'             => get_imagify_option( 'backup' ),
		'optimization_level' => get_imagify_option( 'optimization_level' ),
		'keep_exif'          => get_imagify_option( 'exif' ),
		'context'            => 'wp',
		'resized'            => false,
		'original_size'      => 0,
		'backup_path'        => null,
	), $args );

	/**
	 * Filter the attachment path.
	 *
	 * @since 1.2
	 *
	 * @param string $file_path The attachment path.
	 */
	$file_path = apply_filters( 'imagify_file_path', $file_path );

	// Check that file path isn't empty.
	if ( ! $file_path ) {
		return new WP_Error( 'empty_path', __( 'File path is empty.', 'imagify' ) );
	}

	// Check if curl is available.
	if ( ! Imagify_Requirements::supports_curl() ) {
		return new WP_Error( 'curl', __( 'cURL is not available on the server.', 'imagify' ) );
	}

	$filesystem = imagify_get_filesystem();

	// Check if imageMagick or GD is available.
	if ( $filesystem->is_image( $file_path ) && ! Imagify_Requirements::supports_image_editor() ) {
		return new WP_Error( 'image_editor', sprintf(
			/* translators: %s is a "More info?" link. */
			__( 'No php extensions are available to edit images on the server. ImageMagick or GD is required. %s', 'imagify' ),
			'<a href="' . esc_url( imagify_get_external_url( 'documentation-imagick-gd' ) ) . '" target="_blank">' . __( 'More info?', 'imagify' ) . '</a>'
		) );
	}

	// Check if external HTTP requests are blocked.
	if ( Imagify_Requirements::is_imagify_blocked() ) {
		return new WP_Error( 'http_block_external', __( 'External HTTP requests are blocked.', 'imagify' ) );
	}

	// Check if the Imagify servers & the API are accessible.
	if ( ! Imagify_Requirements::is_api_up() ) {
		return new WP_Error( 'api_server_down', __( 'Sorry, our servers are temporarily unavailable. Please, try again in a couple of minutes.', 'imagify' ) );
	}

	// Check that the file exists.
	if ( ! $filesystem->is_writable( $file_path ) || ! $filesystem->is_file( $file_path ) ) {
		/* translators: %s is a file path. */
		return new WP_Error( 'file_not_found', sprintf( __( 'Could not find %s.', 'imagify' ), $filesystem->make_path_relative( $file_path ) ) );
	}

	// Check that the file directory is writable.
	if ( ! $filesystem->is_writable( $filesystem->dir_path( $file_path ) ) ) {
		/* translators: %s is a file path. */
		return new WP_Error( 'not_writable', sprintf( __( '%s is not writable.', 'imagify' ), $filesystem->make_path_relative( $filesystem->dir_path( $file_path ) ) ) );
	}

	/**
	 * Fires before to optimize the Image with Imagify.
	 *
	 * @since 1.0
	 *
	 * @param string $file_path Absolute path to the image file.
	 * @param bool   $backup    Force a backup of the original file.
	*/
	do_action( 'before_do_imagify', $file_path, $args['backup'] );

	// Create a backup file before sending to optimization (to make sure we can backup the file).
	$do_backup = $args['backup'] && ! $args['resized'];

	if ( $do_backup ) {
		$backup_result = imagify_backup_file( $file_path, $args['backup_path'] );

		if ( is_wp_error( $backup_result ) ) {
			// Stop the process if we can't backup the file.
			return $backup_result;
		}
	}

	// Send image for optimization and fetch the response.
	$response = upload_imagify_image( array(
		'image' => $file_path,
		'data'  => wp_json_encode( array(
			'aggressive'    => ( 1 === (int) $args['optimization_level'] ),
			'ultra'         => ( 2 === (int) $args['optimization_level'] ),
			'keep_exif'     => $args['keep_exif'],
			'context'       => $args['context'],
			'original_size' => $args['original_size'],
		) ),
	) );

	// Check status code.
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'api_error', $response->get_error_message() );
	}

	if ( ! function_exists( 'download_url' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$temp_file = download_url( $response->image );

	if ( is_wp_error( $temp_file ) ) {
		return new WP_Error( 'temp_file_not_found', $temp_file->get_error_message() );
	}

	$filesystem->move( $temp_file, $file_path, true );

	/**
	 * Fires after to optimize the Image with Imagify.
	 *
	 * @since 1.0
	 *
	 * @param string $file_path Absolute path to the image file.
	 * @param bool   $backup    Force a backup of the original file.
	*/
	do_action( 'after_do_imagify', $file_path, $args['backup'] );

	return $response;
}

/**
 * Run an async job to optimize images in background.
 *
 * @param array $body Contains the usual $_POST.
 *
 * @since 1.4
 */
function imagify_do_async_job( $body ) {
	$args = array(
		'timeout'   => 0.01,
		'blocking'  => false,
		'body'      => $body,
		'cookies'   => isset( $_COOKIE ) && is_array( $_COOKIE ) ? $_COOKIE : array(),
		'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
	);

	/**
	 * Filter the arguments used to launch an async job.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param array $args An array of arguments passed to wp_remote_post().
	 */
	$args = apply_filters( 'imagify_do_async_job_args', $args );

	/**
	 * It can be a XML-RPC request. The problem is that XML-RPC doesn't use cookies.
	 */
	if ( defined( 'XMLRPC_REQUEST' ) && get_current_user_id() ) {
		/**
		 * In old WP versions, the field "option_name" in the wp_options table was limited to 64 characters.
		 * From 64, remove 19 characters for "_transient_timeout_" = 45.
		 * Then remove 12 characters for "imagify_rpc_" (transient name) = 33.
		 * Hopefully, a md5 is 32 characters long.
		 */
		$rpc_id = md5( maybe_serialize( $body ) );

		// Send the request to our RPC bridge instead.
		$args['body']['imagify_rpc_action'] = $args['body']['action'];
		$args['body']['action']             = 'imagify_rpc';
		$args['body']['imagify_rpc_id']     = $rpc_id;
		$args['body']['imagify_rpc_nonce']  = wp_create_nonce( 'imagify_rpc_' . $rpc_id );

		// Since we can't send cookies to keep the user logged in, store the user ID in a transient.
		set_transient( 'imagify_rpc_' . $rpc_id, get_current_user_id(), 30 );
	}

	wp_remote_post( admin_url( 'admin-ajax.php' ), $args );
}

/**
 * Backup a file.
 *
 * @since  1.6.8
 * @author Grégory Viguier
 *
 * @param  string $file_path   The file path.
 * @param  string $backup_path The backup path. This is useful for NGG for example, who doesn't store the backups in our backup folder.
 * @return bool|object         True on success. False if the backup option is not enabled. A WP_Error object on failure.
 */
function imagify_backup_file( $file_path, $backup_path = null ) {
	if ( ! get_imagify_option( 'backup' ) ) {
		return false;
	}

	// Make sure the source path is not empty.
	if ( ! $file_path ) {
		return new WP_Error( 'empty_path', __( 'The file path is empty.', 'imagify' ) );
	}

	$filesystem = imagify_get_filesystem();

	// Make sure the filesystem has no errors.
	if ( ! empty( $filesystem->errors->errors ) ) {
		return new WP_Error( 'filesystem_error', __( 'Filesystem error.', 'imagify' ), $filesystem->errors );
	}

	// Make sure the source file exists.
	if ( ! $filesystem->exists( $file_path ) ) {
		return new WP_Error( 'source_doesnt_exist', __( 'The file to backup does not exist.', 'imagify' ), array(
			'file_path' => $filesystem->make_path_relative( $file_path ),
		) );
	}

	if ( ! isset( $backup_path ) ) {
		// Make sure the backup directory is writable.
		if ( ! Imagify_Requirements::attachments_backup_dir_is_writable() ) {
			return new WP_Error( 'backup_dir_not_writable', __( 'The backup directory is not writable.', 'imagify' ) );
		}

		$backup_path = get_imagify_attachment_backup_path( $file_path );
	}

	// Make sure the uploads directory has no errors.
	if ( ! $backup_path ) {
		return new WP_Error( 'wp_upload_error', __( 'Error while retrieving the uploads directory path.', 'imagify' ) );
	}

	// Create sub-directories.
	$filesystem->make_dir( $filesystem->dir_path( $backup_path ) );

	/**
	 * Allow to overwrite the backup file if it already exists.
	 *
	 * @since  1.6.9
	 * @author Grégory Viguier
	 *
	 * @param bool   $overwrite   Whether to overwrite the backup file.
	 * @param string $file_path   The file path.
	 * @param string $backup_path The backup path.
	 */
	$overwrite = apply_filters( 'imagify_backup_overwrite_backup', false, $file_path, $backup_path );

	// Copy the file.
	$filesystem->copy( $file_path, $backup_path, $overwrite, FS_CHMOD_FILE );

	// Make sure the backup copy exists.
	if ( ! $filesystem->exists( $backup_path ) ) {
		return new WP_Error( 'backup_doesnt_exist', __( 'The file could not be saved.', 'imagify' ), array(
			'file_path'   => $filesystem->make_path_relative( $file_path ),
			'backup_path' => $filesystem->make_path_relative( $backup_path ),
		) );
	}

	return true;
}
