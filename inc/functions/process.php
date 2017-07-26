<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

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
	$errors = new WP_Error();
	$args   = array_merge( array(
		'backup'             => get_imagify_option( 'backup', false ),
		'optimization_level' => get_imagify_option( 'optimization_level', 1 ),
		'keep_exif'          => get_imagify_option( 'exif', false ),
		'context'            => 'wp',
		'resized'            => false,
		'original_size'      => 0,
	), $args );

	/**
	 * Filter the attachment path.
	 *
	 * @since 1.2
	 *
	 * @param string $file_path The attachment path.
	 */
	$file_path = apply_filters( 'imagify_file_path', $file_path );

	// Check if the Imagify servers & the API are accessible.
	if ( ! is_imagify_servers_up() ) {
		$errors->add( 'api_server_down', __( 'Sorry, our servers are temporarily unaccessible. Please, try again in a couple of minutes.', 'imagify' ) );
		return $errors;
	}

	// Check if external HTTP requests are blocked.
	if ( is_imagify_blocked() ) {
		$errors->add( 'http_block_external', __( 'External HTTP requests are blocked', 'imagify' ) );
		return $errors;
	}

	// Check that file path isn't empty.
	if ( empty( $file_path ) ) {
		$errors->add( 'empty_path', __( 'File path is empty', 'imagify' ) );
		return $errors;
	}

	$filesystem = imagify_get_filesystem();

	// Check that the file exists.
	if ( ! $filesystem->exists( $file_path ) || ! $filesystem->is_file( $file_path ) ) {
		/* translators: %s is a file path. */
		$errors->add( 'file_not_found', sprintf( __( 'Could not find %s', 'imagify' ), $file_path ) );
		return $errors;
	}

	// Check that the file is writable.
	if ( ! wp_is_writable( dirname( $file_path ) ) ) {
		/* translators: %s is a file path. */
		$errors->add( 'not_writable', sprintf( __( '%s is not writable', 'imagify' ), dirname( $file_path ) ) );
		return $errors;
	}

	// Get file size.
	$file_size = $filesystem->size( $file_path );

	// Check that file exists.
	if ( 0 === $file_size ) {
		/* translators: %s is a file size. */
		$errors->add( 'image_not_found', sprintf( __( 'Skipped (%s), image not found.', 'imagify' ), size_format( $file_size ) ) );
		return $errors;
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
		$errors->add( 'api_error', $response->get_error_message() );
		return $errors;
	}

	// Create a backup file.
	if ( 'wp' === $args['context'] && $args['backup'] && ! $args['resized'] ) {
		// TODO (@Greg): Send an error message if the backup fails.
		imagify_backup_file( $file_path );
	}

	if ( ! function_exists( 'download_url' ) ) {
		require( ABSPATH . 'wp-admin/includes/file.php' );
	}

	$temp_file = download_url( $response->image );

	if ( is_wp_error( $temp_file ) ) {
		$errors->add( 'temp_file_not_found', $temp_file->get_error_message() );
		return $errors;
	}

	$filesystem->move( $temp_file, $file_path, true );
	imagify_chmod_file( $file_path );

	// If temp file still exists, delete it.
	if ( $filesystem->exists( $temp_file ) ) {
		$filesystem->delete( $temp_file );
	}

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
		'cookies'   => $_COOKIE,
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

	// Make sure the source file exists.
	if ( ! $filesystem->exists( $file_path ) ) {
		return new WP_Error( 'source_doesnt_exist', __( 'The file to backup does not exist.', 'imagify' ), array(
			'file_path' => imagify_make_file_path_replative( $file_path ),
		) );
	}

	if ( ! isset( $backup_path ) ) {
		// Make sure the backup directory is writable.
		if ( ! imagify_backup_dir_is_writable() ) {
			return new WP_Error( 'backup_dir_not_writable', __( 'The backup directory is not writable.', 'imagify' ) );
		}

		$backup_path = get_imagify_attachment_backup_path( $file_path );

		// Make sure the uploads directory has no errors.
		if ( ! $backup_path ) {
			return new WP_Error( 'wp_upload_error', __( 'Error while retrieving the uploads directory path.', 'imagify' ) );
		}
	}

	// Make sure the filesystem has no errors.
	if ( ! empty( $filesystem->errors->errors ) ) {
		return new WP_Error( 'filesystem_error', __( 'Filesystem error.', 'imagify' ), $filesystem->errors );
	}

	// Create sub-directories.
	wp_mkdir_p( dirname( $backup_path ) );

	// Copy the file.
	$filesystem->copy( $file_path, $backup_path, false, FS_CHMOD_FILE );

	// Make sure the backup copy exists.
	if ( ! $filesystem->exists( $backup_path ) ) {
		return new WP_Error( 'backup_doesnt_exist', __( 'The file could not be saved.', 'imagify' ), array(
			'file_path'   => imagify_make_file_path_replative( $file_path ),
			'backup_path' => imagify_make_file_path_replative( $backup_path ),
		) );
	}

	return true;
}
