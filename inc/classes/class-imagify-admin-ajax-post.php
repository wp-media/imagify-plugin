<?php

use Imagify\Traits\InstanceGetterTrait;

/**
 * Class that handles admin ajax/post callbacks.
 *
 * @since  1.6.11
 */
class Imagify_Admin_Ajax_Post extends Imagify_Admin_Ajax_Post_Deprecated {
	use InstanceGetterTrait;

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.1';

	/**
	 * Actions to be triggered on admin ajax and admin post.
	 *
	 * @var array
	 */
	protected $ajax_post_actions = [
		// WP optimization.
		'imagify_manual_optimize',
		'imagify_manual_reoptimize',
		'imagify_optimize_missing_sizes',
		'imagify_generate_webp_versions',
		'imagify_delete_webp_versions',
		'imagify_restore',
		// Custom folders optimization.
		'imagify_optimize_file',
		'imagify_reoptimize_file',
		'imagify_restore_file',
		'imagify_refresh_file_modified',
	];

	/**
	 * Actions to be triggered only on admin ajax.
	 *
	 * @var array
	 */
	protected $ajax_only_actions = [
		// Settings page.
		'imagify_check_backup_dir_is_writable',
		'imagify_get_files_tree',
		// Account.
		'imagify_signup',
		'imagify_check_api_key_validity',
		'imagify_get_admin_bar_profile',
		'imagify_get_prices',
		'imagify_check_coupon',
		'imagify_get_discount',
		'imagify_get_images_counts',
		'imagify_update_estimate_sizes',
		'imagify_get_user_data',
		'imagify_delete_user_data_cache',
		// Various.
		'nopriv_imagify_rpc',
	];

	/**
	 * Actions to be triggered only on admin post.
	 *
	 * @var array
	 */
	protected $post_only_actions = [
		// Custom folders optimization.
		'imagify_scan_custom_folders',
		// Various.
		'imagify_dismiss_ad',
	];

	/**
	 * Filesystem object.
	 *
	 * @var Imagify_Filesystem
	 */
	protected $filesystem;


