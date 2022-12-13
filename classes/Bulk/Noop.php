<?php
namespace Imagify\Bulk;

/**
 * Falback class for bulk.
 *
 * @since 1.9
 */
class Noop extends AbstractBulk {
	/**
	 * Get all unoptimized media ids.
	 *
	 * @since 1.9
	 *
	 * @param  int $optimization_level The optimization level.
	 * @return array                   A list of unoptimized media. Array keys are media IDs prefixed with an underscore character, array values are the main file’s URL.
	 */
	public function get_unoptimized_media_ids( $optimization_level ) {
		return [];
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
		return [
			'ids'    => [],
			'errors' => [
				'no_file_path' => [],
				'no_backup'    => [],
			],
		];
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
			'count-optimized' => 0,
			'count-errors'    => 0,
			'optimized-size'  => 0,
			'original-size'   => 0,
			'errors_url'      => get_imagify_admin_url( 'folder-errors', 'noop' ),
		];

		return $this->format_context_data( $data );
	}
}
