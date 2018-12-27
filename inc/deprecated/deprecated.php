<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Include Admin Bar Profile informations styles in front.
 *
 * @since 1.2
 * @since 1.6.10 Deprecated.
 * @deprecated
 */
function _imagify_admin_bar_styles() {
	_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Assets::get_instance()->enqueue_styles_and_scripts_frontend()' );

	if ( ! is_admin() ) {
		Imagify_Assets::get_instance()->enqueue_styles_and_scripts_frontend();
	}
}

/**
 * Make an absolute path relative to WordPress' root folder.
 * Also works for files from registered symlinked plugins.
 *
 * @since  1.6.8
 * @since  1.6.10 Deprecated. Don't laugh.
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  string $file_path An absolute path.
 * @return string            A relative path. Can return the absolute path in case of a failure.
 */
function imagify_make_file_path_replative( $file_path ) {
	_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'imagify_get_filesystem()->make_path_relative( $file_path )' );

	return imagify_get_filesystem()->make_path_relative( $file_path );
}

/**
 * Combine two arrays with some specific keys.
 * We use this function to combine the result of 2 SQL queries.
 *
 * @since 1.4.5
 * @since 1.6.7  Added the $keep_keys_order parameter.
 * @since 1.6.13 Deprecated.
 * @deprecated
 *
 * @param  array $keys            An array of keys.
 * @param  array $values          An array of arrays like array( 'id' => id, 'value' => value ).
 * @param  int   $keep_keys_order Set to true to return an array ordered like $keys instead of $values.
 * @return array                  The combined arrays.
 */
function imagify_query_results_combine( $keys, $values, $keep_keys_order = false ) {
	_deprecated_function( __FUNCTION__ . '()', '1.6.13', 'Imagify_DB::combine_query_results( $keys, $values, $keep_keys_order )' );

	return Imagify_DB::combine_query_results( $keys, $values, $keep_keys_order );
}

/**
 * A helper to retrieve all values from one or several post metas, given a list of post IDs.
 * The $wpdb cache is flushed to save memory.
 *
 * @since  1.6.7
 * @since  1.6.13 Deprecated.
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  array $metas An array of meta names like:
 *                      array(
 *                          'key1' => 'meta_name_1',
 *                          'key2' => 'meta_name_2',
 *                          'key3' => 'meta_name_3',
 *                      )
 *                      If a key contains 'data', the results will be unserialized.
 * @param  array $ids   An array of post IDs.
 * @return array        An array of arrays of results like:
 *                      array(
 *                          'key1' => array( post_id_1 => 'result_1', post_id_2 => 'result_2', post_id_3 => 'result_3' ),
 *                          'key2' => array( post_id_1 => 'result_4', post_id_3 => 'result_5' ),
 *                          'key3' => array( post_id_1 => 'result_6', post_id_2 => 'result_7' ),
 *                      )
 */
function imagify_get_wpdb_metas( $metas, $ids ) {
	_deprecated_function( __FUNCTION__ . '()', '1.6.13', 'Imagify_DB::get_metas( $metas, $ids )' );

	return Imagify_DB::get_metas( $metas, $ids );
}

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
		include IMAGIFY_CLASSES_PATH . 'class-' . $class . '.php';
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
		include IMAGIFY_3RD_PARTY_PATH . $folder . '/inc/classes/class-' . $class . '.php';
	}
}

