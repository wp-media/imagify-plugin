<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Get all mime types which could be optimized by Imagify.
 *
 * @since 1.3
 * @since 1.7 Deprecated.
 * @deprecated
 *
 * @return array $mime_type  The mime type.
 */
function get_imagify_mime_type() {
	_deprecated_function( __FUNCTION__ . '()', '1.7', 'imagify_get_mime_types()' );

	return imagify_get_mime_types();
}

/**
 * Planning cron.
 * If the task is not programmed, it is automatically triggered.
 *
 * @since 1.4.2
 * @since 1.7 Deprecated.
 * @deprecated
 */
function _imagify_rating_scheduled() {
	_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Cron_Rating::get_instance()->schedule_event()' );

	Imagify_Cron_Rating::get_instance()->schedule_event();
}

/**
 * Save the user images count to display it later in a notice message to ask him to rate Imagify on WordPress.org.
 *
 * @since 1.4.2
 * @since 1.7 Deprecated.
 * @deprecated
 */
function _do_imagify_rating_cron() {
	_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Cron_Rating::get_instance()->do_event()' );

	Imagify_Cron_Rating::get_instance()->do_event();
}

/**
 * Adds weekly interval for cron jobs.
 *
 * @since  1.6
 * @since  1.7 Deprecated.
 * @author Remy Perona
 * @deprecated
 *
 * @param  Array $schedules An array of intervals used by cron jobs.
 * @return Array Updated array of intervals.
 */
function imagify_purge_cron_schedule( $schedules ) {
	_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Cron_Library_Size::get_instance()->maybe_add_recurrence( $schedules )' );

	return Imagify_Cron_Library_Size::get_instance()->do_event( $schedules );
}

/**
 * Planning cron task to update weekly the size of the images and the size of images uploaded by month.
 * If the task is not programmed, it is automatically triggered.
 *
 * @since  1.6
 * @since  1.7 Deprecated.
 * @author Remy Perona
 * @deprecated
 */
function _imagify_update_library_size_calculations_scheduled() {
	_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Cron_Library_Size::get_instance()->schedule_event()' );

	Imagify_Cron_Library_Size::get_instance()->schedule_event();
}

/**
 * Cron task to update weekly the size of the images and the size of images uploaded by month.
 *
 * @since  1.6
 * @since  1.7 Deprecated.
 * @author Remy Perona
 * @deprecated
 */
function _do_imagify_update_library_size_calculations() {
	_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Cron_Library_Size::get_instance()->do_event()' );

	Imagify_Cron_Library_Size::get_instance()->do_event();
}

/**
 * Set a file permissions using FS_CHMOD_FILE.
 *
 * @since 1.2
 * @since 1.6.5 Use WP Filesystem.
 * @since 1.7.1 Deprecated.
 * @deprecated
 *
 * @param  string $file_path Path to the file.
 * @return bool              True on success, false on failure.
 */
function imagify_chmod_file( $file_path ) {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'imagify_get_filesystem()->chmod_file( $file_path )' );

	return imagify_get_filesystem()->chmod_file( $file_path );
}

/**
 * Get a file mime type.
 *
 * @since  1.6.9
 * @since  1.7 Doesn't use exif_imagetype() nor getimagesize() anymore.
 * @since  1.7.1 Deprecated.
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  string $file_path A file path (prefered) or a filename.
 * @return string|bool       A mime type. False on failure: the test is limited to mime types supported by Imagify.
 */
function imagify_get_mime_type_from_file( $file_path ) {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'imagify_get_filesystem()->get_mime_type( $file_path )' );

	return imagify_get_filesystem()->get_mime_type( $file_path );
}

/**
 * Get a file modification date, formated as "mysql". Fallback to current date.
 *
 * @since  1.7
 * @since  1.7.1 Deprecated.
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  string $file_path The file path.
 * @return string            The date.
 */
function imagify_get_file_date( $file_path ) {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'imagify_get_filesystem()->get_date( $file_path )' );

	return imagify_get_filesystem()->get_date( $file_path );
}

/**
 * Get a clean value of ABSPATH that can be used in string replacements.
 *
 * @since  1.6.8
 * @since  1.7.1 Deprecated.
 * @author Grégory Viguier
 * @deprecated
 *
 * @return string The path to WordPress' root folder.
 */
function imagify_get_abspath() {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'imagify_get_filesystem()->get_abspath()' );

	return imagify_get_filesystem()->get_abspath();
}

/**
 * Make an absolute path relative to WordPress' root folder.
 * Also works for files from registered symlinked plugins.
 *
 * @since  1.6.10
 * @since  1.7 The parameter $base is added.
 * @since  1.7.1 Deprecated.
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  string $file_path An absolute path.
 * @param  string $base      A base path to use instead of ABSPATH.
 * @return string|bool       A relative path. Can return the absolute path or false in case of a failure.
 */
function imagify_make_file_path_relative( $file_path, $base = '' ) {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'imagify_get_filesystem()->make_path_relative( $file_path, $base )' );

	return imagify_get_filesystem()->make_path_relative( $file_path, $base );
}

/**
 * Tell if a file is symlinked.
 *
 * @since  1.7
 * @since  1.7.1 Deprecated.
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  string $file_path An absolute path.
 * @return bool
 */
function imagify_file_is_symlinked( $file_path ) {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'imagify_get_filesystem()->is_symlinked( $file_path )' );

	return imagify_get_filesystem()->is_symlinked( $file_path );
}

/**
 * Determine if the Imagify API key is valid.
 *
 * @since 1.0
 * @since 1.7.1 Deprecated.
 * @deprecated
 *
 * @return bool True if the API key is valid.
 */
function imagify_valid_key() {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'Imagify_Requirements::is_api_key_valid()' );

	return Imagify_Requirements::is_api_key_valid();
}

/**
 * Check if external requests are blocked for Imagify.
 *
 * @since 1.0
 * @since 1.7.1 Deprecated.
 * @deprecated
 *
 * @return bool True if Imagify API can't be called.
 */
