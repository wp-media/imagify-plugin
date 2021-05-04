<?php
namespace Imagify\Bulk;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class to use for bulk for custom folders.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class CustomFolders extends AbstractBulk {

	/**
	 * Context "short name".
	 *
	 * @var    string
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $context = 'custom-folders';

	/**
	 * Get all unoptimized media ids.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int $optimization_level The optimization level.
	 * @return array                   A list of unoptimized media. Array keys are media IDs prefixed with an underscore character, array values are the main file’s URL.
	 */
	public function get_unoptimized_media_ids( $optimization_level ) {
		@set_time_limit( 0 );

		/**
		 * Get the folders from DB.
		 */
		$folders = \Imagify_Custom_Folders::get_folders( array(
			'active' => true,
		) );

		if ( ! $folders ) {
			return [];
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
		$files = \Imagify_Custom_Folders::get_files_from_folders( $folders, [
			'optimization_level' => $optimization_level,
		] );

		if ( ! $files ) {
			return [];
		}

		// We need to output file URLs.
		foreach ( $files as $k => $file ) {
			$files[ $k ] = esc_url( \Imagify_Files_Scan::remove_placeholder( $file['path'], 'url' ) );
		}

		return $files;
	}

	/**
	 * Get ids of all optimized media without WebP versions.
	 *
	 * @since  1.9
	 * @since  1.9.5 The method doesn't return the IDs directly anymore.
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array {
	 *     @type array $ids    A list of media IDs.
	 *     @type array $errors {
	 *         @type array $no_file_path A list of media IDs.
	 *         @type array $no_backup    A list of media IDs.
	 *     }
	 * }
	 */
	public function get_optimized_media_ids_without_webp() {
		global $wpdb;

		@set_time_limit( 0 );

		$files_table   = \Imagify_Files_DB::get_instance()->get_table_name();
		$folders_table = \Imagify_Folders_DB::get_instance()->get_table_name();
		$mime_types    = \Imagify_DB::get_mime_types( 'image' );
		$webp_suffix   = constant( imagify_get_optimization_process_class_name( 'custom-folders' ) . '::WEBP_SUFFIX' );
		$files         = $wpdb->get_results( $wpdb->prepare( // WPCS: unprepared SQL ok.
			"
			SELECT fi.file_id, fi.path
			FROM $files_table as fi
			INNER JOIN $folders_table AS fo
				ON ( fi.folder_id = fo.folder_id )
			WHERE
				fi.mime_type IN ( $mime_types )
				AND ( fi.status = 'success' OR fi.status = 'already_optimized' )
				AND ( fi.data NOT LIKE %s OR fi.data IS NULL )
			ORDER BY fi.file_id DESC",
			'%' . $wpdb->esc_like( $webp_suffix . '";a:4:{s:7:"success";b:1;' ) . '%'
		) );

		$wpdb->flush();
		unset( $mime_types, $files_table, $folders_table, $webp_suffix );

		$data = [
			'ids'    => [],
			'errors' => [
				'no_file_path' => [],
				'no_backup'    => [],
			],
		];

		if ( ! $files ) {
			return $data;
		}

		foreach ( $files as $file ) {
			$file_id = absint( $file->file_id );

			if ( empty( $file->path ) ) {
				// Problem.
				$data['errors']['no_file_path'][] = $file_id;
				continue;
			}

			$file_path   = \Imagify_Files_Scan::remove_placeholder( $file->path );
			$backup_path = \Imagify_Custom_Folders::get_file_backup_path( $file_path );

			if ( ! $this->filesystem->exists( $backup_path ) ) {
				// No backup, no WebP.
				$data['errors']['no_backup'][] = $file_id;
				continue;
			}

			$data['ids'][] = $file_id;
		} // End foreach().

		return $data;
	}

	/**
	 * Tell if there are optimized media without WebP versions.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int The number of media.
	 */
	public function has_optimized_media_without_webp() {
		global $wpdb;

		$files_table   = \Imagify_Files_DB::get_instance()->get_table_name();
		$folders_table = \Imagify_Folders_DB::get_instance()->get_table_name();
		$mime_types    = \Imagify_DB::get_mime_types( 'image' );
		$webp_suffix   = constant( imagify_get_optimization_process_class_name( 'custom-folders' ) . '::WEBP_SUFFIX' );

		return (int) $wpdb->get_var( $wpdb->prepare( // WPCS: unprepared SQL ok.
			"
			SELECT COUNT(fi.file_id)
			FROM $files_table as fi
			INNER JOIN $folders_table AS fo
				ON ( fi.folder_id = fo.folder_id )
			WHERE
				fi.mime_type IN ( $mime_types )
				AND ( fi.status = 'success' OR fi.status = 'already_optimized' )
				AND ( fi.data NOT LIKE %s OR fi.data IS NULL )",
			'%' . $wpdb->esc_like( $webp_suffix . '";a:4:{s:7:"success";b:1;' ) . '%'
		) );
	}

	/**
	 * Get the context data.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array {
	 *     The formated data.
	 *
	 *     @type string $count-optimized Number of media optimized.
	 *     @type string $count-errors    Number of media having an optimization error, with a link to the page listing the optimization errors.
	 *     @type string $optimized-size  Optimized filesize.
	 *     @type string $original-size   Original filesize.
	 * }
	 */
	public function get_context_data() {
		$data = array(
			'count-optimized' => \Imagify_Files_Stats::count_optimized_files(),
			'count-errors'    => \Imagify_Files_Stats::count_error_files(),
			'optimized-size'  => \Imagify_Files_Stats::get_optimized_size(),
			'original-size'   => \Imagify_Files_Stats::get_original_size(),
			'errors_url'      => get_imagify_admin_url( 'folder-errors', $this->context ),
		);

		return $this->format_context_data( $data );
	}
}