if ( is_admin() ) :

	/**
	 * Add some CSS on the whole administration.
	 *
	 * @since 1.0
	 * @since 1.6.10 Deprecated.
	 * @deprecated
	 */
	function _imagify_admin_print_styles() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Assets::get_instance()->enqueue_styles_and_scripts()' );

		Imagify_Assets::get_instance()->enqueue_styles_and_scripts();
	}

	/**
	 * Add Intercom on Options page an Bulk Optimization.
	 *
	 * @since 1.0
	 * @since 1.6.10 Deprecated.
	 * @deprecated
	 */
	function _imagify_admin_print_intercom() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Assets::get_instance()->print_support_script()' );

		Imagify_Assets::get_instance()->print_support_script();
	}

	/**
	 * Add Intercom on Options page an Bulk Optimization
	 *
	 * @since  1.5
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_ngg_admin_print_intercom() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Assets::get_instance()->print_support_script()' );

		$current_screen = get_current_screen();

		if ( isset( $current_screen ) && false !== strpos( $current_screen->base, '_page_imagify-ngg-bulk-optimization' ) ) {
			Imagify_Assets::get_instance()->print_support_script();
		}
	}

	/**
	 * A helper to deprecate old admin notice functions.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 * @see    Imagify_Notices::notices()
	 * @deprecated
	 *
	 * @param string $function  The function to deprecate.
	 * @param string $notice_id The notice to deprecate.
	 */
	function _imagify_deprecate_old_notice( $function, $notice_id ) {
		_deprecated_function( $function . '()', '1.6.10' );

		$notices  = Imagify_Notices::get_instance();
		$callback = 'display_' . str_replace( '-', '_', $notice_id );
		$data     = method_exists( $notices, $callback ) ? call_user_func( array( $notices, $callback ) ) : false;

		if ( $data ) {
			Imagify_Views::get_instance()->print_template( 'notice-' . $notice_id, $data );
		}
	}

	/**
	 * This warning is displayed when the API key is empty.
	 *
	 * @since  1.0
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_warning_empty_api_key_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'welcome-steps' );
	}

	/**
	 * This warning is displayed when the API key is empty.
	 *
	 * @since  1.0
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_warning_wrong_api_key_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'wrong-api-key' );
	}

	/**
	 * This warning is displayed when some plugins may conflict with Imagify.
	 *
	 * @since  1.0
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_warning_plugins_to_deactivate_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'plugins-to-deactivate' );
	}

	/**
	 * This notice is displayed when external HTTP requests are blocked via the WP_HTTP_BLOCK_EXTERNAL constant.
	 *
	 * @since  1.0
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_http_block_external_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'http-block-external' );
	}

	/**
	 * This warning is displayed when the grid view is active on the library.
	 *
	 * @since  1.0.2
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_warning_grid_view_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'grid-view' );
	}

	/**
	 * This warning is displayed to warn the user that its quota is consumed for the current month.
	 *
	 * @since  1.1.1
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_warning_over_quota_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'over-quota' );
	}

	/**
	 * This warning is displayed if the backup folder is not writable.
	 *
	 * @since  1.6.8
	 * @since  1.6.10 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 */
	function _imagify_warning_backup_folder_not_writable_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'backup-folder-not-writable' );
	}

	/**
	 * Add a message about WP Rocket on the "Bulk Optimization" screen.
	 *
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_rocket_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'rocket' );
	}

	/**
	 * This notice is displayed to rate the plugin after 100 optimization & 7 days after the first installation.
	 *
	 * @since  1.4.2
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_rating_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'rating' );
	}

	/**
	 * Stop the rating cron when the notice is dismissed.
	 *
	 * @since 1.6.10 Deprecated.
	 * @deprecated
	 *
	 * @param string $notice The notice name.
	 */
	function _imagify_clear_scheduled_rating( $notice ) {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Notices::get_instance()->clear_scheduled_rating( $notice )' );

		Imagify_Notices::get_instance()->clear_scheduled_rating( $notice );
	}

	/**
	 * Process a dismissed notice.
	 *
	 * @since  1.0
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _do_admin_post_imagify_dismiss_notice() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Notices::get_instance()->admin_post_dismiss_notice()' );

		Imagify_Notices::get_instance()->admin_post_dismiss_notice();
	}

	/**
	 * Disable a plugin which can be in conflict with Imagify
	 *
	 * @since  1.2
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_deactivate_plugin() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Notices::get_instance()->deactivate_plugin()' );

		Imagify_Notices::get_instance()->deactivate_plugin();
	}

	/**
	 * Renew a dismissed Imagify notice.
	 *
	 * @since 1.0
	 * @since 1.6.10 Deprecated.
	 * @deprecated
	 *
	 * @param  string $notice  A notice ID.
	 * @param  int    $user_id A user ID.
	 * @return void
	 */
	function imagify_renew_notice( $notice, $user_id = 0 ) {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Notices::renew_notice( $notice, $user_id )' );

		Imagify_Notices::renew_notice( $notice, $user_id );
	}

	/**
	 * Dismiss an Imagify notice.
	 *
	 * @since 1.0
	 * @since 1.6.10 Deprecated.
	 * @deprecated
	 *
	 * @param  string $notice  A notice ID.
	 * @param  int    $user_id A user ID.
	 * @return void
	 */
	function imagify_dismiss_notice( $notice, $user_id = 0 ) {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Notices::dismiss_notice( $notice, $user_id )' );

		Imagify_Notices::dismiss_notice( $notice, $user_id );
	}

	/**
	 * Tell if an Imagify notice is dismissed.
	 *
	 * @since  1.6.5
	 * @since  1.6.10 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  string $notice  A notice ID.
	 * @param  int    $user_id A user ID.
	 * @return bool
	 */
	function imagify_notice_is_dismissed( $notice, $user_id = 0 ) {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Notices::notice_is_dismissed( $notice, $user_id )' );

		return Imagify_Notices::notice_is_dismissed( $notice, $user_id );
	}

	/**
	 * Process all thumbnails of a specific image with Imagify with the manual method.
	 *
	 * @since  1.0
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_manual_upload_callback()
	 * @deprecated
	 */
	function _do_admin_post_imagify_manual_upload() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_manual_upload_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_manual_upload_callback();
	}

	/**
	 * Process all thumbnails of a specific image with Imagify with a different optimization level.
	 *
	 * @since  1.0
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_manual_override_upload_callback()
	 * @deprecated
	 */
	function _do_admin_post_imagify_manual_override_upload() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_manual_override_upload_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_manual_override_upload_callback();
	}

	/**
	 * Process one or some thumbnails that are not optimized yet.
	 *
	 * @since  1.6.10
	 * @since  1.6.11 Deprecated.
	 * @author Grégory Viguier
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_optimize_missing_sizes_callback()
	 * @deprecated
	 */
	function _do_admin_post_imagify_optimize_missing_sizes() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_optimize_missing_sizes_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_optimize_missing_sizes_callback();
	}

	/**
	 * Process a restoration to the original attachment.
	 *
	 * @since  1.0
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_restore_upload_callback()
	 * @deprecated
	 */
	function _do_admin_post_imagify_restore_upload() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_restore_upload_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_restore_upload_callback();
	}

	/**
	 * Process all thumbnails of a specific image with Imagify with the bulk method.
	 *
	 * @since  1.0
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_bulk_upload_callback()
	 * @deprecated
	 */
	function _do_wp_ajax_imagify_bulk_upload() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_bulk_upload_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_bulk_upload_callback();
	}

	/**
	 * Optimize image on picture uploading with async request.
	 *
	 * @since  1.5
	 * @since  1.6.11 Deprecated.
	 * @author Julio Potier
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_async_optimize_upload_new_media_callback()
	 * @deprecated
	 */
	function _do_admin_post_async_optimize_upload_new_media() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_async_optimize_upload_new_media_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_async_optimize_upload_new_media_callback();
	}

	/**
	 * Optimize image on picture editing (resize, crop...) with async request.
	 *
	 * @since  1.4
	 * @since  1.6.11 Deprecated.
	 * @author Julio Potier
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_async_optimize_save_image_editor_file_callback()
	 * @deprecated
	 */
	function _do_admin_post_async_optimize_save_image_editor_file() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_async_optimize_save_image_editor_file_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_async_optimize_save_image_editor_file_callback();
	}

	/**
	 * Get all unoptimized attachment ids.
	 *
	 * @since  1.0
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_get_unoptimized_attachment_ids_callback()
	 * @deprecated
	 */
	function _do_wp_ajax_imagify_get_unoptimized_attachment_ids() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_get_unoptimized_attachment_ids_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_get_unoptimized_attachment_ids_callback();
	}

	/**
	 * Check if the backup directory is writable.
	 * This is used to display an error message in the plugin's settings page.
	 *
	 * @since  1.6.8
	 * @since  1.6.11 Deprecated.
	 * @author Grégory Viguier
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_check_backup_dir_is_writable_callback()
	 * @deprecated
	 */
	function _do_wp_ajax_imagify_check_backup_dir_is_writable() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_check_backup_dir_is_writable_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_check_backup_dir_is_writable_callback();
	}

	/**
	 * Create a new Imagify account.
	 *
	 * @since  1.0
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_signup_callback()
	 * @deprecated
	 */
	function _do_wp_ajax_imagify_signup() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_signup_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_signup_callback();
	}

	/**
	 * Process an API key check validity.
	 *
	 * @since  1.0
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_check_api_key_validity_callback()
	 * @deprecated
	 */
	function _do_wp_ajax_imagify_check_api_key_validity() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_check_api_key_validity_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_check_api_key_validity_callback();
	}

	/**
	 * Get admin bar profile output.
	 *
	 * @since  1.2.3
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_get_admin_bar_profile_callback()
	 * @deprecated
	 */
	function _do_wp_ajax_imagify_get_admin_bar_profile() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_get_admin_bar_profile_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_get_admin_bar_profile_callback();
	}

	/**
	 * Get pricings from API for Onetime and Plans at the same time.
	 *
	 * @since  1.6
	 * @since  1.6.11 Deprecated.
	 * @author Geoffrey Crofte
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_get_prices_callback()
	 * @deprecated
	 */
	function _imagify_get_prices_from_api() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_get_prices_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_get_prices_callback();
	}

	/**
	 * Check Coupon code on modal popin.
	 *
	 * @since  1.6
	 * @since  1.6.11 Deprecated.
	 * @author Geoffrey Crofte
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_check_coupon_callback()
	 * @deprecated
	 */
	function _imagify_check_coupon_code() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_check_coupon_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_check_coupon_callback();
	}

	/**
	 * Get current discount promotion to display information on payment modal.
	 *
	 * @since  1.6.3
	 * @since  1.6.11 Deprecated.
	 * @author Geoffrey Crofte
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_get_discount_callback()
	 * @deprecated
	 */
	function _imagify_get_discount() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_get_discount_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_get_discount_callback();
	}

	/**
	 * Get estimated sizes from the WordPress library.
	 *
	 * @since  1.6
	 * @since  1.6.11 Deprecated.
	 * @author Geoffrey Crofte
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_get_images_counts_callback()
	 * @deprecated
	 */
	function _imagify_get_estimated_sizes() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_get_images_counts_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_get_images_counts_callback();
	}

	/**
	 * Estimate sizes and update the options values for them.
	 *
	 * @since  1.6
	 * @since  1.6.11 Deprecated.
	 * @author Remy Perona
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_update_estimate_sizes_callback()
	 * @deprecated
	 */
	function _imagify_update_estimate_sizes() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_update_estimate_sizes_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_update_estimate_sizes_callback();
	}

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

endif;