function is_imagify_blocked() {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'Imagify_Requirements::is_imagify_blocked()' );

	return Imagify_Requirements::is_imagify_blocked();
}

/**
 * Determine if the Imagify API is available by checking the API version.
 *
 * @since 1.0
 * @since 1.7.1 Deprecated.
 * @deprecated
 *
 * @return bool True if the Imagify API is available.
 */
function is_imagify_servers_up() {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'Imagify_Requirements::is_api_up()' );

	return Imagify_Requirements::is_api_up();
}

/**
 * Auto-optimize when a new attachment is generated.
 *
 * @since 1.0
 * @since 1.5 Async job.
 * @since 1.8.4 Deprecated
 * @see   Imagify_Admin_Ajax_Post_Deprecated::imagify_async_optimize_upload_new_media_callback()
 * @deprecated
 *
 * @param  array $metadata      An array of attachment meta data.
 * @param  int   $attachment_id Current attachment ID.
 * @return array
 */
function _imagify_optimize_attachment( $metadata, $attachment_id ) {
	_deprecated_function( __FUNCTION__ . '()', '1.8.4', 'Imagify_Auto_Optimization::get_instance()->store_upload_ids()' );

	if ( ! Imagify_Requirements::is_api_key_valid() || ! get_imagify_option( 'auto_optimize' ) ) {
		return $metadata;
	}

	/**
	 * Allow to prevent automatic optimization for a specific attachment.
	 *
	 * @since  1.6.12
	 * @author Grégory Viguier
	 *
	 * @param bool  $optimize      True to optimize, false otherwise.
	 * @param int   $attachment_id Attachment ID.
	 * @param array $metadata      An array of attachment meta data.
	 */
	$optimize = apply_filters( 'imagify_auto_optimize_attachment', true, $attachment_id, $metadata );

	if ( ! $optimize ) {
		return $metadata;
	}

	$context     = 'wp';
	$action      = 'imagify_async_optimize_upload_new_media';
	$_ajax_nonce = wp_create_nonce( 'new_media-' . $attachment_id );

	imagify_do_async_job( compact( 'action', '_ajax_nonce', 'metadata', 'attachment_id', 'context' ) );

	return $metadata;
}

/**
 * Optimize an attachment after being resized.
 *
 * @since 1.3.6
 * @since 1.4 Async job.
 * @since 1.8.4 Deprecated
 * @deprecated
 */
function _imagify_optimize_save_image_editor_file() {
	_deprecated_function( __FUNCTION__ . '()', '1.8.4' );

	if ( ! isset( $_POST['action'], $_POST['do'], $_POST['postid'] ) || 'image-editor' !== $_POST['action'] || 'open' === $_POST['do'] ) { // WPCS: CSRF ok.
		return;
	}

	$attachment_id = absint( $_POST['postid'] );

	if ( ! $attachment_id || ! Imagify_Requirements::is_api_key_valid() ) {
		return;
	}

	check_ajax_referer( 'image_editor-' . $attachment_id );

	$attachment = get_imagify_attachment( 'wp', $attachment_id, 'save_image_editor_file' );

	if ( ! $attachment->get_data() ) {
		return;
	}

	$body           = $_POST;
	$body['action'] = 'imagify_async_optimize_save_image_editor_file';

	imagify_do_async_job( $body );
}


/**
 * Display an admin notice informing that the current WP version is lower than the required one.
 *
 * @since  1.8.1
 * @since  1.9 Deprecated
 * @author Grégory Viguier
 * @deprecated
 */
function imagify_wp_version_notice() {
	global $wp_version;

	_deprecated_function( __FUNCTION__ . '()', '1.9', 'Imagify_Requirements_Check->print_notice()' );

	if ( is_multisite() ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$is_active = is_plugin_active_for_network( plugin_basename( IMAGIFY_FILE ) );
		$capacity  = $is_active ? 'manage_network_options' : 'manage_options';
	} else {
		$capacity = 'manage_options';
	}

	if ( ! current_user_can( $capacity ) ) {
		return;
	}

	echo '<div class="error notice"><p>';
	echo '<strong>' . __( 'Notice:', 'imagify' ) . '</strong> ';
	/* translators: 1 is this plugin name, 2 is the required WP version, 3 is the current WP version. */
	printf( __( '%1$s requires WordPress %2$s minimum, your website is actually running version %3$s.', 'imagify' ), '<strong>Imagify</strong>', '<code>' . IMAGIFY_WP_MIN . '</code>', '<code>' . $wp_version . '</code>' );
	echo '</p></div>';
}

/**
 * Delete the backup file when an attachement is deleted.
 *
 * @since 1.0
 * @since 1.9 Deprecated
 * @deprecated
 *
 * @param int $post_id Attachment ID.
 */
function _imagify_delete_backup_file( $post_id ) {
	_deprecated_function( __FUNCTION__ . '()', '1.9', 'imagify_cleanup_after_media_deletion( $post_id )' );

	get_imagify_attachment( 'wp', $post_id, 'delete_attachment' )->delete_backup();
}

/**
 * Classes autoloader.
 *
 * @since  1.6.12
 * @since  1.9 Deprecated
 * @author Grégory Viguier
 * @deprecated
 *
 * @param string $class Name of the class to include.
 */
