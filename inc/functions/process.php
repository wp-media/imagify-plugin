<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

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
