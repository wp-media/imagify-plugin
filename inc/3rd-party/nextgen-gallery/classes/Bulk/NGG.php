<?php
namespace Imagify\ThirdParty\NGG\Bulk;

use \Imagify\ThirdParty\NGG\DB;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class to use for bulk for NextGen Gallery.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class NGG extends \Imagify\Bulk\AbstractBulk {

	/**
	 * Context "short name".
	 *
	 * @var    string
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $context = 'ngg';

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
		global $wpdb;

		@set_time_limit( 0 );

		$storage   = \C_Gallery_Storage::get_instance();
		$ngg_table = $wpdb->prefix . 'ngg_pictures';
		$data      = [];
		$images    = $wpdb->get_results( $wpdb->prepare( // WPCS: unprepared SQL ok.
			"
			SELECT picture.pid as id, picture.filename, idata.optimization_level, idata.status, idata.data
			FROM $ngg_table as picture
			LEFT JOIN $wpdb->ngg_imagify_data as idata
			ON picture.pid = idata.pid
			WHERE idata.pid IS NULL
				OR idata.optimization_level != %d
				OR idata.status = 'error'
			LIMIT %d",
			$optimization_level,
			imagify_get_unoptimized_attachment_limit()
		), ARRAY_A );

		if ( ! $images ) {
			return [];
		}

		foreach ( $images as $image ) {
			$id        = absint( $image['id'] );
			$file_path = $storage->get_image_abspath( $id );

			if ( ! $file_path || ! $this->filesystem->exists( $file_path ) ) {
				continue;
			}

			$attachment_data  = maybe_unserialize( $image['data'] );
			$attachment_error = '';

			if ( isset( $attachment_data['sizes']['full']['error'] ) ) {
				$attachment_error = $attachment_data['sizes']['full']['error'];
			}

			$attachment_error              = trim( $attachment_error );
			$attachment_status             = $image['status'];
			$attachment_optimization_level = $image['optimization_level'];
			$attachment_backup_path        = get_imagify_ngg_attachment_backup_path( $file_path );

			// Don't try to re-optimize if the optimization level is still the same.
			if ( $optimization_level === $attachment_optimization_level && is_string( $attachment_error ) ) {
				continue;
			}

			// Don't try to re-optimize if there is no backup file.
			if ( 'success' === $attachment_status && $optimization_level !== $attachment_optimization_level && ! $this->filesystem->exists( $attachment_backup_path ) ) {
				continue;
			}

			// Don't try to re-optimize images already compressed.
			if ( 'already_optimized' === $attachment_status && $attachment_optimization_level >= $optimization_level ) {
				continue;
			}

			// Don't try to re-optimize images with an empty error message.
			if ( 'error' === $attachment_status && empty( $attachment_error ) ) {
				continue;
			}

			$data[ '_' . $id ] = esc_url( $storage->get_image_url( $id ) );
		} // End foreach().

		return $data;
	}

	/**
	 * Get ids of all optimized media without webp versions.
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

		$storage     = \C_Gallery_Storage::get_instance();
		$ngg_table   = $wpdb->prefix . 'ngg_pictures';
		$data_table  = DB::get_instance()->get_table_name();
		$webp_suffix = constant( imagify_get_optimization_process_class_name( 'ngg' ) . '::WEBP_SUFFIX' );
		$files       = $wpdb->get_col( $wpdb->prepare( // WPCS: unprepared SQL ok.
			"
			SELECT ngg.pid
			FROM $ngg_table as ngg
			INNER JOIN $data_table AS data
				ON ( ngg.pid = data.pid )
			WHERE
				data.status = 'success'
				AND data.data NOT LIKE %s
			ORDER BY ngg.pid DESC",
			'%' . $wpdb->esc_like( $webp_suffix . '";a:4:{s:7:"success";b:1;' ) . '%'
		) );

		$wpdb->flush();
		unset( $ngg_table, $data_table, $webp_suffix );

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

		foreach ( $files as $file_id ) {
			$file_id   = absint( $file_id );
			$file_path = $storage->get_image_abspath( $file_id );

			if ( ! $file_path ) {
				// Problem.
				$data['errors']['no_file_path'][] = $file_id;
				continue;
			}

			$backup_path = get_imagify_ngg_attachment_backup_path( $file_path );

			if ( ! $this->filesystem->exists( $backup_path ) ) {
				// No backup, no webp.
				$data['errors']['no_backup'][] = $file_id;
				continue;
			}

			$data['ids'][] = $file_id;
		} // End foreach().

		return $data;
	}

	/**
	 * Tell if there are optimized media without webp versions.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int The number of media.
	 */
	public function has_optimized_media_without_webp() {
		global $wpdb;

		$ngg_table   = $wpdb->prefix . 'ngg_pictures';
		$data_table  = DB::get_instance()->get_table_name();
		$webp_suffix = constant( imagify_get_optimization_process_class_name( 'ngg' ) . '::WEBP_SUFFIX' );

		return (int) $wpdb->get_var( $wpdb->prepare( // WPCS: unprepared SQL ok.
			"
			SELECT COUNT(ngg.pid)
			FROM $ngg_table as ngg
			INNER JOIN $data_table AS data
				ON ( ngg.pid = data.pid )
			WHERE
				data.status = 'success'
				AND data.data NOT LIKE %s
			ORDER BY ngg.pid DESC",
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
		$total_saving_data = imagify_count_saving_data();
		$data              = [
			'count-optimized' => imagify_ngg_count_optimized_attachments(),
			'count-errors'    => imagify_ngg_count_error_attachments(),
			'optimized-size'  => $total_saving_data['optimized_size'],
			'original-size'   => $total_saving_data['original_size'],
			'errors_url'      => get_imagify_admin_url( 'folder-errors', $this->context ),
		];

		return $this->format_context_data( $data );
	}
}