function imagify_autoload( $class ) {
	static $strtolower;

	_deprecated_function( __FUNCTION__ . '()', '1.9' );

	if ( ! isset( $strtolower ) ) {
		$strtolower = function_exists( 'mb_strtolower' ) ? 'mb_strtolower' : 'strtolower';
	}

	// Generic classes.
	$classes = array(
		'Imagify_Abstract_Attachment'         => 1,
		'Imagify_Abstract_Background_Process' => 1,
		'Imagify_Abstract_Cron'               => 1,
		'Imagify_Abstract_DB'                 => 1,
		'Imagify_Abstract_Options'            => 1,
		'Imagify_Admin_Ajax_Post'             => 1,
		'Imagify_Assets'                      => 1,
		'Imagify_Attachment'                  => 1,
		'Imagify_Auto_Optimization'           => 1,
		'Imagify_Cron_Library_Size'           => 1,
		'Imagify_Cron_Rating'                 => 1,
		'Imagify_Cron_Sync_Files'             => 1,
		'Imagify_Custom_Folders'              => 1,
		'Imagify_Data'                        => 1,
		'Imagify_DB'                          => 1,
		'Imagify_File_Attachment'             => 1,
		'Imagify_Files_DB'                    => 1,
		'Imagify_Files_Iterator'              => 1,
		'Imagify_Files_List_Table'            => 1,
		'Imagify_Files_Recursive_Iterator'    => 1,
		'Imagify_Files_Scan'                  => 1,
		'Imagify_Files_Stats'                 => 1,
		'Imagify_Filesystem'                  => 1,
		'Imagify_Folders_DB'                  => 1,
		'Imagify_Notices'                     => 1,
		'Imagify_Options'                     => 1,
		'Imagify_Requirements'                => 1,
		'Imagify_Settings'                    => 1,
		'Imagify_User'                        => 1,
		'Imagify_Views'                       => 1,
		'Imagify'                             => 1,
	);

	if ( isset( $classes[ $class ] ) ) {
		$class = str_replace( '_', '-', call_user_func( $strtolower, $class ) );
		include IMAGIFY_PATH . 'inc/classes/class-' . $class . '.php';
		return;
	}

	// Third party classes.
	$classes = array(
		'Imagify_AS3CF_Attachment'                          => 'amazon-s3-and-cloudfront',
		'Imagify_AS3CF'                                     => 'amazon-s3-and-cloudfront',
		'Imagify_Enable_Media_Replace'                      => 'enable-media-replace',
		'Imagify_Formidable_Pro'                            => 'formidable-pro',
		'Imagify_NGG_Attachment'                            => 'nextgen-gallery',
		'Imagify_NGG_DB'                                    => 'nextgen-gallery',
		'Imagify_NGG_Dynamic_Thumbnails_Background_Process' => 'nextgen-gallery',
		'Imagify_NGG_Storage'                               => 'nextgen-gallery',
		'Imagify_NGG'                                       => 'nextgen-gallery',
		'Imagify_Regenerate_Thumbnails'                     => 'regenerate-thumbnails',
		'Imagify_WP_Retina_2x'                              => 'wp-retina-2x',
		'Imagify_WP_Retina_2x_Core'                         => 'wp-retina-2x',
		'Imagify_WP_Time_Capsule'                           => 'wp-time-capsule',
	);

	if ( isset( $classes[ $class ] ) ) {
		$folder = $classes[ $class ];
		$class  = str_replace( '_', '-', call_user_func( $strtolower, $class ) );
		include IMAGIFY_PATH . 'inc/3rd-party/' . $folder . '/inc/classes/class-' . $class . '.php';
	}
}

/**
 * Tell if the attachment has the required WP metadata.
 *
 * @since  1.6.12
 * @since  1.7 Also checks that the '_wp_attached_file' meta is valid (not a URL or anything funny).
 * @since  1.9 Deprecated
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  int $attachment_id The attachment ID.
 * @return bool
 */
function imagify_attachment_has_required_metadata( $attachment_id ) {
	_deprecated_function( __FUNCTION__ . '()', '1.9', '( new Imagify\\Media\\WP( $attachment_id ) )->has_required_media_data() )' );

	$file = get_post_meta( $attachment_id, '_wp_attached_file', true );

	if ( ! $file || preg_match( '@://@', $file ) || preg_match( '@^.:\\\@', $file ) ) {
		return false;
	}

	return (bool) wp_get_attachment_metadata( $attachment_id, true );
}

/**
 * Get the default Bulk Optimization buffer size.
 *
 * @since  1.5.10
 * @since  1.7 Added $sizes parameter.
 * @since  1.9 Deprecated
 * @author Jonathan Buttigieg
 * @deprecated
 *
 * @param  int $sizes Number of image sizes per item (attachment).
 * @return int        The buffer size.
 */
function get_imagify_bulk_buffer_size( $sizes = false ) {
	_deprecated_function( __FUNCTION__ . '()', '1.9' );

	if ( ! $sizes ) {
		$sizes = count( get_imagify_thumbnail_sizes() );
	}

	switch ( true ) {
		case ( $sizes >= 10 ):
			return 1;

		case ( $sizes >= 8 ):
			return 2;

		case ( $sizes >= 6 ):
			return 3;

		default:
			return 4;
	}
}

/**
 * Get the Imagify attachment class name depending to a context.
 *
 * @since  1.5
 * @since  1.6.6 $attachment_id and $identifier have been added.
 * @since  1.9 Deprecated
 * @author Jonathan Buttigieg
 * @deprecated
 *
 * @param  string $context       The context to determine the class name.
 * @param  int    $attachment_id The attachment ID.
 * @param  string $identifier    An identifier.
 * @return string                The Imagify attachment class name.
 */
function get_imagify_attachment_class_name( $context, $attachment_id, $identifier ) {
	_deprecated_function( __FUNCTION__ . '()', '1.9', 'imagify_get_optimization_process_class_name( $context )' );

	$context = $context ? $context : 'wp';

	if ( 'wp' !== $context && 'wp' === strtolower( $context ) ) {
		$context = 'wp';
	}

	/**
	 * Filter the context used for the optimization.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param string $context       The context.
	 * @param int    $attachment_id The attachment ID.
	 * @param string $identifier    An identifier.
	 */
	$context = apply_filters( 'imagify_optimize_attachment_context', $context, $attachment_id, $identifier );

	return 'Imagify_' . ( 'wp' !== $context ? $context . '_' : '' ) . 'Attachment';
}

/**
 * Get the Imagify attachment instance depending to a context.
 *
 * @since  1.6.13
 * @since  1.9 Deprecated
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  string $context       The context to determine the class name.
 * @param  int    $attachment_id The attachment ID.
 * @param  string $identifier    An identifier.
 * @return object                The Imagify attachment instance.
 */
