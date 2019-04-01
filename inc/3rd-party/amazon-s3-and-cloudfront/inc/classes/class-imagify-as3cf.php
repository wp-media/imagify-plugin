<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Imagify WP Offload S3 class.
 *
 * @since  1.6.6
 * @author Grégory Viguier
 */
class Imagify_AS3CF extends Imagify_AS3CF_Deprecated {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.1';

	/**
	 * Context used with get_imagify_attachment().
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
	protected function __construct() {
		$this->filesystem = Imagify_Filesystem::get_instance();
	}

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
		 * Redirections.
		 */
		add_filter( 'imagify_redirect_to', array( $this, 'redirect_referrer' ) );

		/**
		 * Bulk optimization.
		 */
		add_action( 'imagify_bulk_optimize_before_file_existence_tests', array( $this, 'maybe_copy_files_from_s3' ), 8, 3 );

		/**
		 * Stats.
		 */
		add_filter( 'imagify_total_attachment_filesize', array( $this, 'add_stats_for_s3_files' ), 8, 4 );
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
		if ( self::CONTEXT === $context || ( 'wp' === $context && imagify_is_attachment_mime_type_supported( $attachment_id ) ) ) {
			return self::CONTEXT;
		}
		return $context;
	}

	/**
	 * After a non-ajax optimization, remove some unnecessary arguments from the referrer used for the redirection.
	 * Those arguments don't break anything, they're just not relevant and display obsolete admin notices.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @param  string $redirect The URL to redirect to.
	 * @return string
	 */
	public function redirect_referrer( $redirect ) {
		return remove_query_arg( array( 'as3cfpro-action', 'as3cf_id', 'errors', 'count' ), $redirect );
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

			/** This filter is documented in inc/functions/process.php. */
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
	public function add_stats_for_s3_files( $size_and_count, $image_id, $files, $image_ids ) {
		static $data;

		if ( is_array( $size_and_count ) ) {
			return $size_and_count;
		}

		if ( $this->filesystem->exists( $files['full'] ) ) {
			// If the full size is on the server, that probably means all files are on the server too.
			return $size_and_count;
		}

		if ( ! isset( $data ) ) {
			$data = Imagify_DB::get_metas( array(
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
}
