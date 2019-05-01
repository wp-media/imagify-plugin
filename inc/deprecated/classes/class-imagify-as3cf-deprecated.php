<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class for deprecated methods from Imagify_AS3CF.
 *
 * @since  1.7
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_AS3CF_Deprecated {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.0
	 * @since  1.9 Deprecated
	 * @author Grégory Viguier
	 * @deprecated
	 */
	const VERSION = '1.2';

	/**
	 * Context used with get_imagify_attachment().
	 * It matches the class name Imagify_AS3CF_Attachment.
	 *
	 * @var    string
	 * @since  1.6.6
	 * @since  1.9 Deprecated
	 * @author Grégory Viguier
	 * @deprecated
	 */
	const CONTEXT = 'AS3CF';


	/** ----------------------------------------------------------------------------------------- */
	/** VARIOUS HOOKS =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Filter the context used for the optimization (and other stuff).
	 * That way, we'll use the class Imagify_AS3CF_Attachment everywhere (instead of Imagify_Attachment), and make all the manual optimizations fine.
	 *
	 * @since  1.6.6
	 * @since  1.9 Deprecated
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  string $context       The context to determine the class name.
	 * @param  int    $attachment_id The attachment ID.
	 * @return string                The new context.
	 */
	public function optimize_attachment_context( $context, $attachment_id ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9' );

		if ( self::CONTEXT === $context || ( 'wp' === $context && imagify_is_attachment_mime_type_supported( $attachment_id ) ) ) {
			return self::CONTEXT;
		}
		return $context;
	}

	/**
	 * When getting all unoptimized attachment ids before performing a bulk optimization, download the missing files from S3.
	 *
	 * @since  1.6.7
	 * @since  1.9 Deprecated
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param array $ids                An array of attachment IDs.
	 * @param array $results            An array of the data fetched from the database.
	 * @param int   $optimization_level The optimization level that will be used for the optimization.
	 */
	public function maybe_copy_files_from_s3( $ids, $results, $optimization_level ) {
		global $wpdb, $as3cf;

		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9' );

		if ( ! $as3cf || ! $as3cf->is_plugin_setup() ) {
			return;
		}

		// Remove from the list files that exist.
		$ids = array_flip( $ids );

		foreach ( $ids as $id => $i ) {
			if ( empty( $results['filenames'][ $id ] ) ) {
				// Problem.
				unset( $ids[ $id ] );
				continue;
			}

			$file_path = get_imagify_attached_file( $results['filenames'][ $id ] );

			/** This filter is documented in inc/deprecated/deprecated.php. */
			$file_path = apply_filters( 'imagify_file_path', $file_path, $id, 'as3cf_maybe_copy_files_from_s3' );

			if ( ! $file_path || $this->filesystem->exists( $file_path ) ) {
				// The file exists, no need to retrieve it from S3.
				unset( $ids[ $id ] );
			} else {
				$ids[ $id ] = $file_path;
			}
		}

		if ( ! $ids ) {
			// All files are already on the server.
			return;
		}

		// Determine which files are on S3.
		$ids     = array_flip( $ids );
		$sql_ids = implode( ',', $ids );

		$s3_data = $wpdb->get_results( // WPCS: unprepared SQL ok.
			"SELECT pm.post_id as id, pm.meta_value as value
			FROM $wpdb->postmeta as pm
			WHERE pm.meta_key = 'amazonS3_info'
				AND pm.post_id IN ( $sql_ids )
			ORDER BY pm.post_id DESC",
			ARRAY_A
		);

		$wpdb->flush();

		if ( ! $s3_data ) {
			return;
		}

		unset( $sql_ids );
		$s3_data = Imagify_DB::combine_query_results( $ids, $s3_data, true );

		// Retrieve the missing files from S3.
		$ids = array_flip( $ids );

		foreach ( $s3_data as $id => $s3_object ) {
			$s3_object = maybe_unserialize( $s3_object );
			$file_path = $ids[ $id ];

			$attachment_backup_path        = get_imagify_attachment_backup_path( $file_path );
			$attachment_status             = isset( $results['statuses'][ $id ] )            ? $results['statuses'][ $id ]            : false;
			$attachment_optimization_level = isset( $results['optimization_levels'][ $id ] ) ? $results['optimization_levels'][ $id ] : false;

			// Don't try to re-optimize if there is no backup file.
			if ( 'success' === $attachment_status && $optimization_level !== $attachment_optimization_level && ! $this->filesystem->exists( $attachment_backup_path ) ) {
				unset( $s3_data[ $id ], $ids[ $id ] );
				continue;
			}

			$directory        = $this->filesystem->dir_path( $s3_object['key'] );
			$directory        = $this->filesystem->is_root( $directory ) ? '' : $directory;
			$s3_object['key'] = $directory . $this->filesystem->file_name( $file_path );

			// Retrieve file from S3.
			if ( method_exists( $as3cf->plugin_compat, 'copy_s3_file_to_server' ) ) {
				$as3cf->plugin_compat->copy_s3_file_to_server( $s3_object, $file_path );
			} else {
				$as3cf->plugin_compat->copy_provider_file_to_server( $s3_object, $file_path );
			}

			unset( $s3_data[ $id ], $ids[ $id ] );
		}
	}

	/** ----------------------------------------------------------------------------------------- */
	/** AUTOMATIC OPTIMIZATION: OPTIMIZE AFTER S3 HAS DONE ITS WORK ============================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Filter the generated attachment meta data.
	 * This is used when a new attachment has just been uploaded (or not, when wp_generate_attachment_metadata() is used).
	 * We use it to tell the difference later in wp_update_attachment_metadata().
	 *
	 * @since  1.6.6
	 * @since  1.8.4 Deprecated
	 * @author Grégory Viguier
	 * @see    $this->do_async_job()
	 * @deprecated
	 *
	 * @param  array $metadata      An array of attachment meta data.
	 * @param  int   $attachment_id Current attachment ID.
	 * @return array
	 */
	public function store_upload_ids( $metadata, $attachment_id ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4' );

		if ( imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			$this->uploads[ $attachment_id ] = 1;
		}

		return $metadata;
	}

	/**
	 * After an image (maybe) being sent to S3, launch an async optimization.
	 *
	 * @since  1.6.6
	 * @since  1.8.4 Deprecated
	 * @author Grégory Viguier
	 * @see    $this->store_upload_ids()
	 * @deprecated
	 *
	 * @param  array $metadata      An array of attachment meta data.
	 * @param  int   $attachment_id Current attachment ID.
	 * @return array
	 */
	public function do_async_job( $metadata, $attachment_id ) {
		static $auto_optimize;

		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4' );

		$is_new_upload = ! empty( $this->uploads[ $attachment_id ] );
		unset( $this->uploads[ $attachment_id ] );

		if ( ! $metadata || ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			return $metadata;
		}

		if ( ! isset( $auto_optimize ) ) {
			$auto_optimize = Imagify_Requirements::is_api_key_valid() && get_imagify_option( 'auto_optimize' );
		}

		if ( $is_new_upload ) {
			// It's a new upload.
			if ( ! $auto_optimize ) {
				// Auto-optimization is disabled.
				return $metadata;
			}

			/** This filter is documented in inc/common/attachments.php. */
			$optimize = apply_filters( 'imagify_auto_optimize_attachment', true, $attachment_id, $metadata );

			if ( ! $optimize ) {
				return $metadata;
			}
		}

		if ( ! $is_new_upload ) {
			$attachment = get_imagify_attachment( self::CONTEXT, $attachment_id, 'as3cf_async_job' );

			if ( ! $attachment->get_data() ) {
				// It's not a new upload and the attachment is not optimized yet.
				return $metadata;
			}
		}

		$data = array();

		// Some specifics for the image editor.
		if ( isset( $_POST['action'], $_POST['do'], $_POST['postid'] ) && 'image-editor' === $_POST['action'] && (int) $_POST['postid'] === $attachment_id ) { // WPCS: CSRF ok.
			check_ajax_referer( 'image_editor-' . $_POST['postid'] );
			$data = $_POST;
		}

		imagify_do_async_job( array(
			'action'      => 'imagify_async_optimize_as3cf',
			'_ajax_nonce' => wp_create_nonce( 'imagify_async_optimize_as3cf' ),
			'post_id'     => $attachment_id,
			'metadata'    => $metadata,
			'data'        => $data,
		) );

		return $metadata;
	}

	/**
	 * Once an image has been sent to S3, optimize it and send it again.
	 *
	 * @since  1.6.6
	 * @since  1.8.4 Deprecated
	 * @author Grégory Viguier
	 * @deprecated
	 */
	public function optimize() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4' );

		check_ajax_referer( 'imagify_async_optimize_as3cf' );

		if ( empty( $_POST['post_id'] ) || ! imagify_current_user_can( 'auto-optimize' ) ) {
			die();
		}

		$attachment_id = absint( $_POST['post_id'] );

		if ( ! $attachment_id || empty( $_POST['metadata'] ) || ! is_array( $_POST['metadata'] ) || empty( $_POST['metadata']['sizes'] ) ) {
			die();
		}

		if ( ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			die();
		}

		$optimization_level = null;
		$attachment         = get_imagify_attachment( self::CONTEXT, $attachment_id, 'as3cf_optimize' );

		// Some specifics for the image editor.
		if ( ! empty( $_POST['data']['do'] ) ) {
			$optimization_level = $attachment->get_optimization_level();

			// Remove old optimization data.
			$attachment->delete_imagify_data();

			if ( 'restore' === $_POST['data']['do'] ) {
				// Restore the backup file.
				$attachment->restore();
			}
		}

		// Optimize it.
		$attachment->optimize( $optimization_level, $_POST['metadata'] );
	}
}