function get_imagify_attachment( $context, $attachment_id, $identifier ) {
	_deprecated_function( __FUNCTION__ . '()', '1.9', 'imagify_get_optimization_process( $media_id, $context )' );

	$class_name = get_imagify_attachment_class_name( $context, $attachment_id, $identifier );
	return new $class_name( $attachment_id );
}

/**
 * Optimize a file with Imagify.
 *
 * @since 1.0
 * @since 1.9 Deprecated
 * @deprecated
 *
 * @param  string $file_path Absolute path to the file.
 * @param  array  $args      {
 *     Optional. An array of arguments.
 *
 *     @type bool $backup              Force a backup of the original file.
 *     @type int  $optimization_level  The optimization level (2=ultra, 1=aggressive, 0=normal).
 *     @type bool $keep_exif           To keep exif data or not.
 * }
 * @return array|WP_Error    Optimized image data. A WP_Error object on error.
 */
function do_imagify( $file_path, $args = array() ) {
	_deprecated_function( __FUNCTION__ . '()', '1.9', '(new Imagify\\Optimization\\File( $file_path ))->optimize( $args )' );

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
 * Backup a file.
 *
 * @since  1.6.8
 * @since  1.9 Deprecated
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  string $file_path   The file path.
 * @param  string $backup_path The backup path. This is useful for NGG for example, who doesn't store the backups in our backup folder.
 * @return bool|object         True on success. False if the backup option is not enabled. A WP_Error object on failure.
 */
function imagify_backup_file( $file_path, $backup_path = null ) {
	_deprecated_function( __FUNCTION__ . '()', '1.9', '(new Imagify\\Optimization\\File( $file_path ))->backup( $backup_path )' );

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

if ( is_admin() ) :

	/**
	 * Fix the capability for our capacity filter hook
	 *
	 * @since  1.0
	 * @since  1.7 Deprecated.
	 * @author Jonathan
	 * @deprecated
	 */
	function _imagify_correct_capability_for_options_page() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Settings::get_instance()->get_capability()' );

		return Imagify_Settings::get_instance()->get_capability();
	}

	/**
	 * Tell to WordPress to be confident with our setting, we are clean!
	 *
	 * @since  1.0
	 * @since  1.7 Deprecated.
	 * @author Jonathan
	 * @deprecated
	 */
	function _imagify_register_setting() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Settings::get_instance()->register()' );

		Imagify_Settings::get_instance()->register();
	}

	/**
	 * Filter specific options before its value is (maybe) serialized and updated.
	 *
	 * @since  1.0
	 * @since  1.7 Deprecated.
	 * @author Jonathan
	 * @deprecated
	 *
	 * @param  mixed $value     The new option value.
	 * @param  mixed $old_value The old option value.
	 * @return array The new option value.
	 */
	function _imagify_pre_update_option( $value, $old_value ) {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Settings::get_instance()->sanitize_and_validate( $value )' );

		return Imagify_Settings::get_instance()->sanitize_and_validate( $value );
	}

	/**
	 * If the user clicked the "Save & Go to Bulk Optimizer" button, set a redirection to the bulk optimizer.
	 * We use this hook because it can be triggered even if the option value hasn't changed.
	 *
	 * @since  1.6.8
	 * @since  1.7 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  mixed $value     The new, unserialized option value.
	 * @param  mixed $old_value The old option value.
	 * @return mixed            The option value.
	 */
	function _imagify_maybe_set_redirection_before_save_options( $value, $old_value ) {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Settings::get_instance()->maybe_set_redirection( $value, $old_value )' );

		return Imagify_Settings::get_instance()->maybe_set_redirection( $value, $old_value );
	}

	/**
	 * Used to launch some actions after saving the network options.
	 *
	 * @since  1.6.5
	 * @since  1.7 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param string $option     Name of the network option.
	 * @param mixed  $value      Current value of the network option.
	 * @param mixed  $old_value  Old value of the network option.
	 */
	function _imagify_after_save_network_options( $option, $value, $old_value ) {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Settings::get_instance()->after_save_network_options( $option, $value, $old_value )' );

		Imagify_Settings::get_instance()->after_save_network_options( $option, $value, $old_value );
	}

	/**
	 * Used to launch some actions after saving the options.
	 *
	 * @since  1.0
	 * @since  1.5    Used to redirect user to Bulk Optimizer (if requested).
	 * @since  1.6.8  Not used to redirect user to Bulk Optimizer anymore: see _imagify_maybe_set_redirection_before_save_options().
	 * @since  1.7 Deprecated.
	 * @author Jonathan
	 * @deprecated
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $value     The new option value.
	 */
	function _imagify_after_save_options( $old_value, $value ) {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Settings::get_instance()->after_save_options( $old_value, $value )' );

		Imagify_Settings::get_instance()->after_save_options( $old_value, $value );
	}

	/**
	 * `options.php` do not handle site options. Let's use `admin-post.php` for multisite installations.
	 *
	 * @since 1.0
	 * @since 1.7 Deprecated.
	 * @deprecated
	 */
	function _imagify_update_site_option_on_network() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Settings::get_instance()->update_site_option_on_network()' );

		Imagify_Settings::get_instance()->update_site_option_on_network();
	}

	/**
	 * Display the plan chooser section.
	 *
	 * @since  1.6
	 * @since  1.7 Deprecated.
	 * @author Geoffrey
	 * @deprecated
	 *
	 * @return string HTML.
	 */
	function get_imagify_new_to_imagify() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'imagify_get_template( \'part-new-to-imagify\' )' );

		return imagify_get_template( 'part-new-to-imagify' );
	}

	/**
	 * Get the payment modal HTML.
	 *
	 * @since  1.6
	 * @since  1.6.3 Include discount banners.
	 * @since  1.7 Deprecated.
	 * @author Geoffrey
	 * @deprecated
	 */
	function imagify_payment_modal() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->print_template( \'modal-payment\' )' );

		Imagify_Views::get_instance()->print_template( 'modal-payment' );
	}

	/**
	 * Print the discount banner used inside Payment Modal.
	 *
	 * @since  1.6.3
	 * @since  1.7 Deprecated.
	 * @author Geoffrey Crofte
	 * @deprecated
	 */
	function imagify_print_discount_banner() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->print_template( \'part-discount-banner\' )' );

		Imagify_Views::get_instance()->print_template( 'part-discount-banner' );
	}

	/**
	 * Return the formatted price present in pricing tables.
	 *
	 * @since  1.6
	 * @since  1.7 Deprecated.
	 * @author Geoffrey
	 * @deprecated
	 *
	 * @param  float $value The price value.
	 * @return string       The markuped price.
	 */
	function get_imagify_price_table_format( $value ) {
		_deprecated_function( __FUNCTION__ . '()', '1.7' );

		$v = explode( '.', (string) $value );

		return '<span class="imagify-price-big">' . $v[0] . '</span> <span class="imagify-price-mini">.' . ( strlen( $v[1] ) === 1 ? $v[1] . '0' : $v[1] ) . '</span>';
	}

	/**
	 * Add submenu in menu "Settings".
	 *
	 * @since 1.0
	 * @since 1.7 Deprecated.
	 * @deprecated
	 */
	function _imagify_settings_menu() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->add_network_menus()' );

		Imagify_Views::get_instance()->add_network_menus();
	}

	/**
	 * Add submenu in menu "Media".
	 *
	 * @since 1.0
	 * @since 1.7 Deprecated.
	 * @deprecated
	 */
	function _imagify_bulk_optimization_menu() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->add_site_menus()' );

		Imagify_Views::get_instance()->add_site_menus();
	}

	/**
	 * The main settings page.
	 *
	 * @since 1.0
	 * @since 1.7 Deprecated.
	 * @deprecated
	 */
	function _imagify_display_options_page() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->display_settings_page()' );

		Imagify_Views::get_instance()->display_settings_page();
	}

	/**
	 * The bulk optimization page.
	 *
	 * @since 1.0
	 * @since 1.7 Deprecated.
	 * @deprecated
	 */
	function _imagify_display_bulk_page() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->display_bulk_page()' );

		Imagify_Views::get_instance()->display_bulk_page();
	}

	/**
	 * Add link to the plugin configuration pages.
	 *
	 * @since 1.0
	 * @since 1.7 Deprecated.
	 *
	 * @param  array $actions An array of action links.
	 * @return array
	 */
	function _imagify_plugin_action_links( $actions ) {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->plugin_action_links( $actions )' );

		return Imagify_Views::get_instance()->plugin_action_links( $actions );
	}

	/**
	 * Get stats data for a specific folder type.
	 *
	 * @since  1.7
	 * @since  1.9 Deprecated
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  string $context A context.
	 * @return array
	 */
	function imagify_get_folder_type_data( $context ) {
		_deprecated_function( __FUNCTION__ . '()', '1.9', 'Imagify_Admin_Ajax_Post::get_instance()->get_bulk_instance( $context )->get_context_data()' );

		/**
		 * Get the data.
		 */
		switch ( $context ) {
			case 'wp':
				$total_saving_data = imagify_count_saving_data();
				$data              = array(
					'images-optimized' => imagify_count_optimized_attachments(),
					'errors'           => imagify_count_error_attachments(),
					'optimized'        => $total_saving_data['optimized_size'],
					'original'         => $total_saving_data['original_size'],
					'errors_url'       => get_imagify_admin_url( 'folder-errors', $context ),
				);
				break;

			case 'custom-folders':
				$data = array(
					'images-optimized' => Imagify_Files_Stats::count_optimized_files(),
					'errors'           => Imagify_Files_Stats::count_error_files(),
					'optimized'        => Imagify_Files_Stats::get_optimized_size(),
					'original'         => Imagify_Files_Stats::get_original_size(),
					'errors_url'       => get_imagify_admin_url( 'folder-errors', $context ),
				);
				break;

			default:
				/**
				 * Provide custom folder type data.
				 *
				 * @since  1.7
				 * @author Grégory Viguier
				 *
				 * @param array  $data    An array with keys corresponding to cell classes, and values formatted with HTML.
				 * @param string $context A context.
				 */
				$data = apply_filters( 'imagify_get_folder_type_data', [], $context );

				if ( ! $data || ! is_array( $data ) ) {
					return [];
				}
		}

		/**
		 * Format the data.
		 */
		/* translators: %s is a formatted number, dont use %d. */
		$data['images-optimized'] = sprintf( _n( '%s Media File Optimized', '%s Media Files Optimized', $data['images-optimized'], 'imagify' ), '<span>' . number_format_i18n( $data['images-optimized'] ) . '</span>' );

		if ( $data['errors'] ) {
			/* translators: %s is a formatted number, dont use %d. */
			$data['errors']  = sprintf( _n( '%s Error', '%s Errors', $data['errors'], 'imagify' ), '<span>' . number_format_i18n( $data['errors'] ) . '</span>' );
			$data['errors'] .= ' <a href="' . esc_url( $data['errors_url'] ) . '">' . __( 'View Errors', 'imagify' ) . '</a>';
		} else {
			$data['errors'] = '';
		}

		if ( $data['optimized'] ) {
			$data['optimized'] = '<span class="imagify-cell-label">' . __( 'Optimized Filesize', 'imagify' ) . '</span> ' . imagify_size_format( $data['optimized'], 2 );
		} else {
			$data['optimized'] = '';
		}

		if ( $data['original'] ) {
			$data['original'] = '<span class="imagify-cell-label">' . __( 'Original Filesize', 'imagify' ) . '</span> ' . imagify_size_format( $data['original'], 2 );
		} else {
			$data['original'] = '';
		}

		unset( $data['errors_url'] );

		return $data;
	}

	/**
	 * Tell if the current user has the required ability to operate Imagify.
	 *
	 * @since  1.6.11
	 * @since  1.9
	 * @see    imagify_get_capacity()
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  string $describer Capacity describer. See imagify_get_capacity() for possible values. Can also be a "real" user capacity.
	 * @param  int    $post_id   A post ID.
	 * @return bool
	 */
	function imagify_current_user_can( $describer = 'manage', $post_id = null ) {
		static $can_upload;

		_deprecated_function( __FUNCTION__ . '()', '1.9', 'imagify_get_context( $context )->current_user_can( $describer, $media_id )' );

		$post_id  = $post_id ? $post_id : null;
		$capacity = imagify_get_capacity( $describer );
		$user_can = false;

		if ( 'manage' !== $describer && 'bulk-optimize' !== $describer && 'optimize-file' !== $describer ) {
			// Describers that are not 'manage', 'bulk-optimize', and 'optimize-file' need an additional test for 'upload_files'.
			if ( ! isset( $can_upload ) ) {
				$can_upload = current_user_can( 'upload_files' );
			}

			if ( $can_upload ) {
				if ( 'upload_files' === $capacity ) {
					// We already know it's true.
					$user_can = true;
				} else {
					$user_can = current_user_can( $capacity, $post_id );
				}
			}
		} else {
			$user_can = current_user_can( $capacity );
		}

		/**
		 * Filter the current user ability to operate Imagify.
		 *
		 * @since 1.6.11
		 *
		 * @param bool   $user_can  Tell if the current user has the required ability to operate Imagify.
		 * @param string $capacity  The user capacity.
		 * @param string $describer Capacity describer. See imagify_get_capacity() for possible values. Can also be a "real" user capacity.
		 * @param int    $post_id   A post ID (a gallery ID for NGG).
		 */
		return apply_filters( 'imagify_current_user_can', $user_can, $capacity, $describer, $post_id );
	}

	/**
	 * Get user capacity to operate Imagify.
	 *
	 * @since  1.6.5
	 * @since  1.6.11 Uses a string as describer for the first argument.
	 * @since  1.9    Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  string $describer Capacity describer. Possible values are 'manage', 'bulk-optimize', 'manual-optimize', 'auto-optimize', and 'optimize-file'.
	 * @return string
	 */
	function imagify_get_capacity( $describer = 'manage' ) {
		static $edit_attachment_cap;

		_deprecated_function( __FUNCTION__ . '()', '1.9', 'imagify_get_context( $context )->get_capacity( $describer )' );

		// Back compat.
		if ( ! is_string( $describer ) ) {
			if ( $describer || ! is_multisite() ) {
				$describer = 'bulk-optimize';
			} else {
				$describer = 'manage';
			}
		}

		switch ( $describer ) {
			case 'manage':
				$capacity = imagify_is_active_for_network() ? 'manage_network_options' : 'manage_options';
				break;

			case 'optimize-file':
				$capacity = is_multisite() ? 'manage_network_options' : 'manage_options';
				break;

			case 'bulk-optimize':
				$capacity = 'manage_options';
				break;

			case 'optimize':
			case 'restore':
				// This is a generic capacity: don't use it unless you have no other choices!
				if ( ! isset( $edit_attachment_cap ) ) {
					$edit_attachment_cap = get_post_type_object( 'attachment' );
					$edit_attachment_cap = $edit_attachment_cap ? $edit_attachment_cap->cap->edit_posts : 'edit_posts';
				}

				$capacity = $edit_attachment_cap;
				break;

			case 'manual-optimize':
			case 'manual-restore':
				// Must be used with an Attachment ID.
				$capacity = 'edit_post';
				break;

			case 'auto-optimize':
				$capacity = 'upload_files';
				break;

			default:
				$capacity = $describer;
		}

		/**
		 * Filter the user capacity used to operate Imagify.
		 *
		 * @since 1.0
		 * @since 1.6.5  Added $force_mono parameter.
		 * @since 1.6.11 Replaced $force_mono by $describer.
		 *
		 * @param string $capacity  The user capacity.
		 * @param string $describer Capacity describer. Possible values are 'manage', 'bulk-optimize', 'manual-optimize', 'auto-optimize', and 'optimize-file'.
		 */
		return apply_filters( 'imagify_capacity', $capacity, $describer );
	}

	/**
	 * Check for user capacity.
	 *
	 * @since  1.6.10
	 * @since  1.6.11 Uses a capacity describer instead of a capacity itself.
	 * @since  1.9    Deprecated.
	 * @see    imagify_get_capacity()
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param string $describer Capacity describer. See imagify_get_capacity() for possible values. Can also be a "real" user capacity.
	 * @param int    $post_id   A post ID.
	 */
	function imagify_check_user_capacity( $describer = 'manage', $post_id = null ) {
		_deprecated_function( __FUNCTION__ . '()', '1.9' );

		if ( ! imagify_current_user_can( $describer, $post_id ) ) {
			imagify_die();
		}
	}

	/**
	 * Update the Heartbeat API settings.
	 *
	 * @since 1.4.5
	 * @since 1.9.3 Deprecated.
	 * @deprecated
	 *
	 * @param  array $settings Heartbeat API settings.
	 * @return array
	 */
	function _imagify_heartbeat_settings( $settings ) {
		_deprecated_function( __FUNCTION__ . '()', '1.9.3' );

		$settings['interval'] = 30;
		return $settings;
	}

	/**
	 * Prepare the data that goes back with the Imagifybeat API.
	 *
	 * @since 1.4.5
	 * @since 1.9.3 Deprecated.
	 * @deprecated
	 *
	 * @param  array $response The Imagifybeat response.
	 * @param  array $data     The $_POST data sent.
	 * @return array
	 */
	function _imagify_heartbeat_received( $response, $data ) {
		_deprecated_function( __FUNCTION__ . '()', '1.9.3', '\\Imagify\\Imagifybeat\\Actions::get_instance()->add_bulk_optimization_stats_to_response()' );

		$heartbeat_id = 'imagify_bulk_data';

		if ( empty( $data[ $heartbeat_id ] ) ) {
			return $response;
		}

		$folder_types = array_flip( array_filter( $data[ $heartbeat_id ] ) );

		$response[ $heartbeat_id ] = imagify_get_bulk_stats( $folder_types, array(
			'fullset' => true,
		) );

		return $response;
	}

	/**
	 * Prepare the data that goes back with the Imagifybeat API.
	 *
	 * @since  1.7.1
	 * @since  1.9.3 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  array $response The Imagifybeat response.
	 * @param  array $data     The $_POST data sent.
	 * @return array
	 */
	function imagify_heartbeat_requirements_received( $response, $data ) {
		_deprecated_function( __FUNCTION__ . '()', '1.9.3', '\\Imagify\\Imagifybeat\\Actions::get_instance()->add_requirements_to_response()' );

		$heartbeat_id = 'imagify_bulk_requirements';

		if ( empty( $data[ $heartbeat_id ] ) ) {
			return $response;
		}

		$response[ $heartbeat_id ] = array(
			'curl_missing'          => ! Imagify_Requirements::supports_curl(),
			'editor_missing'        => ! Imagify_Requirements::supports_image_editor(),
			'external_http_blocked' => Imagify_Requirements::is_imagify_blocked(),
			'api_down'              => Imagify_Requirements::is_imagify_blocked() || ! Imagify_Requirements::is_api_up(),
			'key_is_valid'          => ! Imagify_Requirements::is_imagify_blocked() && Imagify_Requirements::is_api_up() && Imagify_Requirements::is_api_key_valid(),
			'is_over_quota'         => ! Imagify_Requirements::is_imagify_blocked() && Imagify_Requirements::is_api_up() && Imagify_Requirements::is_api_key_valid() && Imagify_Requirements::is_over_quota(),
		);

		return $response;
	}

	/**
	 * Look for media where status has changed, compared to what Imagifybeat sends.
	 * This is used in the bulk optimization page.
	 *
	 * @since  1.9
	 * @since  1.9.3 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  array $response The Imagifybeat response.
	 * @param  array $data     The $_POST data sent.
	 * @return array
	 */
	function imagify_heartbeat_bulk_optimization_status_received( $response, $data ) {
		_deprecated_function( __FUNCTION__ . '()', '1.9.3', '\\Imagify\\Imagifybeat\\Actions::get_instance()->add_bulk_optimization_status_to_response()' );

		$heartbeat_id = 'imagify_bulk_queue';

		if ( empty( $data[ $heartbeat_id ] ) || ! is_array( $data[ $heartbeat_id ] ) ) {
			return $response;
		}

		$statuses = [];

		foreach ( $data[ $heartbeat_id ] as $item ) {
			if ( empty( $statuses[ $item['context'] ] ) ) {
				$statuses[ $item['context'] ] = [];
			}

			$statuses[ $item['context'] ][ '_' . $item['mediaID'] ] = 1;
		}

		$results = imagify_get_modified_optimization_statusses( $statuses );

		if ( ! $results ) {
			return $response;
		}

		$response[ $heartbeat_id ] = [];

		// Sanitize received data and grab some other info.
		foreach ( $results as $context_id => $media_atts ) {
			$process    = imagify_get_optimization_process( $media_atts['media_id'], $media_atts['context'] );
			$optim_data = $process->get_data();

			if ( $optim_data->is_optimized() ) {
				// Successfully optimized.
				$full_size_data              = $optim_data->get_size_data();
				$response[ $heartbeat_id ][] = [
					'mediaID'                  => $media_atts['media_id'],
					'context'                  => $media_atts['context'],
					'success'                  => true,
					'status'                   => 'optimized',
					// Raw data.
					'originalOverallSize'      => $full_size_data['original_size'],
					'newOverallSize'           => $full_size_data['optimized_size'],
					'overallSaving'            => $full_size_data['original_size'] - $full_size_data['optimized_size'],
					'thumbnailsCount'          => $optim_data->get_optimized_sizes_count(),
					// Human readable data.
					'originalSizeHuman'        => imagify_size_format( $full_size_data['original_size'], 2 ),
					'newSizeHuman'             => imagify_size_format( $full_size_data['optimized_size'], 2 ),
					'overallSavingHuman'       => imagify_size_format( $full_size_data['original_size'] - $full_size_data['optimized_size'], 2 ),
					'originalOverallSizeHuman' => imagify_size_format( $full_size_data['original_size'], 2 ),
					'percentHuman'             => $full_size_data['percent'] . '%',
				];
			} elseif ( $optim_data->is_already_optimized() ) {
				// Already optimized.
				$response[ $heartbeat_id ][] = [
					'mediaID' => $media_atts['media_id'],
					'context' => $media_atts['context'],
					'success' => true,
					'status'  => 'already-optimized',
				];
			} else {
				// Error.
				$full_size_data = $optim_data->get_size_data();
				$message        = ! empty( $full_size_data['error'] ) ? $full_size_data['error'] : '';
				$status         = 'error';

				if ( 'You\'ve consumed all your data. You have to upgrade your account to continue' === $message ) {
					$status = 'over-quota';
				}

				$response[ $heartbeat_id ][] = [
					'mediaID' => $media_atts['media_id'],
					'context' => $media_atts['context'],
					'success' => false,
					'status'  => $status,
					'error'   => imagify_translate_api_message( $message ),
				];
			}
		}

		return $response;
	}

	/**
	 * Look for media where status has changed, compared to what Imagifybeat sends.
	 * This is used in the settings page.
	 *
	 * @since  1.9
	 * @since  1.9.3 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  array $response The Imagifybeat response.
	 * @param  array $data     The $_POST data sent.
	 * @return array
	 */
	function imagify_heartbeat_options_bulk_optimization_status_received( $response, $data ) {
		_deprecated_function( __FUNCTION__ . '()', '1.9.3', '\\Imagify\\Imagifybeat\\Actions::get_instance()->add_options_optimization_status_to_response()' );

		$heartbeat_id = 'imagify_options_bulk_queue';

		if ( empty( $data[ $heartbeat_id ] ) || ! is_array( $data[ $heartbeat_id ] ) ) {
			return $response;
		}

		$statuses = [];

		foreach ( $data[ $heartbeat_id ] as $item ) {
			if ( empty( $statuses[ $item['context'] ] ) ) {
				$statuses[ $item['context'] ] = [];
			}

			$statuses[ $item['context'] ][ '_' . $item['mediaID'] ] = 1;
		}

		$results = imagify_get_modified_optimization_statusses( $statuses );

		if ( ! $results ) {
			return $response;
		}

		$response[ $heartbeat_id ] = [];

		foreach ( $results as $result ) {
			$response[ $heartbeat_id ][] = [
				'mediaID' => $result['media_id'],
				'context' => $result['context'],
			];
		}

		return $response;
	}

	/**
	 * Look for media where status has changed, compared to what Imagifybeat sends.
	 * This is used in the WP Media Library.
	 *
	 * @since  1.9
	 * @since  1.9.3 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  array $response The Imagifybeat response.
	 * @param  array $data     The $_POST data sent.
	 * @return array
	 */
	function imagify_heartbeat_optimization_status_received( $response, $data ) {
		_deprecated_function( __FUNCTION__ . '()', '1.9.3', '\\Imagify\\Imagifybeat\\Actions::get_instance()->add_library_optimization_status_to_response()' );

		$heartbeat_id = get_imagify_localize_script_translations( 'media-modal' );
		$heartbeat_id = $heartbeat_id['heartbeatId'];

		if ( empty( $data[ $heartbeat_id ] ) || ! is_array( $data[ $heartbeat_id ] ) ) {
			return $response;
		}

		$response[ $heartbeat_id ] = imagify_get_modified_optimization_statusses( $data[ $heartbeat_id ] );

		if ( ! $response[ $heartbeat_id ] ) {
			return $response;
		}

		// Sanitize received data and grab some other info.
		foreach ( $response[ $heartbeat_id ] as $context_id => $media_atts ) {
			$process = imagify_get_optimization_process( $media_atts['media_id'], $media_atts['context'] );

			$response[ $heartbeat_id ][ $context_id ] = get_imagify_media_column_content( $process, false );
		}

		return $response;
	}

	/**
	 * Look for media where status has changed, compared to what Imagifybeat sends.
	 * This is used in the custom folders list (the "Other Media" page).
	 *
	 * @since  1.9
	 * @since  1.9.3 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  array $response The Imagifybeat response.
	 * @param  array $data     The $_POST data sent.
	 * @return array
	 */
	function imagify_heartbeat_custom_folders_optimization_status_received( $response, $data ) {
		_deprecated_function( __FUNCTION__ . '()', '1.9.3', '\\Imagify\\Imagifybeat\\Actions::get_instance()->add_custom_folders_optimization_status_to_response()' );

		$heartbeat_id = get_imagify_localize_script_translations( 'files-list' );
		$heartbeat_id = $heartbeat_id['heartbeatId'];

		if ( empty( $data[ $heartbeat_id ] ) || ! is_array( $data[ $heartbeat_id ] ) ) {
			return $response;
		}

		$response[ $heartbeat_id ] = imagify_get_modified_optimization_statusses( $data[ $heartbeat_id ] );

		if ( ! $response[ $heartbeat_id ] ) {
			return $response;
		}

		$admin_ajax_post = Imagify_Admin_Ajax_Post::get_instance();
		$list_table      = new Imagify_Files_List_Table( [
			'screen' => 'imagify-files',
		] );

		// Sanitize received data and grab some other info.
		foreach ( $response[ $heartbeat_id ] as $context_id => $media_atts ) {
			$process = imagify_get_optimization_process( $media_atts['media_id'], $media_atts['context'] );

			$response[ $heartbeat_id ][ $context_id ] = $admin_ajax_post->get_media_columns( $process, $list_table );
		}

		return $response;
	}

	/**
	 * Look for media where status has changed, compared to what Imagifybeat sends.
	 *
	 * @since  1.9
	 * @since  1.9.3 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  array $data The data received.
	 * @return array
	 */
	function imagify_get_modified_optimization_statusses( $data ) {
		_deprecated_function( __FUNCTION__ . '()', '1.9.3', '\\Imagify\\Imagifybeat\\Actions::get_instance()->get_modified_optimization_statuses()' );

		if ( ! $data ) {
			return [];
		}

		$output = [];

		// Sanitize received data and grab some other info.
		foreach ( $data as $context => $media_statuses ) {
			if ( ! $context || ! $media_statuses || ! is_array( $media_statuses ) ) {
				continue;
			}

			// Sanitize the IDs: IDs come as strings, prefixed with an undescore character (to prevent JavaScript from screwing everything).
			$media_ids = array_keys( $media_statuses );
			$media_ids = array_map( function( $media_id ) {
				return (int) substr( $media_id, 1 );
			}, $media_ids );
			$media_ids = array_filter( $media_ids );

			if ( ! $media_ids ) {
				continue;
			}

			// Sanitize the context.
			$context_instance   = imagify_get_context( $context );
			$context            = $context_instance->get_name();
			$process_class_name = imagify_get_optimization_process_class_name( $context );
			$transient_name     = sprintf( $process_class_name::LOCK_NAME, $context, '%' );
			$is_network_wide    = $context_instance->is_network_wide();

			Imagify_DB::cache_process_locks( $context, $media_ids );

			// Now that everything is cached for this context, we can get the transients without hitting the DB.
			foreach ( $media_ids as $id ) {
				$is_locked   = (bool) $media_statuses[ '_' . $id ];
				$option_name = str_replace( '%', $id, $transient_name );

				if ( $is_network_wide ) {
					$in_db = (bool) get_site_transient( $option_name );
				} else {
					$in_db = (bool) get_transient( $option_name );
				}

				if ( $is_locked === $in_db ) {
					continue;
				}

				$output[ $context . '_' . $id ] = [
					'media_id' => $id,
					'context'  => $context,
				];
			}
		}

		return $output;
	}

endif;
