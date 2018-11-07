<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles admin ajax/post callbacks.
 *
 * @since  1.6.11
 * @author Grégory Viguier
 */
class Imagify_Admin_Ajax_Post extends Imagify_Admin_Ajax_Post_Deprecated {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.6.11
	 * @author Grégory Viguier
	 */
	const VERSION = '1.0.6';

	/**
	 * Actions to be triggered on admin ajax and admin post.
	 *
	 * @var    array
	 * @since  1.6.11
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $ajax_post_actions = array(
		'imagify_manual_upload',
		'imagify_manual_override_upload',
		'imagify_optimize_missing_sizes',
		'imagify_restore_upload',
		'imagify_optimize_file',
		'imagify_reoptimize_file',
		'imagify_restore_file',
		'imagify_refresh_file_modified',
	);

	/**
	 * Actions to be triggered only on admin ajax.
	 *
	 * @var    array
	 * @since  1.6.11
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $ajax_only_actions = array(
		'imagify_bulk_upload',
		'imagify_bulk_optimize_file',
		'imagify_auto_optimize',
		'imagify_get_unoptimized_attachment_ids',
		'imagify_get_unoptimized_file_ids',
		'imagify_check_backup_dir_is_writable',
		'nopriv_imagify_rpc',
		'imagify_signup',
		'imagify_check_api_key_validity',
		'imagify_get_admin_bar_profile',
		'imagify_get_prices',
		'imagify_check_coupon',
		'imagify_get_discount',
		'imagify_get_images_counts',
		'imagify_update_estimate_sizes',
		'imagify_get_user_data',
		'imagify_get_files_tree',
		'imagify_get_folder_type_data',
		'imagify_bulk_info_seen',
		'imagify_bulk_get_stats',
	);

	/**
	 * Actions to be triggered only on admin post.
	 *
	 * @var    array
	 * @since  1.6.11
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $post_only_actions = array(
		'imagify_scan_custom_folders',
		'imagify_dismiss_ad',
	);

	/**
	 * Filesystem object.
	 *
	 * @var    object Imagify_Filesystem
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $_instance;

	/**
	 * The constructor.
	 *
	 * @since  1.6.11
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected function __construct() {
		$this->filesystem = Imagify_Filesystem::get_instance();
	}


	/** ----------------------------------------------------------------------------------------- */
	/** PUBLIC METHODS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the main Instance.
	 *
	 * @since  1.6.11
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Launch the hooks.
	 *
	 * @since  1.6.11
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		if ( wp_doing_ajax() ) {
			// Actions triggered only on admin ajax.
			$actions = array_merge( $this->ajax_post_actions, $this->ajax_only_actions );

			foreach ( $actions as $action ) {
				add_action( 'wp_ajax_' . $action, array( $this, $action . '_callback' ) );
			}
		}

		// Actions triggered on both admin ajax and admin post.
		$actions = array_merge( $this->ajax_post_actions, $this->post_only_actions );

		foreach ( $actions as $action ) {
			add_action( 'admin_post_' . $action, array( $this, $action . '_callback' ) );
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** MANUAL OPTIMIZATION ===================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Optimize all thumbnails of a specific image with the manual method.
	 *
	 * @since  1.6.11
	 * @access public
	 * @author Jonathan Buttigieg
	 */
	public function imagify_manual_upload_callback() {
		if ( empty( $_GET['attachment_id'] ) || empty( $_GET['context'] ) ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$context       = imagify_sanitize_context( $_GET['context'] );
		$attachment_id = absint( $_GET['attachment_id'] );

		imagify_check_nonce( 'imagify-manual-upload-' . $attachment_id . '-' . $context );
		imagify_check_user_capacity( 'manual-optimize', $attachment_id );

		$attachment = get_imagify_attachment( $context, $attachment_id, 'imagify_manual_upload' );

		// Optimize it.
		$attachment->optimize();

		imagify_maybe_redirect();

		// Return the optimization statistics.
		$output = get_imagify_attachment_optimization_text( $attachment, $context );
		wp_send_json_success( $output );
	}

	/**
	 * Optimize all thumbnails of a specific image with a different optimization level.
	 *
	 * @since  1.6.11
	 * @access public
	 * @author Jonathan Buttigieg
	 */
	public function imagify_manual_override_upload_callback() {
		if ( empty( $_GET['attachment_id'] ) || empty( $_GET['context'] ) ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$context       = imagify_sanitize_context( $_GET['context'] );
		$attachment_id = absint( $_GET['attachment_id'] );

		imagify_check_nonce( 'imagify-manual-override-upload-' . $attachment_id . '-' . $context );
		imagify_check_user_capacity( 'manual-optimize', $attachment_id );

		$attachment = get_imagify_attachment( $context, $attachment_id, 'imagify_manual_override_upload' );

		// Restore the backup file.
		$attachment->restore();

		// Optimize it.
		$attachment->optimize( $this->get_optimization_level() );

		imagify_maybe_redirect();

		// Return the optimization statistics.
		$output = get_imagify_attachment_optimization_text( $attachment, $context );
		wp_send_json_success( $output );
	}

	/**
	 * Optimize one or some thumbnails that are not optimized yet.
	 *
	 * @since  1.6.11
	 * @access public
	 * @author Grégory Viguier
	 */
	public function imagify_optimize_missing_sizes_callback() {
		if ( empty( $_GET['attachment_id'] ) || empty( $_GET['context'] ) ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$context       = imagify_sanitize_context( $_GET['context'] );
		$attachment_id = absint( $_GET['attachment_id'] );

		imagify_check_nonce( 'imagify-optimize-missing-sizes-' . $attachment_id . '-' . $context );
		imagify_check_user_capacity( 'manual-optimize', $attachment_id );

		$attachment = get_imagify_attachment( $context, $attachment_id, 'imagify_optimize_missing_sizes' );
		$context    = $attachment->get_context();

		if ( ! $attachment->is_image() ) {
			$output = get_imagify_attachment_optimization_text( $attachment, $context );
			wp_send_json_error( $output );
		}

		// Optimize the missing thumbnails.
		$attachment->optimize_missing_thumbnails();

		imagify_maybe_redirect();

		// Return the optimization statistics.
		$output = get_imagify_attachment_optimization_text( $attachment, $context );
		wp_send_json_success( $output );
	}

	/**
	 * Process a restoration to the original attachment.
	 *
	 * @since  1.6.11
	 * @access public
	 * @author Jonathan Buttigieg
	 */
	public function imagify_restore_upload_callback() {
		if ( empty( $_GET['attachment_id'] ) || empty( $_GET['context'] ) ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$context       = imagify_sanitize_context( $_GET['context'] );
		$attachment_id = absint( $_GET['attachment_id'] );

		imagify_check_nonce( 'imagify-restore-upload-' . $attachment_id . '-' . $context );
		imagify_check_user_capacity( 'manual-optimize', $attachment_id );

		$attachment = get_imagify_attachment( $context, $attachment_id, 'imagify_restore_upload' );

		// Restore the backup file.
		$attachment->restore();

		imagify_maybe_redirect();

		// Return the optimization button.
		$output = get_imagify_admin_url( 'manual-upload', array(
			'attachment_id' => $attachment->id,
			'context'       => $context,
		) );
		$output = '<a id="imagify-upload-' . $attachment->id . '" href="' . esc_url( $output ) . '" class="button-primary button-imagify-manual-upload" data-waiting-label="' . esc_attr__( 'Optimizing...', 'imagify' ) . '">' . __( 'Optimize', 'imagify' ) . '</a>';
		wp_send_json_success( $output );
	}

	/**
	 * Optimize all thumbnails of a specific image with the bulk method.
	 *
	 * @since  1.6.11
	 * @access public
	 * @author Jonathan Buttigieg
	 */
	public function imagify_bulk_upload_callback() {
		if ( empty( $_POST['image'] ) || empty( $_POST['context'] ) ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$context       = imagify_sanitize_context( $_POST['context'] );
		$attachment_id = absint( $_POST['image'] );

		imagify_check_nonce( 'imagify-bulk-upload' );
		imagify_check_user_capacity( 'bulk-optimize', $attachment_id );

		$attachment         = get_imagify_attachment( $context, $attachment_id, 'imagify_bulk_upload' );
		$optimization_level = $this->get_optimization_level();

		// Restore it if the optimization level is updated.
		if ( $optimization_level !== $attachment->get_optimization_level() ) {
			$attachment->restore();
		}

		// Optimize it.
		$attachment->optimize( $optimization_level );

		// Return the optimization statistics.
		$fullsize_data = $attachment->get_size_data();
		$data          = array();

		if ( ! $attachment->is_optimized() ) {
			$data['success']    = false;
			$data['error_code'] = '';
			$data['error']      = isset( $fullsize_data['error'] ) ? (string) $fullsize_data['error'] : '';

			if ( ! $attachment->has_error() ) {
				$data['error_code'] = 'already-optimized';
			} else {
				$message = 'You\'ve consumed all your data. You have to upgrade your account to continue';

				if ( $data['error'] === $message ) {
					$data['error_code'] = 'over-quota';
				}
			}

			$data['error'] = imagify_translate_api_message( $data['error'] );

			imagify_die( $data );
		}

		$stats_data = $attachment->get_stats_data();

		$data['success']                     = true;
		$data['original_size_human']         = imagify_size_format( $fullsize_data['original_size'], 2 );
		$data['new_size_human']              = imagify_size_format( $fullsize_data['optimized_size'], 2 );
		$data['overall_saving']              = $stats_data['original_size'] - $stats_data['optimized_size'];
		$data['overall_saving_human']        = imagify_size_format( $data['overall_saving'], 2 );
		$data['original_overall_size']       = $stats_data['original_size'];
		$data['original_overall_size_human'] = imagify_size_format( $data['original_overall_size'], 2 );
		$data['new_overall_size']            = $stats_data['optimized_size'];
		$data['percent_human']               = $fullsize_data['percent'] . '%';
		$data['thumbnails']                  = $attachment->get_optimized_sizes_count();

		wp_send_json_success( $data );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** CUSTOM FOLDERS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Optimize a file.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function imagify_bulk_optimize_file_callback() {
		imagify_check_nonce( 'imagify-bulk-upload' );
		imagify_check_user_capacity( 'optimize-file' );

		$file_id = filter_input( INPUT_POST, 'image', FILTER_VALIDATE_INT );
		$context = imagify_sanitize_context( filter_input( INPUT_POST, 'context', FILTER_SANITIZE_STRING ) );
		$context = ! $context || 'wp' === strtolower( $context ) ? 'File' : $context;

		if ( ! $file_id ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$file = get_imagify_attachment( $context, $file_id, 'imagify_bulk_optimize_file' );

		if ( ! $file->is_valid() ) {
			imagify_die( __( 'Invalid file ID', 'imagify' ) );
		}

		// Restore before re-optimizing.
		if ( false !== $file->get_optimization_level() ) {
			$file->restore();
		}

		// Optimize it.
		$result = $file->optimize( $this->get_optimization_level() );

		// Return the optimization statistics.
		if ( ! $file->is_optimized() ) {
			$data = array(
				'success'    => false,
				'error_code' => '',
				'error'      => (string) $file->get_optimized_error(),
			);

			if ( ! $file->has_error() ) {
				$data['error_code'] = 'already-optimized';
			} else {
				$message = 'You\'ve consumed all your data. You have to upgrade your account to continue';

				if ( $data['error'] === $message ) {
					$data['error_code'] = 'over-quota';
				}
			}

			$data['error'] = imagify_translate_api_message( $data['error'] );

			imagify_die( $data );
		}

		$data = $file->get_size_data();

		wp_send_json_success( array(
			'success'                     => true,
			'original_size_human'         => imagify_size_format( $data['original_size'], 2 ),
			'new_size_human'              => imagify_size_format( $data['optimized_size'], 2 ),
			'overall_saving'              => $data['original_size'] - $data['optimized_size'],
			'overall_saving_human'        => imagify_size_format( $data['original_size'] - $data['optimized_size'], 2 ),
			'original_overall_size'       => $data['original_size'],
			'original_overall_size_human' => imagify_size_format( $data['original_size'], 2 ),
			'new_overall_size'            => $data['optimized_size'],
			'percent_human'               => $data['percent'] . '%',
			'thumbnails'                  => $file->get_optimized_sizes_count(),
		) );
	}

	/**
	 * Optimize a file.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function imagify_optimize_file_callback() {
		imagify_check_nonce( 'imagify_optimize_file' );
		imagify_check_user_capacity( 'optimize-file' );

		$file = $this->get_file_to_optimize( 'imagify_optimize_file' );

		// Optimize it.
		$result = $file->optimize();

		$this->file_optimization_output( $result, $file );
	}

	/**
	 * Re-optimize a file.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function imagify_reoptimize_file_callback() {
		imagify_check_nonce( 'imagify_reoptimize_file' );
		imagify_check_user_capacity( 'optimize-file' );

		$file = $this->get_file_to_optimize( 'imagify_reoptimize_file' );

		// Restore it.
		$result = $file->restore();

		if ( ! is_wp_error( $result ) ) {
			// Optimize it.
			$level  = isset( $_GET['level'] ) && is_numeric( $_GET['level'] ) ? $_GET['level'] : null;
			$result = $file->optimize( $level );
		}

		$this->file_optimization_output( $result, $file );
	}

	/**
	 * Restore a file.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function imagify_restore_file_callback() {
		imagify_check_nonce( 'imagify_restore_file' );
		imagify_check_user_capacity( 'optimize-file' );

		$file = $this->get_file_to_optimize( 'imagify_restore_file' );

		// Restore it.
		$result = $file->restore();

		$this->file_optimization_output( $result, $file );
	}

	/**
	 * Check if a file has been modified, and update the database accordingly.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function imagify_refresh_file_modified_callback() {
		imagify_check_nonce( 'imagify_refresh_file_modified' );
		imagify_check_user_capacity( 'optimize-file' );

		$file   = $this->get_file_to_optimize( 'imagify_refresh_file_modified' );
		$result = Imagify_Custom_Folders::refresh_file( $file );

		if ( is_wp_error( $result ) ) {
			$message = $result->get_error_message();

			imagify_maybe_redirect( $message );

			wp_send_json_error( array(
				'row' => $message,
			) );
		}

		imagify_maybe_redirect();

		// Return some HTML to the ajax call.
		$list_table = new Imagify_Files_List_Table( array(
			'screen' => 'imagify-files',
		) );

		wp_send_json_success( array(
			'folder'             => $list_table->get_column( 'folder', $file ),
			'optimization'       => $list_table->get_column( 'optimization', $file ),
			'status'             => $list_table->get_column( 'status', $file ),
			'optimization_level' => $list_table->get_column( 'optimization_level', $file ),
			'actions'            => $list_table->get_column( 'actions', $file ),
			'title'              => $list_table->get_column( 'title', $file ),
		) );
	}

	/**
	 * Look for new files in custom folders.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function imagify_scan_custom_folders_callback() {
		imagify_check_nonce( 'imagify_scan_custom_folders' );
		imagify_check_user_capacity( 'optimize-file' );

		$folder = filter_input( INPUT_GET, 'folder', FILTER_VALIDATE_INT );

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
	/** AUTOMATIC OPTIMIZATION ================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Optimize image with async request.
	 *
	 * @since  1.8.4
	 * @access public
	 * @author Grégory Viguier
	 * @see    Imagify_Auto_Optimization->do_auto_optimization()
	 */
	public function imagify_auto_optimize_callback() {
		if ( empty( $_POST['_ajax_nonce'] ) || empty( $_POST['attachment_id'] ) || empty( $_POST['context'] ) ) { // WPCS: CSRF ok.
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$attachment_id = absint( $_POST['attachment_id'] );

		imagify_check_nonce( 'imagify_auto_optimize-' . $attachment_id );

		if ( ! get_transient( 'imagify-auto-optimize-' . $attachment_id ) ) {
			imagify_die();
		}

		delete_transient( 'imagify-auto-optimize-' . $attachment_id );

		if ( ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			imagify_die( __( 'This type of file is not supported.', 'imagify' ) );
		}

		$this->check_can_optimize();

		@set_time_limit( 0 );

		/**
		 * Let's start.
		 */
		$context       = imagify_sanitize_context( $_POST['context'] );
		$attachment    = get_imagify_attachment( $context, $attachment_id, 'auto_optimize' );
		$is_new_upload = ! empty( $_POST['is_new_upload'] );

		/**
		 * Triggered before an attachment is auto-optimized.
		 *
		 * @since  1.8.4
		 * @author Grégory Viguier
		 *
		 * @param int  $attachment_id The attachment ID.
		 * @param bool $is_new_upload True if it's a new upload. False otherwize.
		 */
		do_action( 'imagify_before_auto_optimization', $attachment_id, $is_new_upload );

		if ( $is_new_upload ) {
			/**
			 * It's a new upload.
			 */
			// $metadata is provided to tell `$attachment->optimize()` it's a new upload.
			$metadata = wp_get_attachment_metadata( $attachment_id, true );

			// Optimize.
			$attachment->optimize( null, $metadata );
		} else {
			/**
			 * The attachment has already been optimized (or at least it has been tried).
			 */
			// Get the optimization level before remove our data.
			$optimization_level = $attachment->get_optimization_level();

			// Remove old optimization data.
			$attachment->delete_imagify_data();

			// Some specifics for the image editor.
			if ( ! empty( $_POST['data']['do'] ) && 'restore' === $_POST['data']['do'] ) {
				// Restore the backup file.
				$attachment->restore();
			}

			// Optimize.
			$attachment->optimize( $optimization_level );
		}

		/**
		 * Triggered after an attachment is auto-optimized.
		 *
		 * @since  1.8.4
		 * @author Grégory Viguier
		 *
		 * @param int  $attachment_id The attachment ID.
		 * @param bool $is_new_upload True if it's a new upload. False otherwize.
		 */
		do_action( 'imagify_after_auto_optimization', $attachment_id, $is_new_upload );
		die( 1 );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** VARIOUS FOR OPTIMIZATION ================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get all unoptimized attachment ids.
	 *
	 * @since  1.6.11
	 * @access public
	 * @author Jonathan Buttigieg
	 */
	public function imagify_get_unoptimized_attachment_ids_callback() {
		global $wpdb;

		imagify_check_nonce( 'imagify-bulk-upload' );
		imagify_check_user_capacity( 'bulk-optimize' );
		$this->check_can_optimize();

		@set_time_limit( 0 );

		// Get (ordered) IDs.
		$optimization_level = $this->get_optimization_level();

		$mime_types   = Imagify_DB::get_mime_types();
		$statuses     = Imagify_DB::get_post_statuses();
		$nodata_join  = Imagify_DB::get_required_wp_metadata_join_clause();
		$nodata_where = Imagify_DB::get_required_wp_metadata_where_clause( array(
			'prepared' => true,
		) );
		$ids          = $wpdb->get_col( $wpdb->prepare( // WPCS: unprepared SQL ok.
			"
			SELECT p.ID
			FROM $wpdb->posts AS p
				$nodata_join
			LEFT JOIN $wpdb->postmeta AS mt1
				ON ( p.ID = mt1.post_id AND mt1.meta_key = '_imagify_status' )
			LEFT JOIN $wpdb->postmeta AS mt2
				ON ( p.ID = mt2.post_id AND mt2.meta_key = '_imagify_optimization_level' )
			WHERE
				p.post_mime_type IN ( $mime_types )
				AND (
					mt1.meta_value = 'error'
					OR
					mt2.meta_value != %d
					OR
					mt2.post_id IS NULL
				)
				AND p.post_type = 'attachment'
				AND p.post_status IN ( $statuses )
				$nodata_where
			GROUP BY p.ID
			ORDER BY
				CASE mt1.meta_value
					WHEN 'already_optimized' THEN 2
					ELSE 1
				END ASC,
				p.ID DESC
			LIMIT 0, %d",
			$optimization_level,
			imagify_get_unoptimized_attachment_limit()
		) );

		$wpdb->flush();
		unset( $mime_types );
		$ids = array_filter( array_map( 'absint', $ids ) );

		if ( ! $ids ) {
			wp_send_json_success( array() );
		}

		$results = Imagify_DB::get_metas( array(
			// Get attachments filename.
			'filenames'           => '_wp_attached_file',
			// Get attachments data.
			'data'                => '_imagify_data',
			// Get attachments optimization level.
			'optimization_levels' => '_imagify_optimization_level',
			// Get attachments status.
			'statuses'            => '_imagify_status',
		), $ids );

		// First run.
		foreach ( $ids as $i => $id ) {
			$attachment_status             = isset( $results['statuses'][ $id ] )            ? $results['statuses'][ $id ]            : false;
			$attachment_optimization_level = isset( $results['optimization_levels'][ $id ] ) ? $results['optimization_levels'][ $id ] : false;
			$attachment_error              = '';

			if ( isset( $results['data'][ $id ]['sizes']['full']['error'] ) ) {
				$attachment_error = $results['data'][ $id ]['sizes']['full']['error'];
			}

			// Don't try to re-optimize if the optimization level is still the same.
			if ( $optimization_level === $attachment_optimization_level && is_string( $attachment_error ) ) {
				unset( $ids[ $i ] );
				continue;
			}

			// Don't try to re-optimize images already compressed.
			if ( 'already_optimized' === $attachment_status && $attachment_optimization_level >= $optimization_level ) {
				unset( $ids[ $i ] );
				continue;
			}

			$attachment_error = trim( $attachment_error );

			// Don't try to re-optimize images with an empty error message.
			if ( 'error' === $attachment_status && empty( $attachment_error ) ) {
				unset( $ids[ $i ] );
			}
		}

		if ( ! $ids ) {
			wp_send_json_success( array() );
		}

		$ids = array_values( $ids );

		/**
		 * Triggered before testing for file existence.
		 *
		 * @since  1.6.7
		 * @author Grégory Viguier
		 *
		 * @param array $ids                An array of attachment IDs.
		 * @param array $results            An array of the data fetched from the database.
		 * @param int   $optimization_level The optimization level that will be used for the optimization.
		 */
		do_action( 'imagify_bulk_optimize_before_file_existence_tests', $ids, $results, $optimization_level );

		$data = array();

		foreach ( $ids as $i => $id ) {
			if ( empty( $results['filenames'][ $id ] ) ) {
				// Problem.
				continue;
			}

			$file_path = get_imagify_attached_file( $results['filenames'][ $id ] );

			/** This filter is documented in inc/functions/process.php. */
			$file_path = apply_filters( 'imagify_file_path', $file_path );

			if ( ! $file_path || ! $this->filesystem->exists( $file_path ) ) {
				continue;
			}

			$attachment_backup_path        = get_imagify_attachment_backup_path( $file_path );
			$attachment_status             = isset( $results['statuses'][ $id ] )            ? $results['statuses'][ $id ]            : false;
			$attachment_optimization_level = isset( $results['optimization_levels'][ $id ] ) ? $results['optimization_levels'][ $id ] : false;

			// Don't try to re-optimize if there is no backup file.
			if ( 'success' === $attachment_status && $optimization_level !== $attachment_optimization_level && ! $this->filesystem->exists( $attachment_backup_path ) ) {
				continue;
			}

			$data[ '_' . $id ] = get_imagify_attachment_url( $results['filenames'][ $id ] );
		} // End foreach().

		if ( ! $data ) {
			wp_send_json_success( array() );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Get all unoptimized file ids.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function imagify_get_unoptimized_file_ids_callback() {
		imagify_check_nonce( 'imagify-bulk-upload' );
		imagify_check_user_capacity( 'optimize-file' );

		$this->check_can_optimize();

		@set_time_limit( 0 );

		$optimization_level = $this->get_optimization_level();

		/**
		 * Get the folders from DB.
		 */
		$folders = Imagify_Custom_Folders::get_folders( array(
			'active' => true,
		) );

		if ( ! $folders ) {
			wp_send_json_success( array() );
		}

		/**
		 * Triggered before getting file IDs.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param array $folders            An array of folders data.
		 * @param int   $optimization_level The optimization level that will be used for the optimization.
		 */
		do_action( 'imagify_bulk_optimize_files_before_get_files', $folders, $optimization_level );

		/**
		 * Get the files from DB, and from the folders.
		 */
		$files = Imagify_Custom_Folders::get_files_from_folders( $folders, array(
			'optimization_level' => $optimization_level,
		) );

		if ( ! $files ) {
			wp_send_json_success( array() );
		}

		// We need to output file URLs.
		foreach ( $files as $k => $file ) {
			$files[ $k ] = Imagify_Files_Scan::remove_placeholder( $file['path'], 'url' );
		}

		wp_send_json_success( $files );
	}

	/**
	 * Get stats data for a specific folder type.
	 *
	 * @since  1.7
	 * @access public
	 * @see    imagify_get_folder_type_data()
	 * @author Grégory Viguier
	 */
	public function imagify_get_folder_type_data_callback() {
		imagify_check_nonce( 'imagify-bulk-upload' );

		$folder_type = filter_input( INPUT_GET, 'folder_type', FILTER_SANITIZE_STRING );

		if ( 'library' === $folder_type ) {
			imagify_check_user_capacity( 'bulk-optimize' );
		} elseif ( 'custom-folders' === $folder_type ) {
			imagify_check_user_capacity( 'optimize-file' );
		} else {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		/**
		 * Get the formated data.
		 */
		$data = imagify_get_folder_type_data( $folder_type );

		if ( ! $data ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Set the "bulk info" popup state as "seen".
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function imagify_bulk_info_seen_callback() {
		imagify_check_nonce( 'imagify-bulk-upload' );

		$folder_type = filter_input( INPUT_GET, 'folder_type', FILTER_SANITIZE_STRING );

		if ( 'library' === $folder_type ) {
			imagify_check_user_capacity( 'bulk-optimize' );
		} elseif ( 'custom-folders' === $folder_type ) {
			imagify_check_user_capacity( 'optimize-file' );
		} else {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		set_transient( 'imagify_bulk_optimization_infos', 1, WEEK_IN_SECONDS );

		wp_send_json_success();
	}

	/**
	 * Get generic stats to display in the bulk page.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 */
	public function imagify_bulk_get_stats_callback() {
		imagify_check_nonce( 'imagify-bulk-upload' );

		$folder_types = filter_input( INPUT_GET, 'types', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		$folder_types = is_array( $folder_types ) ? array_flip( array_filter( $folder_types ) ) : array();

		if ( ! $folder_types ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		foreach ( $folder_types as $folder_type ) {
			if ( 'library' === $folder_type ) {
				imagify_check_user_capacity( 'bulk-optimize' );
			} elseif ( 'custom-folders' === $folder_type ) {
				imagify_check_user_capacity( 'optimize-file' );
			} else {
				imagify_check_user_capacity( 'bulk-optimize', $folder_type );
			}
		}

		wp_send_json_success( imagify_get_bulk_stats( $folder_types ) );
	}

	/**
	 * Check if the backup directory is writable.
	 * This is used to display an error message in the plugin's settings page.
	 *
	 * @since  1.6.11
	 * @access public
	 * @author Grégory Viguier
	 */
	public function imagify_check_backup_dir_is_writable_callback() {
		imagify_check_nonce( 'imagify_check_backup_dir_is_writable' );
		imagify_check_user_capacity();

		wp_send_json_success( array(
			'is_writable' => (int) Imagify_Requirements::attachments_backup_dir_is_writable(),
		) );
	}

	/**
	 * Bridge between XML-RPC and actions triggered by imagify_do_async_job().
	 * When XML-RPC is used, a current user is set, but no cookies are set, so they cannot be sent with the request. Instead we stored the user ID in a transient.
	 *
	 * @since  1.6.11
	 * @access public
	 * @author Grégory Viguier
	 * @see    imagify_do_async_job()
	 */
	public function nopriv_imagify_rpc_callback() {
		if ( empty( $_POST['imagify_rpc_action'] ) || empty( $_POST['imagify_rpc_id'] ) || 32 !== strlen( $_POST['imagify_rpc_id'] ) ) { // WPCS: CSRF ok.
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		// Not necessary but just in case, whitelist the original action.
		$action  = $_POST['imagify_rpc_action']; // WPCS: CSRF ok.
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
		do_action( 'wp_ajax_' . $_POST['action'] );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** IMAGIFY ACCOUNT ========================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Create a new Imagify account.
	 *
	 * @since  1.6.11
	 * @access public
	 * @author Jonathan Buttigieg
	 */
	public function imagify_signup_callback() {
		imagify_check_nonce( 'imagify-signup', 'imagifysignupnonce' );
		imagify_check_user_capacity();

		if ( empty( $_GET['email'] ) ) {
			imagify_die( __( 'Empty email address.', 'imagify' ) );
		}

		if ( ! is_email( $_GET['email'] ) ) {
			imagify_die( __( 'Not a valid email address.', 'imagify' ) );
		}

		$data = array(
			'email'    => $_GET['email'],
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
	 * @since  1.6.11
	 * @access public
	 * @author Jonathan Buttigieg
	 */
	public function imagify_check_api_key_validity_callback() {
		imagify_check_nonce( 'imagify-check-api-key', 'imagifycheckapikeynonce' );
		imagify_check_user_capacity();

		if ( empty( $_GET['api_key'] ) ) {
			imagify_die( __( 'Empty API key.', 'imagify' ) );
		}

		$response = get_imagify_status( $_GET['api_key'] );

		if ( is_wp_error( $response ) ) {
			imagify_die( $response );
		}

		update_imagify_option( 'api_key', $_GET['api_key'] );

		wp_send_json_success();
	}

	/**
	 * Get admin bar profile output.
	 *
	 * @since  1.6.11
	 * @access public
	 * @author Jonathan Buttigieg
	 */
	public function imagify_get_admin_bar_profile_callback() {
		imagify_check_nonce( 'imagify-get-admin-bar-profile', 'imagifygetadminbarprofilenonce' );
		imagify_check_user_capacity();

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
	 * @since  1.6.11
	 * @access public
	 * @author Geoffrey Crofte
	 */
	public function imagify_get_prices_callback() {
		imagify_check_nonce( 'imagify_get_pricing_' . get_current_user_id(), 'imagifynonce' );
		imagify_check_user_capacity();

		$prices_all = get_imagify_all_prices();

		if ( is_wp_error( $prices_all ) ) {
			imagify_die( $prices_all );
		}

		if ( ! is_object( $prices_all ) ) {
			imagify_die( __( 'Wrongly formatted response from our server.', 'imagify' ) );
		}

		wp_send_json_success( array(
			'onetimes'  => $prices_all->Packs,
			'monthlies' => $prices_all->Plans,
		) );
	}

	/**
	 * Check Coupon code on modal popin.
	 *
	 * @since  1.6.11
	 * @access public
	 * @author Geoffrey Crofte
	 */
	public function imagify_check_coupon_callback() {
		imagify_check_nonce( 'imagify_get_pricing_' . get_current_user_id(), 'imagifynonce' );
		imagify_check_user_capacity();

		if ( empty( $_POST['coupon'] ) ) {
			wp_send_json_success( array(
				'success' => false,
				'detail'  => __( 'Coupon is empty.', 'imagify' ),
			) );
		}

		$coupon = check_imagify_coupon_code( $_POST['coupon'] );

		if ( is_wp_error( $coupon ) ) {
			imagify_die( $coupon );
		}

		wp_send_json_success( imagify_translate_api_message( $coupon ) );
	}

	/**
	 * Get current discount promotion to display information on payment modal.
	 *
	 * @since  1.6.11
	 * @author Geoffrey Crofte
	 */
	public function imagify_get_discount_callback() {
		imagify_check_nonce( 'imagify_get_pricing_' . get_current_user_id(), 'imagifynonce' );
		imagify_check_user_capacity();

		wp_send_json_success( imagify_translate_api_message( check_imagify_discount() ) );
	}

	/**
	 * Get estimated sizes from the WordPress library.
	 *
	 * @since  1.6.11
	 * @access public
	 * @author Geoffrey Crofte
	 */
	public function imagify_get_images_counts_callback() {
		imagify_check_nonce( 'imagify_get_pricing_' . get_current_user_id(), 'imagifynonce' );
		imagify_check_user_capacity();

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
	 * @since  1.6.11
	 * @access public
	 * @author Remy Perona
	 */
	public function imagify_update_estimate_sizes_callback() {
		imagify_check_nonce( 'update_estimate_sizes' );
		imagify_check_user_capacity();

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
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function imagify_get_user_data_callback() {
		imagify_check_nonce( 'imagify_get_user_data' );
		imagify_check_user_capacity();

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
	 * Get files and folders that are direct children of a given folder.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function imagify_get_files_tree_callback() {
		imagify_check_nonce( 'get-files-tree' );
		imagify_check_user_capacity( 'optimize-file' );

		if ( ! isset( $_POST['folder'] ) || '' === $_POST['folder'] ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$folder = trailingslashit( sanitize_text_field( $_POST['folder'] ) );
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
		$selected = ! empty( $_POST['selected'] ) && is_array( $_POST['selected'] ) ? array_flip( $_POST['selected'] ) : array();
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

	/**
	 * Store the "closed" status of the ads.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function imagify_dismiss_ad_callback() {

		imagify_check_nonce( 'imagify-dismiss-ad' );
		imagify_check_user_capacity();

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
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int
	 */
	public function get_optimization_level() {
		$optimization_level = filter_input( INPUT_GET, 'optimization_level', FILTER_SANITIZE_NUMBER_INT );

		if ( isset( $optimization_level ) && $optimization_level >= 0 && $optimization_level <= 2 ) {
			return (int) $optimization_level;
		}

		return get_imagify_option( 'optimization_level' );
	}

	/**
	 * Check if the user has a valid account and has quota. Die on failure.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
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
	 * Depending on the file ID sent, get the corresponding file object.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $identifier The identifier to use in get_imagify_attachment().
	 * @return object             A Imagify_File_Attachment object.
	 */
	protected function get_file_to_optimize( $identifier ) {
		$file_id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );

		if ( ! $file_id ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$file = get_imagify_attachment( 'File', $file_id, $identifier );

		if ( ! $file->is_valid() ) {
			imagify_die( __( 'Invalid file ID', 'imagify' ) );
		}

		return $file;
	}

	/**
	 * After a file optimization, restore, or whatever, redirect the user or output HTML for ajax.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param bool|object $result True if the operation succeeded. A WP_Error object on failure.
	 * @param object      $file   A Imagify_File_Attachment object.
	 */
	protected function file_optimization_output( $result, $file ) {
		imagify_maybe_redirect( is_wp_error( $result ) ? $result : false );

		// Return some HTML to the ajax call.
		$list_table = new Imagify_Files_List_Table( array(
			'screen' => 'imagify-files',
		) );

		wp_send_json_success( array(
			'optimization'       => $list_table->get_column( 'optimization', $file ),
			'status'             => $list_table->get_column( 'status', $file ),
			'optimization_level' => $list_table->get_column( 'optimization_level', $file ),
			'actions'            => $list_table->get_column( 'actions', $file ),
			'title'              => $list_table->get_column( 'title', $file ), // This one must remain after the "optimization" column, otherwize the data for the comparison tool won't be up-to-date.
		) );
	}
}
