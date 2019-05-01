<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class for deprecated methods from Imagify_Admin_Ajax_Post.
 *
 * @since  1.8.4
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_Admin_Ajax_Post_Deprecated {

	/**
	 * Optimize image on picture uploading with async request.
	 *
	 * @since  1.6.11
	 * @since  1.8.4 Deprecated
	 * @access public
	 * @author Julio Potier
	 * @see    _imagify_optimize_attachment()
	 * @deprecated
	 */
	public function imagify_async_optimize_upload_new_media_callback() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_auto_optimize_callback()' );

		if ( empty( $_POST['_ajax_nonce'] ) || empty( $_POST['attachment_id'] ) || empty( $_POST['metadata'] ) || empty( $_POST['context'] ) ) { // WPCS: CSRF ok.
			return;
		}

		$context       = imagify_sanitize_context( $_POST['context'] );
		$attachment_id = absint( $_POST['attachment_id'] );

		imagify_check_nonce( 'new_media-' . $attachment_id );
		imagify_check_user_capacity( 'auto-optimize' );

		$attachment = get_imagify_attachment( $context, $attachment_id, 'imagify_async_optimize_upload_new_media' );

		// Optimize it!!!!!
		$attachment->optimize( null, $_POST['metadata'] );
		die( 1 );
	}

	/**
	 * Optimize image on picture editing (resize, crop...) with async request.
	 *
	 * @since  1.6.11
	 * @since  1.8.4 Deprecated
	 * @access public
	 * @author Julio Potier
	 * @deprecated
	 */
	public function imagify_async_optimize_save_image_editor_file_callback() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_auto_optimize_callback()' );

		$attachment_id = ! empty( $_POST['postid'] ) ? absint( $_POST['postid'] ) : 0;

		if ( ! $attachment_id || empty( $_POST['do'] ) ) {
			return;
		}

		imagify_check_nonce( 'image_editor-' . $attachment_id );
		imagify_check_user_capacity( 'edit_post', $attachment_id );

		$attachment = get_imagify_attachment( 'wp', $attachment_id, 'wp_ajax_imagify_async_optimize_save_image_editor_file' );

		if ( ! $attachment->get_data() ) {
			return;
		}

		$optimization_level = $attachment->get_optimization_level();
		$metadata           = wp_get_attachment_metadata( $attachment_id );

		// Remove old optimization data.
		$attachment->delete_imagify_data();

		if ( 'restore' === $_POST['do'] ) {
			// Restore the backup file.
			$attachment->restore();

			// Get old metadata to regenerate all thumbnails.
			$metadata     = array( 'sizes' => array() );
			$backup_sizes = (array) get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );

			foreach ( $backup_sizes as $size_key => $size_data ) {
				$size_key = str_replace( '-origin', '' , $size_key );
				$metadata['sizes'][ $size_key ] = $size_data;
			}
		}

		// Optimize it!!!!!
		$attachment->optimize( $optimization_level, $metadata );
		die( 1 );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** CUSTOM FOLDERS CALLBACKS ================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Optimize a file.
	 *
	 * @since  1.7
	 * @since  1.9 Deprecated
	 * @access public
	 * @author Grégory Viguier
	 * @deprecated
	 */
	public function imagify_bulk_optimize_file_callback() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9', '$this->imagify_bulk_optimize_callback()' );

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


	/** ----------------------------------------------------------------------------------------- */
	/** AUTOMATIC OPTIMIZATION ================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Auto-optimize files.
	 *
	 * @since  1.8.4
	 * @since  1.9 Deprecated
	 * @access public
	 * @author Grégory Viguier
	 * @see    Imagify_Auto_Optimization->do_auto_optimization()
	 * @deprecated
	 */
	public function imagify_auto_optimize_callback() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9' );

		if ( empty( $_POST['_ajax_nonce'] ) || empty( $_POST['attachment_id'] ) || empty( $_POST['context'] ) ) { // WPCS: CSRF ok.
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$media_id = $this->get_media_id( 'POST' );

		imagify_check_nonce( 'imagify_auto_optimize-' . $media_id );

		if ( ! get_transient( 'imagify-auto-optimize-' . $media_id ) ) {
			imagify_die();
		}

		delete_transient( 'imagify-auto-optimize-' . $media_id );

		$context = $this->get_context( 'POST' );
		$process = imagify_get_optimization_process( $media_id, $context );

		if ( ! $process->is_valid() ) {
			imagify_die( __( 'This media is not valid.', 'imagify' ) );
		}

		if ( ! $process->get_media()->is_supported() ) {
			imagify_die( __( 'This type of file is not supported.', 'imagify' ) );
		}

		$this->check_can_optimize();

		/**
		 * Let's start.
		 */
		$is_new_upload = ! empty( $_POST['is_new_upload'] );

		/**
		 * Triggered before a media is auto-optimized.
		 *
		 * @since  1.8.4
		 * @author Grégory Viguier
		 *
		 * @param int  $media_id      The media ID.
		 * @param bool $is_new_upload True if it's a new upload. False otherwize.
		 */
		do_action( 'imagify_before_auto_optimization', $media_id, $is_new_upload );

		if ( $is_new_upload ) {
			/**
			 * It's a new upload.
			 */
			// Optimize.
			$process->optimize();
		} else {
			/**
			 * The media has already been optimized (or at least it has been tried).
			 */
			$data = $process->get_data();

			// Get the optimization level before deleting the optimization data.
			$optimization_level = $data->get_optimization_level();

			// Remove old optimization data.
			$data->delete_imagify_data();

			// Some specifics for the image editor.
			if ( ! empty( $_POST['data']['do'] ) && 'restore' === $_POST['data']['do'] ) {
				// Restore the backup file.
				$process->restore();
			}

			// Optimize.
			$process->optimize( $optimization_level );
		}

		/**
		 * Triggered after a media is auto-optimized.
		 *
		 * @since  1.8.4
		 * @author Grégory Viguier
		 *
		 * @param int  $media_id      The media ID.
		 * @param bool $is_new_upload True if it's a new upload. False otherwize.
		 */
		do_action( 'imagify_after_auto_optimization', $media_id, $is_new_upload );
		die( 1 );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** VARIOUS FOR OPTIMIZATION ================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get all unoptimized attachment ids.
	 *
	 * @since  1.6.11
	 * @since  1.9 Deprecated
	 * @access public
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	public function imagify_get_unoptimized_attachment_ids_callback() {
		global $wpdb;

		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9', '$this->imagify_get_media_ids_callback()' );

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

			/** This filter is documented in inc/deprecated/deprecated.php. */
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
	 * @since  1.9 Deprecated
	 * @access public
	 * @author Grégory Viguier
	 * @deprecated
	 */
	public function imagify_get_unoptimized_file_ids_callback() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9', '$this->imagify_get_media_ids_callback()' );

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
}
