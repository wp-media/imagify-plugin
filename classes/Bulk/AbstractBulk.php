<?php
namespace Imagify\Bulk;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Abstract class to use for bulk.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
abstract class AbstractBulk implements BulkInterface {

	/**
	 * Filesystem object.
	 *
	 * @var    \Imagify_Filesystem
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

	/**
	 * The constructor.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function __construct() {
		$this->filesystem = \Imagify_Filesystem::get_instance();
	}

	/**
	 * Format context data (stats).
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
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
		/* translators: %s is a formatted number, dont use %d. */
		$data['count-optimized'] = sprintf( _n( '%s Media File Optimized', '%s Media Files Optimized', $data['count-optimized'], 'imagify' ), '<span>' . number_format_i18n( $data['count-optimized'] ) . '</span>' );

		if ( $data['count-errors'] ) {
			/* translators: %s is a formatted number, dont use %d. */
			$data['count-errors']  = sprintf( _n( '%s Error', '%s Errors', $data['count-errors'], 'imagify' ), '<span>' . number_format_i18n( $data['count-errors'] ) . '</span>' );
			$data['count-errors'] .= ' <a href="' . esc_url( $data['errors_url'] ) . '">' . __( 'View Errors', 'imagify' ) . '</a>';
		} else {
			$data['count-errors'] = '';
		}

		if ( $data['optimized-size'] ) {
			$data['optimized-size'] = '<span class="imagify-cell-label">' . __( 'Optimized Filesize', 'imagify' ) . '</span> ' . imagify_size_format( $data['optimized-size'], 2 );
		} else {
			$data['optimized'] = '';
		}

		if ( $data['original-size'] ) {
			$data['original-size'] = '<span class="imagify-cell-label">' . __( 'Original Filesize', 'imagify' ) . '</span> ' . imagify_size_format( $data['original-size'], 2 );
		} else {
			$data['original-size'] = '';
		}

		unset( $data['errors_url'] );

		return $data;
	}
}
