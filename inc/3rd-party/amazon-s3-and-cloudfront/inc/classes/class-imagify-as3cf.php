<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify WP Offload S3 class.
 *
 * @since  1.6.6
 * @author Grégory Viguier
 */
class Imagify_AS3CF {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0';

	/**
	 * Context used with get_imagify_attachment_class_name().
	 * It matches the class name Imagify_AS3CF_Attachment.
	 *
	 * @var string
	 */
	const CONTEXT = 'AS3CF';

	/**
	 * An array containing the IDs (as keys) of attachments just being uploaded.
	 *
	 * @var array
	 */
	protected $uploads = array();

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $_instance;

	/**
	 * Get the main Instance.
	 *
	 * Ensures only one instance of class is loaded or can be loaded.
	 *
	 * @since  1.6.6
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
	 * The class constructor.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 */
	protected function __construct() {}

	/**
	 * Launch the hooks.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 */
	public function init() {
		static $done = false;

		if ( $done ) {
			return;
		}
		$done = true;

		/**
		 * One context to rule 'em all.
		 */
		add_filter( 'imagify_optimize_attachment_context', array( $this, 'optimize_attachment_context' ), 10, 2 );

		/**
		 * Bulk optimization.
		 */
		add_action( 'imagify_bulk_optimize_before_file_existence_tests', array( $this, 'maybe_copy_files_from_s3' ), 8, 3 );

		/**
		 * Stats.
		 */
		add_filter( 'imagify_total_attachment_filesize', array( $this, 'add_stats_for_s3_files' ), 8, 4 );

		/**
		 * Automatic optimisation.
		 */
		// Remove some of our hooks: let S3 work first in these cases.
		remove_filter( 'wp_generate_attachment_metadata',                       '_imagify_optimize_attachment', PHP_INT_MAX );
		remove_action( 'wp_ajax_imagify_async_optimize_as3cf',                  '_do_admin_post_async_optimize_upload_new_media' );
		remove_action( 'shutdown',                                              '_imagify_optimize_save_image_editor_file' );
		remove_action( 'wp_ajax_imagify_async_optimize_save_image_editor_file', '_do_admin_post_async_optimize_save_image_editor_file' );

		// Store the IDs of the attachments being uploaded.
		add_filter( 'wp_generate_attachment_metadata',      array( $this, 'store_upload_ids' ), 10, 2 );
		// Once uploaded to S3, launch the async optimization.
		add_filter( 'wp_update_attachment_metadata',        array( $this, 'do_async_job' ), 210, 2 );
		// Do the optimization in a new thread.
		add_action( 'wp_ajax_imagify_async_optimize_as3cf', array( $this, 'optimize' ) );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** VARIOUS HOOKS =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Filter the context used for the optimization (and other stuff).
	 * That way, we'll use the class Imagify_AS3CF_Attachment everywhere (instead of Imagify_Attachment), and make all the manual optimizations fine.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param  string $context       The context to determine the class name.
	 * @param  int    $attachment_id The attachment ID.
	 * @return string                The new context.
	 */
	public function optimize_attachment_context( $context, $attachment_id ) {
		if ( self::CONTEXT === $context || imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			return self::CONTEXT;
		}
		return $context;
	}

	/**
	 * When getting all unoptimized attachment ids before performing a bulk optimization, download the missing files from S3.
	 *
	 * @since 1.6.7
	 * @author Grégory Viguier
	 *
	 * @param array $ids                An array of attachment IDs.
	 * @param array $results            An array of the data fetched from the database.
	 * @param int   $optimization_level The optimization level that will be used for the optimization.
	 */
	public function maybe_copy_files_from_s3( $ids, $results, $optimization_level ) {
		global $wpdb, $as3cf;

		if ( ! $as3cf->is_plugin_setup() ) {
			return;
		}

		// Remove from the list files that exist.
		$ids = array_flip( $ids );

		foreach ( $ids as $id => $i ) {
			$file_path = get_imagify_attached_file( $results['filenames'][ $id ] );

			/** This filter is documented in inc/functions/process.php. */
			$file_path = apply_filters( 'imagify_file_path', $file_path, $id, 'as3cf_maybe_copy_files_from_s3' );

			if ( ! $file_path || file_exists( $file_path ) ) {
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
		$s3_data = imagify_query_results_combine( $ids, $s3_data, true );

		// Retrieve the missing files from S3.
		$ids = array_flip( $ids );

		foreach ( $s3_data as $id => $s3_object ) {
			$s3_object = maybe_unserialize( $s3_object );
			$file_path = $ids[ $id ];

			$attachment_backup_path        = get_imagify_attachment_backup_path( $file_path );
			$attachment_status             = isset( $results['statuses'][ $id ] )            ? $results['statuses'][ $id ]            : false;
			$attachment_optimization_level = isset( $results['optimization_levels'][ $id ] ) ? $results['optimization_levels'][ $id ] : false;

			// Don't try to re-optimize if there is no backup file.
			if ( 'success' === $attachment_status && $optimization_level !== $attachment_optimization_level && ! file_exists( $attachment_backup_path ) ) {
				unset( $s3_data[ $id ], $ids[ $id ] );
				continue;
			}

			$directory        = dirname( $s3_object['key'] );
			$directory        = '.' === $directory || '' === $directory ? '' : $directory . '/';
			$s3_object['key'] = $directory . wp_basename( $file_path );

			// Retrieve file from S3.
			$as3cf->plugin_compat->copy_s3_file_to_server( $s3_object, $file_path );

			unset( $s3_data[ $id ], $ids[ $id ] );
		}
	}

	/**
	 * Provide the file sizes and the number of thumbnails for files that are only on S3.
	 *
	 * @since  1.6.7
	 * @author Grégory Viguier
	 *
	 * @param  bool  $size_and_count False by default.
	 * @param  int   $image_id       The attachment ID.
	 * @param  array $files          An array of file paths with thumbnail sizes as keys.
	 * @param  array $image_ids      An array of all attachment IDs.
	 * @return bool|array            False by default. Provide an array with the keys 'filesize' (containing the total filesize) and 'thumbnails' (containing the number of thumbnails).
	 */
	function add_stats_for_s3_files( $size_and_count, $image_id, $files, $image_ids ) {
		static $data;

		if ( is_array( $size_and_count ) ) {
			return $size_and_count;
		}

		if ( file_exists( $files['full'] ) ) {
			// If the full size is on the server, that probably means all files are on the server too.
			return $size_and_count;
		}

		if ( ! isset( $data ) ) {
			$data = imagify_get_wpdb_metas( array(
				// Get the filesizes.
				's3_filesize' => 'wpos3_filesize_total',
			), $image_ids );

			$data = array_map( 'absint', $data['s3_filesize'] );
		}

		if ( empty( $data[ $image_id ] ) ) {
			// The file is not on S3.
			return $size_and_count;
		}

		// We can't take the disallowed sizes into account here.
		return array(
			'filesize'   => (int) $data[ $image_id ],
			'thumbnails' => count( $files ) - 1,
		);
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
	 * @author Grégory Viguier
	 * @see    $this->do_async_job()
	 *
	 * @param  array $metadata      An array of attachment meta data.
	 * @param  int   $attachment_id Current attachment ID.
	 * @return array
	 */
	public function store_upload_ids( $metadata, $attachment_id ) {

		if ( imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			$this->uploads[ $attachment_id ] = 1;
		}

		return $metadata;
	}

	/**
	 * After an image (maybe) being sent to S3, launch an async optimization.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 * @see    $this->store_upload_ids()
	 *
	 * @param  array $metadata      An array of attachment meta data.
	 * @param  int   $attachment_id Current attachment ID.
	 * @return array
	 */
	public function do_async_job( $metadata, $attachment_id ) {
		static $auto_optimize;

		$is_new_upload = ! empty( $this->uploads[ $attachment_id ] );
		unset( $this->uploads[ $attachment_id ] );

		if ( ! $metadata || ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			return $metadata;
		}

		if ( ! isset( $auto_optimize ) ) {
			$auto_optimize = get_imagify_option( 'api_key' ) && get_imagify_option( 'auto_optimize' );
		}

		if ( $is_new_upload && ! $auto_optimize ) {
			// It's a new upload and auto-optimization is disabled.
			return $metadata;
		}

		if ( ! $is_new_upload && ! get_post_meta( $attachment_id, '_imagify_data', true ) ) {
			// It's not a new upload and the attachment is not optimized yet.
			return $metadata;
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
	 * @author Grégory Viguier
	 */
	public function optimize() {
		global $as3cf;

		check_ajax_referer( 'imagify_async_optimize_as3cf' );

		if ( empty( $_POST['post_id'] ) || ! current_user_can( 'upload_files' ) ) {
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
		$class_name         = get_imagify_attachment_class_name( self::CONTEXT, $attachment_id, 'as3cf_optimize' );
		$attachment         = new $class_name( $attachment_id );

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


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if the attachment has a supported mime type.
	 *
	 * @since  1.6.6
	 * @since  1.6.8 Deprecated.
	 * @see    imagify_is_attachment_mime_type_supported()
	 * @author Grégory Viguier
	 *
	 * @param  int $post_id The attachment ID.
	 * @return bool
	 */
	public function is_mime_type_supported( $post_id ) {
		_deprecated_function( 'Imagify_AS3CF::is_mime_type_supported()', '1.6.8', 'imagify_is_attachment_mime_type_supported()' );

		return imagify_is_attachment_mime_type_supported( $post_id );
	}
}

/**
 * Returns the main instance of the Imagify_AS3CF class.
 *
 * @since  1.6.6
 * @author Grégory Viguier
 *
 * @return object The Imagify_AS3CF instance.
 */
function imagify_as3cf() {
	return Imagify_AS3CF::get_instance();
}
