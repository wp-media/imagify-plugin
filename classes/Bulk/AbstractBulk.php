<?php
namespace Imagify\Bulk;

use Imagify_Filesystem;

/**
 * Abstract class to use for bulk.
 *
 * @since 1.9
 */
abstract class AbstractBulk implements BulkInterface {
	/**
	 * Filesystem object.
	 *
	 * @var Imagify_Filesystem
	 * @since 1.9
	 */
	protected $filesystem;

	/**
	 * The constructor.
	 *
	 * @since 1.9
	 */
	public function __construct() {
		$this->filesystem = Imagify_Filesystem::get_instance();
	}

	/**
	 * Format context data (stats).
	 *
	 * @since 1.9
	 *
	 * @param  array $data {
	 *     The data to format.
	 *
	 *     @type int    $count-optimized Number of media optimized.
	 *     @type int    $count-errors    Number of media having an optimization error.
	 *     @type int    $optimized-size  Optimized filesize.
	 *     @type int    $original-size   Original filesize.
	 *     @type string $errors_url      URL to the page listing the optimization errors.
	 * }
	 * @return array {
	 *     The formated data.
	 *
	 *     @type string $count-optimized Number of media optimized.
	 *     @type string $count-errors    Number of media having an optimization error, with a link to the page listing the optimization errors.
	 *     @type string $optimized-size  Optimized filesize.
	 *     @type string $original-size   Original filesize.
	 * }
	 */
	protected function format_context_data( $data ) {
		$defaults = [
			'count-optimized' => '',
			'count-errors'    => '',
			'optimized-size'  => '',
			'original-size'   => '',
		];

		$data = wp_parse_args( $data, $defaults );

		$data = array_map( function( $item ) {
			return empty( $item ) ? '' : $item;
		}, $data );

		if ( ! empty( $data['count-optimized'] ) ) {
			// translators: %s is a formatted number, dont use %d.
			$data['count-optimized'] = sprintf( _n( '%s Media File Optimized', '%s Media Files Optimized', $data['count-optimized'], 'imagify' ), '<span>' . number_format_i18n( $data['count-optimized'] ) . '</span>' );

		}

		if ( ! empty( $data['count-errors'] ) ) {
			/* translators: %s is a formatted number, dont use %d. */
			$data['count-errors']  = sprintf( _n( '%s Error', '%s Errors', $data['count-errors'], 'imagify' ), '<span>' . number_format_i18n( $data['count-errors'] ) . '</span>' );
			$data['count-errors'] .= ' <a href="' . esc_url( $data['errors_url'] ) . '">' . __( 'View Errors', 'imagify' ) . '</a>';
		}

		if ( ! empty( $data['optimized-size'] ) ) {
			$data['optimized-size'] = '<span class="imagify-cell-label">' . __( 'Optimized Filesize', 'imagify' ) . '</span> ' . imagify_size_format( $data['optimized-size'], 2 );
		}

		if ( ! empty( $data['original-size'] ) ) {
			$data['original-size'] = '<span class="imagify-cell-label">' . __( 'Original Filesize', 'imagify' ) . '</span> ' . imagify_size_format( $data['original-size'], 2 );
		}

		unset( $data['errors_url'] );

		return $data;
	}

	/**
	 * Attempts to set no limit to the PHP timeout for time intensive processes.
	 *
	 * @return void
	 */
	protected function set_no_time_limit() {
		if (
			function_exists( 'set_time_limit' )
			&&
			false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' )
			&& ! ini_get( 'safe_mode' ) // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
		) {
			@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}

	/**
	 * Tell if there are optimized media without WebP versions.
	 *
	 * @since 1.9
	 *
	 * @return int The number of media.
	 */
	public function has_optimized_media_without_webp() {
		return count( $this->get_optimized_media_ids_without_webp()['ids'] );
	}
}
