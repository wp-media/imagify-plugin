<?php
namespace Imagify\Bulk;

use Imagify_Custom_Folders;
use Imagify_Files_Scan;
use Imagify_Files_DB;
use Imagify_Folders_DB;
use Imagify_DB;
use Imagify_Files_Stats;

/**
 * Class to use for bulk for custom folders.
 *
 * @since 1.9
 */
class CustomFolders extends AbstractBulk {
	/**
	 * Context "short name".
	 *
	 * @var string
	 * @since 1.9
	 */
	protected $context = 'custom-folders';

	/**
	 * Get all unoptimized media ids.
	 *
	 * @since 1.9
	 *
	 * @param  int $optimization_level The optimization level.
	 *
	 * @return array A list of unoptimized media IDs.
	 */
	public function get_unoptimized_media_ids( $optimization_level ) {
		$this->set_no_time_limit();

		/**
		 * Get the folders from DB.
		 */
		$folders = Imagify_Custom_Folders::get_folders( [
			'active' => true,
		] );

		if ( ! $folders ) {
			return [];
		}

		/**
		 * Fires before getting file IDs.
		 *
		 * @since 1.7
		 *
		 * @param array $folders            An array of folders data.
		 * @param int   $optimization_level The optimization level that will be used for the optimization.
		 */
		do_action( 'imagify_bulk_optimize_files_before_get_files', $folders, $optimization_level );

		/**
		 * Get the files from DB, and from the folders.
		 */
		$files = Imagify_Custom_Folders::get_files_from_folders( $folders, [
			'optimization_level' => $optimization_level,
		] );

		if ( ! $files ) {
			return [];
		}

		foreach ( $files as $k => $file ) {
			$files[ $k ] = $file['file_id'];
		}

		return $files;
	}

	/**
	 * Get ids of all optimized media without WebP versions.
	 *
	 * @since 1.9
	 * @since 1.9.5 The method doesn't return the IDs directly anymore.
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

		$this->set_no_time_limit();

		$files_table   = Imagify_Files_DB::get_instance()->get_table_name();
		$folders_table = Imagify_Folders_DB::get_instance()->get_table_name();
		$mime_types    = Imagify_DB::get_mime_types( 'image' );
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

			$file_path   = Imagify_Files_Scan::remove_placeholder( $file->path );
			$backup_path = Imagify_Custom_Folders::get_file_backup_path( $file_path );

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
	 * Get the context data.
	 *
	 * @since 1.9
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
		$data = [
			'count-optimized' => Imagify_Files_Stats::count_optimized_files(),
			'count-errors'    => Imagify_Files_Stats::count_error_files(),
			'optimized-size'  => Imagify_Files_Stats::get_optimized_size(),
			'original-size'   => Imagify_Files_Stats::get_original_size(),
			'errors_url'      => get_imagify_admin_url( 'folder-errors', $this->context ),
		];

		return $this->format_context_data( $data );
	}
}