	/** ----------------------------------------------------------------------------------------- */
	/** INIT ==================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->filesystem = Imagify_Filesystem::get_instance();
	}

	/**
	 * Launch the hooks.
	 *
	 * @since 1.6.11
	 */
	public function init() {
		$doing_ajax = wp_doing_ajax();

		foreach ( $this->ajax_post_actions as $action ) {
			$action_callback = "{$action}_callback";
			if ( $doing_ajax ) {
				add_action( 'wp_ajax_' . $action, array( $this, $action_callback ) );
			}
			add_action( 'admin_post_' . $action, array( $this, $action_callback ) );
		}

		// Actions triggered only on admin ajax.
		if ( $doing_ajax ) {
			foreach ( $this->ajax_only_actions as $action ) {
				add_action( 'wp_ajax_' . $action, array( $this, $action . '_callback' ) );
			}
		}

		// Actions triggered on admin post.
		foreach ( $this->post_only_actions as $action ) {
			add_action( 'admin_post_' . $action, array( $this, $action . '_callback' ) );
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** OPTIMIZATION PROCESSES ================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Optimize one media.
	 *
	 * @since 1.9
	 *
	 * @param  int    $media_id The media ID.
	 * @param  string $context  The context.
	 * @return bool|WP_Error    True if successfully launched. A \WP_Error instance on failure.
	 */
	protected function optimize_media( $media_id, $context ) {
		return imagify_get_optimization_process( $media_id, $context )->optimize();
	}

	/**
	 * Re-optimize a media to a different optimization level.
	 *
	 * @since 1.9
	 *
	 * @param  int    $media_id The media ID.
	 * @param  string $context  The context.
	 * @param  int    $level    The optimization level.
	 * @return bool|WP_Error    True if successfully launched. A \WP_Error instance on failure.
	 */
	protected function reoptimize_media( $media_id, $context, $level ) {
		return imagify_get_optimization_process( $media_id, $context )->reoptimize( $level );
	}

	/**
	 * Optimize all files from a media, whatever this media’s previous optimization status (will be restored if needed).
	 * This is used by the bulk optimization page.
	 *
	 * @since 1.9
	 *
	 * @param  int    $media_id The media ID.
	 * @param  string $context  The context.
	 * @param  int    $level    The optimization level.
	 * @return bool|WP_Error    True if successfully launched. A \WP_Error instance on failure.
	 */
	protected function force_optimize( $media_id, $context, $level ) {
		$process = imagify_get_optimization_process( $media_id, $context );
		$data    = $process->get_data();

		// Restore before re-optimizing.
		if ( $data->is_optimized() ) {
			$result = $process->restore();

			if ( is_wp_error( $result ) ) {
				// Return an error message.
				return $result;
			}
		}

		return $process->optimize( $level );
	}

	/**
	 * Optimize one or some thumbnails that are not optimized yet.
	 *
	 * @since 1.9
	 *
	 * @param  int    $media_id The media ID.
	 * @param  string $context  The context.
	 * @return bool|WP_Error    True if successfully launched. A \WP_Error instance on failure.
	 */
	protected function optimize_missing_sizes( $media_id, $context ) {
		return imagify_get_optimization_process( $media_id, $context )->optimize_missing_thumbnails();
	}

	/**
	 * Generate WebP images if they are missing.
	 *
	 * @since 1.9
	 *
	 * @param  int    $media_id The media ID.
	 * @param  string $context  The context.
	 * @return bool|WP_Error    True if successfully launched. A \WP_Error instance on failure.
	 */
	protected function generate_webp_versions( $media_id, $context ) {
		return imagify_get_optimization_process( $media_id, $context )->generate_webp_versions();
	}

	/**
	 * Delete WebP images for media that are "already_optimize".
	 *
	 * @since 1.9.6
	 *
	 * @param  int    $media_id The media ID.
	 * @param  string $context  The context.
	 * @return bool|WP_Error    True if successfully launched. A \WP_Error instance on failure.
	 */
	protected function delete_webp_versions( $media_id, $context ) {
		$process = imagify_get_optimization_process( $media_id, $context );

		if ( ! $process->is_valid() ) {
			return new \WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$data = $process->get_data();

		if ( ! $data->is_already_optimized() ) {
			return new \WP_Error( 'not_already_optimized', __( 'This media does not have the right optimization status.', 'imagify' ) );
		}

		if ( ! $process->has_webp() ) {
			return true;
		}

		$data->delete_optimization_data();
		$deleted = $process->delete_webp_files();

		if ( is_wp_error( $deleted ) ) {
			return new \WP_Error( 'webp_not_deleted', __( 'Previous WebP files could not be deleted.', 'imagify' ) );
		}

		return true;
	}

	/**
	 * Restore a media.
	 *
	 * @since 1.9
	 *
	 * @param  int    $media_id The media ID.
	 * @param  string $context  The context.
	 * @return bool|WP_Error    True on success. A \WP_Error instance on failure.
	 */
	protected function restore_media( $media_id, $context ) {
		return imagify_get_optimization_process( $media_id, $context )->restore();
	}

	/** ----------------------------------------------------------------------------------------- */
	/** WP OPTIMIZATION CALLBACKS =============================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Optimize all thumbnails of a specific image with the manual method.
	 *
	 * @since 1.6.11
	 */
	public function imagify_manual_optimize_callback() {
		$context  = $this->get_context();
		$media_id = $this->get_media_id();

		if ( ! $media_id || ! $context ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		imagify_check_nonce( 'imagify-optimize-' . $media_id . '-' . $context );

		if ( ! imagify_get_context( $context )->current_user_can( 'manual-optimize', $media_id ) ) {
			imagify_die();
		}

		$result = $this->optimize_media( $media_id, $context );

		imagify_maybe_redirect( is_wp_error( $result ) ? $result : false );

		if ( is_wp_error( $result ) ) {
			// Return an error message.
			$output = $result->get_error_message();

			wp_send_json_error( [ 'html' => $output ] );
		}

		wp_send_json_success();
	}

	/**
	 * Optimize all thumbnails of a specific image with a different optimization level.
	 *
	 * @since 1.6.11
	 */
	public function imagify_manual_reoptimize_callback() {
		$context  = $this->get_context();
		$media_id = $this->get_media_id();

		if ( ! $media_id || ! $context ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		imagify_check_nonce( 'imagify-manual-reoptimize-' . $media_id . '-' . $context );

		if ( ! imagify_get_context( $context )->current_user_can( 'manual-optimize', $media_id ) ) {
			imagify_die();
		}

		$result = $this->reoptimize_media( $media_id, $context, $this->get_optimization_level() );

		imagify_maybe_redirect( is_wp_error( $result ) ? $result : false );

		if ( is_wp_error( $result ) ) {
			// Return an error message.
			$output = $result->get_error_message();

			wp_send_json_error( [ 'html' => $output ] );
		}

		wp_send_json_success();
	}

	/**
	 * Optimize one or some thumbnails that are not optimized yet.
	 *
	 * @since 1.6.11
	 */
	public function imagify_optimize_missing_sizes_callback() {
		$context  = $this->get_context();
		$media_id = $this->get_media_id();

		if ( ! $media_id || ! $context ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		imagify_check_nonce( 'imagify-optimize-missing-sizes-' . $media_id . '-' . $context );

		if ( ! imagify_get_context( $context )->current_user_can( 'manual-optimize', $media_id ) ) {
			imagify_die();
		}

		$result = $this->optimize_missing_sizes( $media_id, $context );

		imagify_maybe_redirect( is_wp_error( $result ) ? $result : false );

		if ( is_wp_error( $result ) ) {
			// Return an error message.
			$output = $result->get_error_message();

			wp_send_json_error( [ 'html' => $output ] );
		}

		wp_send_json_success();
	}

	/**
	 * Generate WebP images if they are missing.
	 *
	 * @since 1.9
	 */
	public function imagify_generate_webp_versions_callback() {
		$context  = $this->get_context();
		$media_id = $this->get_media_id();

		if ( ! $media_id || ! $context ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		imagify_check_nonce( 'imagify-generate-webp-versions-' . $media_id . '-' . $context );

		if ( ! imagify_get_context( $context )->current_user_can( 'manual-optimize', $media_id ) ) {
			imagify_die();
		}

		$result = $this->generate_webp_versions( $media_id, $context );

		imagify_maybe_redirect( is_wp_error( $result ) ? $result : false );

		if ( is_wp_error( $result ) ) {
			// Return an error message.
			$output = $result->get_error_message();

			wp_send_json_error( [ 'html' => $output ] );
		}

		wp_send_json_success();
	}

	/**
	 * Generate WebP images if they are missing.
	 *
	 * @since 1.9.6
	 */
	public function imagify_delete_webp_versions_callback() {
		$context  = $this->get_context();
		$media_id = $this->get_media_id();

		if ( ! $media_id || ! $context ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		imagify_check_nonce( 'imagify-delete-webp-versions-' . $media_id . '-' . $context );

		if ( ! imagify_get_context( $context )->current_user_can( 'manual-restore', $media_id ) ) {
			imagify_die();
		}

		$result = $this->delete_webp_versions( $media_id, $context );

		imagify_maybe_redirect( is_wp_error( $result ) ? $result : false );

		if ( is_wp_error( $result ) ) {
			// Return an error message.
			$output = $result->get_error_message();

			wp_send_json_error( [ 'html' => $output ] );
		}

		wp_send_json_success();
	}

	/**
	 * Process a restoration to the original attachment.
	 *
	 * @since 1.6.11
	 */
	public function imagify_restore_callback() {
		$context  = $this->get_context();
		$media_id = $this->get_media_id();

		if ( ! $media_id || ! $context ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		imagify_check_nonce( 'imagify-restore-' . $media_id . '-' . $context );

		if ( ! imagify_get_context( $context )->current_user_can( 'manual-restore', $media_id ) ) {
			imagify_die();
		}

		$result = $this->restore_media( $media_id, $context );

		imagify_maybe_redirect( is_wp_error( $result ) ? $result : false );

		if ( is_wp_error( $result ) ) {
			// Return an error message.
			$output = $result->get_error_message();

			wp_send_json_error( [ 'html' => $output ] );
		}

		// Return the optimization button.
		$output = Imagify_Views::get_instance()->get_template( 'button/optimize', [
			'url' => get_imagify_admin_url( 'optimize', array(
				'attachment_id' => $media_id,
				'context'       => $context,
			) ),
		] );

		wp_send_json_success( [ 'html' => $output ] );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** CUSTOM FOLDERS OPTIMIZATION CALLBACKS =================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Optimize a file.
	 *
	 * @since 1.7
	 */
	public function imagify_optimize_file_callback() {
		imagify_check_nonce( 'imagify_optimize_file' );

		$media_id = $this->get_media_id( 'GET', 'id' );

		if ( ! $media_id ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		if ( ! imagify_get_context( 'custom-folders' )->current_user_can( 'manual-optimize', $media_id ) ) {
			imagify_die();
		}

		$result = $this->optimize_media( $media_id, 'custom-folders' );

		imagify_maybe_redirect( is_wp_error( $result ) ? $result : false );

		if ( is_wp_error( $result ) ) {
			// Return an error message.
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success();
	}

	/**
	 * Re-optimize a file.
	 *
	 * @since 1.7
	 */
	public function imagify_reoptimize_file_callback() {
		imagify_check_nonce( 'imagify_reoptimize_file' );

		$media_id = $this->get_media_id( 'GET', 'id' );

		if ( ! $media_id ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		if ( ! imagify_get_context( 'custom-folders' )->current_user_can( 'manual-optimize', $media_id ) ) {
			imagify_die();
		}

		$level = $this->get_optimization_level( 'GET', 'level' );

		$result = $this->reoptimize_media( $media_id, 'custom-folders', $level );

		imagify_maybe_redirect( is_wp_error( $result ) ? $result : false );

		if ( is_wp_error( $result ) ) {
			// Return an error message.
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success();
	}

	/**
	 * Restore a file.
	 *
	 * @since 1.7
	 */
	public function imagify_restore_file_callback() {
		imagify_check_nonce( 'imagify_restore_file' );

		$media_id = $this->get_media_id( 'GET', 'id' );

		if ( ! $media_id ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		if ( ! imagify_get_context( 'custom-folders' )->current_user_can( 'manual-restore', $media_id ) ) {
			imagify_die();
		}

		$result = $this->restore_media( $media_id, 'custom-folders' );

		imagify_maybe_redirect( is_wp_error( $result ) ? $result : false );

		if ( is_wp_error( $result ) ) {
			// Return an error message.
			wp_send_json_error( $result->get_error_message() );
		}

		$process = imagify_get_optimization_process( $media_id, 'custom-folders' );
		$this->file_optimization_output( $process );
	}

	/**
	 * Check if a file has been modified, and update the database accordingly.
	 *
	 * @since 1.7
	 */
	public function imagify_refresh_file_modified_callback() {
		imagify_check_nonce( 'imagify_refresh_file_modified' );

		$media_id = $this->get_media_id( 'GET', 'id' );

		if ( ! $media_id ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		if ( ! imagify_get_context( 'custom-folders' )->current_user_can( 'manual-optimize', $media_id ) ) {
			imagify_die();
		}

		$process  = imagify_get_optimization_process( $media_id, 'custom-folders' );
		$result   = Imagify_Custom_Folders::refresh_file( $process );

		if ( is_wp_error( $result ) ) {
			// The media is not valid or has been removed from the database.
			$message = $result->get_error_message();

			imagify_maybe_redirect( $message );

			wp_send_json_error( array(
				'row' => $message,
			) );
		}

		imagify_maybe_redirect();

		// Return some HTML to the ajax call.
		$this->file_optimization_output( $process );
	}

	/**
	 * Look for new files in custom folders.
	 *
	 * @since 1.7
	 */
	public function imagify_scan_custom_folders_callback() {
		imagify_check_nonce( 'imagify_scan_custom_folders' );

		if ( ! imagify_get_context( 'custom-folders' )->current_user_can( 'optimize' ) ) {
			imagify_die();
		}

		$folder = (int) filter_input( INPUT_GET, 'folder', FILTER_VALIDATE_INT );

		if ( $folder > 0 ) {
			// A specific custom folder (selected or not).
			$folders_db  = Imagify_Folders_DB::get_instance();
			$folders_key = $folders_db->get_primary_key();
			$folder      = $folders_db->get( $folder );

			if ( ! $folder ) {
				// This should not happen.
				imagify_maybe_redirect( __( 'This folder is not in the database.', 'imagify' ) );
			}

			$folder['folder_path'] = Imagify_Files_Scan::remove_placeholder( $folder['path'] );

			$folders = array(
				$folder[ $folders_key ] => $folder,
			);

			Imagify_Custom_Folders::get_files_from_folders( $folders, array(
				'add_inactive_folder_files' => true,
			) );

			imagify_maybe_redirect();
		}

		// All selected custom folders.
		$folders = Imagify_Custom_Folders::get_folders( array(
			'active' => true,
		) );
		Imagify_Custom_Folders::get_files_from_folders( $folders );

		imagify_maybe_redirect();
	}


	/** ----------------------------------------------------------------------------------------- */
	/** SETTINGS PAGE CALLBACKS ================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Check if the backup directory is writable.
	 * This is used to display an error message in the plugin's settings page.
	 *
	 * @since 1.6.11
	 */
	public function imagify_check_backup_dir_is_writable_callback() {
		imagify_check_nonce( 'imagify_check_backup_dir_is_writable' );

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			imagify_die();
		}

		wp_send_json_success( array(
			'is_writable' => (int) Imagify_Requirements::attachments_backup_dir_is_writable(),
		) );
	}

	/**
	 * Get files and folders that are direct children of a given folder.
	 *
	 * @since 1.7
	 */
	public function imagify_get_files_tree_callback() {
		imagify_check_nonce( 'get-files-tree' );

		if ( ! imagify_get_context( 'custom-folders' )->current_user_can( 'manage' ) ) {
			imagify_die();
		}

		if ( ! isset( $_POST['folder'] ) || '' === $_POST['folder'] ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$folder = wp_unslash( $_POST['folder'] );
		$folder = trailingslashit( sanitize_text_field( $folder ) );
		$folder = realpath( $this->filesystem->get_site_root() . ltrim( $folder, '/' ) );

		if ( ! $folder ) {
			imagify_die( __( 'This folder doesn\'t exist.', 'imagify' ) );
		}

		if ( ! $this->filesystem->is_dir( $folder ) ) {
			imagify_die( __( 'This file is not a folder.', 'imagify' ) );
		}

		$folder = $this->filesystem->normalize_dir_path( $folder );

		if ( Imagify_Files_Scan::is_path_forbidden( $folder ) ) {
			imagify_die( __( 'This folder is not allowed.', 'imagify' ) );
		}

		// Finally we made all our validations.
		$selected = ! empty( $_POST['selected'] ) && is_array( $_POST['selected'] ) ? array_flip( wp_unslash( $_POST['selected'] ) ) : array();
		$views    = Imagify_Views::get_instance();
		$output   = '';

		if ( $this->filesystem->is_site_root( $folder ) ) {
			$output .= $views->get_template( 'part-settings-files-tree-row', array(
				'relative_path'     => '/',
				// Value #///# Label.
				'checkbox_value'    => '{{ROOT}}/#///#' . esc_attr__( 'Site\'s root', 'imagify' ),
				'checkbox_id'       => 'ABSPATH',
				'checkbox_selected' => isset( $selected['{{ROOT}}/'] ),
				'label'             => __( 'Site\'s root', 'imagify' ),
				'no_button'         => true,
			) );
		}

		$dir    = new DirectoryIterator( $folder );
		$dir    = new Imagify_Files_Iterator( $dir );
		$images = 0;

		foreach ( new IteratorIterator( $dir ) as $file ) {
			if ( ! $file->isDir() ) {
				++$images;
				continue;
			}

			$folder_path   = trailingslashit( $file->getPathname() );
			$relative_path = $this->filesystem->make_path_relative( $folder_path );
			$placeholder   = Imagify_Files_Scan::add_placeholder( $folder_path );

			$output .= $views->get_template( 'part-settings-files-tree-row', array(
				'relative_path'     => esc_attr( $relative_path ),
				// Value #///# Label.
				'checkbox_value'    => esc_attr( $placeholder ) . '#///#' . esc_attr( $relative_path ),
				'checkbox_id'       => sanitize_html_class( $placeholder ),
				'checkbox_selected' => isset( $selected[ $placeholder ] ),
				'label'             => $this->filesystem->file_name( $folder_path ),
			) );
		}

		if ( $images ) {
			/* translators: %s is a formatted number, dont use %d. */
			$output .= '<li class="imagify-number-of-images-in-folder"><em><span class="dashicons dashicons-images-alt"></span> ' . sprintf( _n( '%s Media File', '%s Media Files', $images, 'imagify' ), number_format_i18n( $images ) ) . '</em></li>';
		}

		if ( ! $output ) {
			$output .= '<li class="imagify-empty-folder"><em>' . __( 'No optimizable files', 'imagify' ) . '</em></li>';
		}

		wp_send_json_success( $output );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** IMAGIFY ACCOUNT CALLBACKS =============================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Create a new Imagify account.
	 *
	 * @since 1.6.11
	 */
	public function imagify_signup_callback() {
		imagify_check_nonce( 'imagify-signup', 'imagifysignupnonce' );

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			imagify_die();
		}

		if ( empty( $_GET['email'] ) ) {
			imagify_die( __( 'Empty email address.', 'imagify' ) );
		}

		$email = wp_unslash( $_GET['email'] );

		if ( ! is_email( $email ) ) {
			imagify_die( __( 'Not a valid email address.', 'imagify' ) );
		}

		$data = array(
			'email'    => $email,
			'password' => wp_generate_password( 12, false ),
			'lang'     => imagify_get_locale(),
		);

		$response = add_imagify_user( $data );

		if ( is_wp_error( $response ) ) {
			imagify_die( $response );
		}

		wp_send_json_success();
	}

	/**
	 * Check the API key validity.
	 *
	 * @since 1.6.11
	 */
	public function imagify_check_api_key_validity_callback() {
		imagify_check_nonce( 'imagify-check-api-key', 'imagifycheckapikeynonce' );

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			imagify_die();
		}

		if ( empty( $_GET['api_key'] ) ) {
			imagify_die( __( 'Empty API key.', 'imagify' ) );
		}

		$api_key  = wp_unslash( $_GET['api_key'] );
		$response = get_imagify_status( $api_key );

		if ( is_wp_error( $response ) ) {
			imagify_die( $response );
		}

		update_imagify_option( 'api_key', $api_key );

		wp_send_json_success();
	}

	/**
	 * Get admin bar profile output.
	 *
	 * @since 1.6.11
	 */
	public function imagify_get_admin_bar_profile_callback() {
		imagify_check_nonce( 'imagify-get-admin-bar-profile', 'imagifygetadminbarprofilenonce' );

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			imagify_die();
		}

		$user             = new Imagify_User();
		$views            = Imagify_Views::get_instance();
		$unconsumed_quota = $views->get_quota_percent();
		$message          = '';

		if ( $unconsumed_quota <= 20 ) {
			$message  = '<div class="imagify-error">';
				$message .= '<p><i class="dashicons dashicons-warning" aria-hidden="true"></i><strong>' . __( 'Oops, It\'s almost over!', 'imagify' ) . '</strong></p>';
				/* translators: %s is a line break. */
				$message .= '<p>' . sprintf( __( 'You have almost used all your credit.%sDon\'t forget to upgrade your subscription to continue optimizing your images.', 'imagify' ), '<br/><br/>' ) . '</p>';
				$message .= '<p class="center txt-center text-center"><a class="btn imagify-btn-ghost" href="' . esc_url( imagify_get_external_url( 'subscription' ) ) . '" target="_blank">' . __( 'View My Subscription', 'imagify' ) . '</a></p>';
			$message .= '</div>';
		}

		if ( 0 === $unconsumed_quota ) {
			$message  = '<div class="imagify-error">';
				$message .= '<p><i class="dashicons dashicons-warning" aria-hidden="true"></i><strong>' . __( 'Oops, It\'s Over!', 'imagify' ) . '</strong></p>';
				$message .= '<p>' . sprintf(
					/* translators: 1 is a data quota, 2 is a date. */
					__( 'You have consumed all your credit for this month. You will have <strong>%1$s back on %2$s</strong>.', 'imagify' ),
					imagify_size_format( $user->quota * pow( 1024, 2 ) ),
					date_i18n( get_option( 'date_format' ), strtotime( $user->next_date_update ) )
				) . '</p>';
				$message .= '<p class="center txt-center text-center"><a class="btn imagify-btn-ghost" href="' . esc_url( imagify_get_external_url( 'subscription' ) ) . '" target="_blank">' . __( 'Upgrade My Subscription', 'imagify' ) . '</a></p>';
			$message .= '</div>';
		}

		// Custom HTML.
		$quota_section  = '<div class="imagify-admin-bar-quota">';
			$quota_section .= '<div class="imagify-abq-row">';

		if ( 1 === $user->plan_id ) {
			$quota_section .= '<div class="imagify-meteo-icon">' . $views->get_quota_icon() . '</div>';
		}

		$quota_section .= '<div class="imagify-account">';
			$quota_section .= '<p class="imagify-meteo-title">' . __( 'Account status', 'imagify' ) . '</p>';
			$quota_section .= '<p class="imagify-meteo-subs">' . __( 'Your subscription:', 'imagify' ) . '&nbsp;<strong class="imagify-user-plan">' . $user->plan_label . '</strong></p>';
		$quota_section .= '</div>'; // .imagify-account
		$quota_section .= '</div>'; // .imagify-abq-row

		if ( 1 === $user->plan_id ) {
			$quota_section .= '<div class="imagify-abq-row">';
				$quota_section .= '<div class="imagify-space-left">';
					/* translators: %s is a data quota. */
					$quota_section .= '<p>' . sprintf( __( 'You have %s space credit left', 'imagify' ), '<span class="imagify-unconsumed-percent">' . $unconsumed_quota . '%</span>' ) . '</p>';
					$quota_section .= '<div class="' . $views->get_quota_class() . '">';
						$quota_section .= '<div style="width: ' . $unconsumed_quota . '%;" class="imagify-unconsumed-bar imagify-progress"></div>';
					$quota_section .= '</div>'; // .imagify-bar-{negative|neutral|positive}
				$quota_section .= '</div>'; // .imagify-space-left
			$quota_section .= '</div>'; // .imagify-abq-row
		}

		$quota_section .= '<p class="imagify-abq-row">';
			$quota_section .= '<a class="imagify-account-link" href="' . esc_url( imagify_get_external_url( 'subscription' ) ) . '" target="_blank">';
				$quota_section .= '<span class="dashicons dashicons-admin-users"></span>';
				$quota_section .= '<span class="button-text">' . __( 'View my subscription', 'imagify' ) . '</span>';
			$quota_section .= '</a>'; // .imagify-account-link
		$quota_section .= '</p>'; // .imagify-abq-row
		$quota_section .= '</div>'; // .imagify-admin-bar-quota
		$quota_section .= $message;

		wp_send_json_success( $quota_section );
	}

	/**
	 * Get pricings from API for Onetime and Plans at the same time.
	 *
	 * @since 1.6.11
	 */
	public function imagify_get_prices_callback() {
		imagify_check_nonce( 'imagify_get_pricing_' . get_current_user_id(), 'imagifynonce' );

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			imagify_die();
		}

		$prices_all = get_imagify_all_prices();

		if ( is_wp_error( $prices_all ) ) {
			imagify_die( $prices_all );
		}

		if ( ! is_object( $prices_all ) ) {
			imagify_die( __( 'Wrongly formatted response from our server.', 'imagify' ) );
		}

		wp_send_json_success( array(
			'monthlies' => $prices_all->Plans,
		) );
	}

	/**
	 * Check Coupon code on modal popin.
	 *
	 * @since 1.6.11
	 */
	public function imagify_check_coupon_callback() {
		imagify_check_nonce( 'imagify_get_pricing_' . get_current_user_id(), 'imagifynonce' );

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			imagify_die();
		}

		if ( empty( $_POST['coupon'] ) ) {
			wp_send_json_success( array(
				'success' => false,
				'detail'  => __( 'Coupon is empty.', 'imagify' ),
			) );
		}

		$coupon = wp_unslash( $_POST['coupon'] );
		$coupon = check_imagify_coupon_code( $coupon );

		if ( is_wp_error( $coupon ) ) {
			imagify_die( $coupon );
		}

		wp_send_json_success( imagify_translate_api_message( $coupon ) );
	}

	/**
	 * Get current discount promotion to display information on payment modal.
	 *
	 * @since 1.6.11
	 */
	public function imagify_get_discount_callback() {
		imagify_check_nonce( 'imagify_get_pricing_' . get_current_user_id(), 'imagifynonce' );

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			imagify_die();
		}

		wp_send_json_success( imagify_translate_api_message( check_imagify_discount() ) );
	}

	/**
	 * Get estimated sizes from the WordPress library.
	 *
	 * @since 1.6.11
	 */
	public function imagify_get_images_counts_callback() {
		imagify_check_nonce( 'imagify_get_pricing_' . get_current_user_id(), 'imagifynonce' );

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			imagify_die();
		}

		$raw_total_size_in_library = imagify_calculate_total_size_images_library() + Imagify_Files_Stats::get_overall_original_size();
		$raw_average_per_month     = imagify_calculate_average_size_images_per_month() + Imagify_Files_Stats::calculate_average_size_per_month();

		Imagify_Data::get_instance()->set( array(
			'total_size_images_library'     => $raw_total_size_in_library,
			'average_size_images_per_month' => $raw_average_per_month,
		) );

		wp_send_json_success( array(
			'total_library_size' => array(
				'raw'   => $raw_total_size_in_library,
				'human' => imagify_size_format( $raw_total_size_in_library ),
			),
			'average_month_size' => array(
				'raw'   => $raw_average_per_month,
				'human' => imagify_size_format( $raw_average_per_month ),
			),
		) );
	}

	/**
	 * Estimate sizes and update the options values for them.
	 *
	 * @since 1.6.11
	 */
	public function imagify_update_estimate_sizes_callback() {
		imagify_check_nonce( 'update_estimate_sizes' );

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			imagify_die();
		}

		$raw_total_size_in_library = imagify_calculate_total_size_images_library() + Imagify_Files_Stats::get_overall_original_size();
		$raw_average_per_month     = imagify_calculate_average_size_images_per_month() + Imagify_Files_Stats::calculate_average_size_per_month();

		Imagify_Data::get_instance()->set( array(
			'total_size_images_library'     => $raw_total_size_in_library,
			'average_size_images_per_month' => $raw_average_per_month,
		) );

		die( 1 );
	}

	/**
	 * Get the Imagify User data.
	 *
	 * @since 1.7
	 */
	public function imagify_get_user_data_callback() {
		imagify_check_nonce( 'imagify_get_user_data' );

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			imagify_die();
		}

		$user = imagify_cache_user();

		if ( ! $user || ! $user->id ) {
			imagify_die( __( 'Couldn\'t get user data.', 'imagify' ) );
		}

		// Remove useless sensitive data.
		unset( $user->email );

		if ( ! $user->get_percent_unconsumed_quota ) {
			$user->best_plan_title = __( 'Oops, It\'s Over!', 'imagify' );
		} elseif ( $user->get_percent_unconsumed_quota <= 20 ) {
			$user->best_plan_title = __( 'Oops, It\'s almost over!', 'imagify' );
		} else {
			$user->best_plan_title = __( 'You\'re new to Imagify?', 'imagify' );
		}

		wp_send_json_success( $user );
	}

	/**
	 * Delete the Imagify User data cache.
	 *
	 * @since 1.9.5
	 */
	public function imagify_delete_user_data_cache_callback() {
		imagify_check_nonce( 'imagify_delete_user_data_cache' );

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			imagify_die();
		}

		imagify_delete_cached_user();

		wp_send_json_success();
	}


	/** ----------------------------------------------------------------------------------------- */
	/** VARIOUS CALLBACKS ======================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Bridge between XML-RPC and actions triggered by imagify_do_async_job().
	 * When XML-RPC is used, a current user is set, but no cookies are set, so they cannot be sent with the request. Instead we stored the user ID in a transient.
	 *
	 * @since 1.6.11
	 * @see imagify_do_async_job()
	 */
	public function nopriv_imagify_rpc_callback() {
		if ( empty( $_POST['imagify_rpc_action'] ) || empty( $_POST['imagify_rpc_id'] ) ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$action = wp_unslash( $_POST['imagify_rpc_action'] ); // WPCS: CSRF ok.

		if ( 32 !== strlen( $action ) ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		// Not necessary but just in case, whitelist the original action.
		$actions = array_flip( $this->ajax_only_actions );
		unset( $actions['nopriv_imagify_rpc'] );

		if ( ! isset( $actions[ $action ] ) ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		// Get the user ID.
		$rpc_id  = sanitize_key( $_POST['imagify_rpc_id'] );
		$user_id = absint( get_transient( 'imagify_rpc_' . $rpc_id ) );
		$user    = $user_id ? get_userdata( $user_id ) : false;

		delete_transient( 'imagify_rpc_' . $rpc_id );

		if ( ! $user || ! $user->exists() ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		// The current user must be set before verifying the nonce.
		wp_set_current_user( $user_id );

		imagify_check_nonce( 'imagify_rpc_' . $rpc_id, 'imagify_rpc_nonce' );

		// Trigger the action we originally wanted.
		$_POST['action'] = $action;
		unset( $_POST['imagify_rpc_action'], $_POST['imagify_rpc_id'], $_POST['imagify_rpc_nonce'] );

		/** This hook is documented in wp-admin/admin-ajax.php. */
		do_action( 'wp_ajax_' . $action );
	}

	/**
	 * Store the "closed" status of the ads.
	 *
	 * @since 1.7
	 */
	public function imagify_dismiss_ad_callback() {
		imagify_check_nonce( 'imagify-dismiss-ad' );

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			imagify_die();
		}

		$notice = filter_input( INPUT_GET, 'ad', FILTER_SANITIZE_STRING );

		if ( ! $notice ) {
			imagify_maybe_redirect();
			wp_send_json_error();
		}

		$user_id = get_current_user_id();
		$notices = get_user_meta( $user_id, '_imagify_ignore_ads', true );
		$notices = $notices && is_array( $notices ) ? array_flip( $notices ) : array();

		if ( isset( $notices[ $notice ] ) ) {
			imagify_maybe_redirect();
			wp_send_json_success();
		}

		$notices   = array_flip( $notices );
		$notices[] = $notice;
		$notices   = array_filter( $notices );
		$notices   = array_values( $notices );

		update_user_meta( $user_id, '_imagify_ignore_ads', $notices );

		imagify_maybe_redirect();
		wp_send_json_success();
	}


	/** ----------------------------------------------------------------------------------------- */
	/** VARIOUS HELPERS ========================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the submitted optimization level.
	 *
	 * @since 1.7
	 * @since 1.9 Added $method and $parameter parameters.
	 *
	 * @param  string $method The method used: 'GET' (default), or 'POST'.
	 * @param  string $parameter The name of the parameter to look for.
	 * @return int
	 */
	public function get_optimization_level( $method = 'GET', $parameter = 'optimization_level' ) {
		$method = 'POST' === $method ? INPUT_POST : INPUT_GET;
		$level  = filter_input( $method, $parameter );

		if ( ! is_numeric( $level ) || $level < 0 || $level > 2 ) {
			if ( get_imagify_option( 'lossless' ) ) {
				return 0;
			}

			return get_imagify_option( 'optimization_level' );
		}

		return (int) $level;
	}

	/**
	 * Get the submitted context.
	 *
	 * @since 1.9
	 *
	 * @param  string $method The method used: 'GET' (default), or 'POST'.
	 * @param  string $parameter The name of the parameter to look for.
	 * @return string
	 */
	public function get_context( $method = 'GET', $parameter = 'context' ) {
		$method  = 'POST' === $method ? INPUT_POST : INPUT_GET;
		$context = filter_input( $method, $parameter, FILTER_SANITIZE_STRING );

		return imagify_sanitize_context( $context );
	}

	/**
	 * Get the submitted media ID.
	 *
	 * @since 1.9
	 *
	 * @param  string $method    The method used: 'GET' (default), or 'POST'.
	 * @param  string $parameter The name of the parameter to look for.
	 * @return int
	 */
	public function get_media_id( $method = 'GET', $parameter = 'attachment_id' ) {
		$method   = 'POST' === $method ? INPUT_POST : INPUT_GET;
		$media_id = filter_input( $method, $parameter );

		if ( ! is_numeric( $media_id ) || $media_id < 0 ) {
			return 0;
		}

		return (int) $media_id;
	}

	/**
	 * Get the submitted folder_type.
	 *
	 * @since 1.9
	 *
	 * @param  string $method    The method used: 'GET' (default), or 'POST'.
	 * @param  string $parameter The name of the parameter to look for.
	 * @return string
	 */
	public function get_folder_type( $method = 'GET', $parameter = 'folder_type' ) {
		$method = 'POST' === $method ? INPUT_POST : INPUT_GET;

		return filter_input( $method, $parameter, FILTER_SANITIZE_STRING );
	}

	/**
	 * Get the submitted imagify action.
	 *
	 * @since 1.9
	 *
	 * @param  string $method    The method used: 'GET' (default), or 'POST'.
	 * @param  string $parameter The name of the parameter to look for.
	 * @return string
	 */
	public function get_imagify_action( $method = 'GET', $parameter = 'imagify_action' ) {
		$method = 'POST' === $method ? INPUT_POST : INPUT_GET;
		$action = filter_input( $method, $parameter, FILTER_SANITIZE_STRING );

		return $action ? $action : 'optimize';
	}

	/**
	 * Get the Bulk class name depending on a context.
	 *
	 * @since 1.9
	 *
	 * @param  string $context The context name. Default values are 'wp' and 'custom-folders'.
	 * @return string          The Bulk class name.
	 */
	public function get_bulk_class_name( $context ) {
		switch ( $context ) {
			case 'wp':
				$class_name = '\\Imagify\\Bulk\\WP';
				break;

			case 'custom-folders':
				$class_name = '\\Imagify\\Bulk\\CustomFolders';
				break;

			default:
				$class_name = '\\Imagify\\Bulk\\Noop';
		}

		/**
		* Filter the name of the class to use for bulk process.
		*
		* @since 1.9
		*
		* @param int    $class_name The class name.
		* @param string $context    The context name.
		*/
		$class_name = apply_filters( 'imagify_bulk_class_name', $class_name, $context );

		return '\\' . ltrim( $class_name, '\\' );
	}

	/**
	 * Get the Bulk instance depending on a context.
	 *
	 * @since 1.9
	 *
	 * @param  string $context The context name. Default values are 'wp' and 'custom-folders'.
	 * @return BulkInterface   The optimization process instance.
	 */
	public function get_bulk_instance( $context ) {
		$class_name = $this->get_bulk_class_name( $context );
		return new $class_name();
	}

	/**
	 * Check if the user has a valid account and has quota. Die on failure.
	 *
	 * @since 1.7
	 */
	public function check_can_optimize() {
		if ( ! Imagify_Requirements::is_api_key_valid() ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				wp_send_json_error( array( 'message' => 'invalid-api-key' ) );
			}

			imagify_die( __( 'Your API key is not valid!', 'imagify' ) );
		}

		if ( Imagify_Requirements::is_over_quota() ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				wp_send_json_error( array( 'message' => 'over-quota' ) );
			}

			imagify_die( __( 'You have used all your credits!', 'imagify' ) );
		}
	}

	/**
	 * Get a media columns for the "Other Media" page.
	 *
	 * @since 1.9
	 *
	 * @param  object $process    A \Imagify\Optimization\Process\CustomFolders object.
	 * @param  object $list_table A Imagify_Files_List_Table object.
	 * @return array              An array of HTML, keyed by column name.
	 */
	public function get_media_columns( $process, $list_table ) {
		$item = (object) [ 'process' => $process ];

		return [
			'folder'             => $list_table->get_column( 'folder', $item ),
			'optimization'       => $list_table->get_column( 'optimization', $item ),
			'status'             => $list_table->get_column( 'status', $item ),
			'optimization_level' => $list_table->get_column( 'optimization_level', $item ),
			'actions'            => $list_table->get_column( 'actions', $item ),
			'title'              => $list_table->get_column( 'title', $item ), // This one must remain after the "optimization" column, otherwize the data for the comparison tool won't be up-to-date.
		];
	}

	/**
	 * After a file optimization, restore, or whatever, redirect the user or output HTML for ajax.
	 *
	 * @since 1.7
	 * @since 1.9 Removed parameter $result.
	 * @since 1.9 Added $folder in the returned JSON.
	 *
	 * @param object $process A \Imagify\Optimization\Process\CustomFolders object.
	 */
	protected function file_optimization_output( $process ) {
		$list_table = new Imagify_Files_List_Table( [
			'screen' => 'imagify-files',
		] );

		wp_send_json_success( [
			'columns' => $this->get_media_columns( $process, $list_table ),
		] );
	}
}
