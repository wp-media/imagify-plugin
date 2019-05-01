<?php
namespace Imagify\Optimization\Process;

use Imagify\Optimization\File;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Optimization class for the custom folders.
 * This class constructor accepts:
 * - A post ID (int).
 * - An array of data coming from the files DB table /!\
 * - An object of data coming from the files DB table /!\
 * - A \Imagify\Media\MediaInterface object.
 * - A \Imagify\Media\DataInterface object.
 *
 * @since  1.9
 * @see    Imagify\Media\CustomFolders
 * @author Grégory Viguier
 */
class CustomFolders extends AbstractProcess {

	/**
	 * Restore the thumbnails.
	 * This context has no thumbnails.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|WP_Error True on success. A \WP_Error instance on failure.
	 */
	protected function restore_thumbnails() {
		return true;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** MISSING THUMBNAILS ====================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the sizes for this media that have not get through optimization.
	 * Since this context has no thumbnails, this will always return an empty array, unless an error is triggered.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array|WP_Error A WP_Error object on failure. An empty array on success: this context has no thumbnails.
	 *                        The tests are kept for consistency.
	 */
	public function get_missing_sizes() {
		// The media must have been optimized once and have a backup.
		if ( ! $this->is_valid() ) {
			return new \WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media = $this->get_media();

		if ( ! $media->is_supported() ) {
			return new \WP_Error( 'media_not_supported', __( 'This media is not supported.', 'imagify' ) );
		}

		$data = $this->get_data();

		if ( ! $data->is_optimized() ) {
			return new \WP_Error( 'media_not_optimized', __( 'This media is not optimized yet.', 'imagify' ) );
		}

		if ( ! $media->has_backup() ) {
			return new \WP_Error( 'no_backup', __( 'This file has no backup file.', 'imagify' ) );
		}

		if ( ! $media->is_image() ) {
			return new \WP_Error( 'media_not_an_image', __( 'This media is not an image.', 'imagify' ) );
		}

		return [];
	}

	/**
	 * Optimize missing thumbnail sizes.
	 * Since this context has no thumbnails, this will always return a \WP_Error object.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|WP_Error True if successfully launched. A \WP_Error instance on failure.
	 */
	public function optimize_missing_thumbnails() {
		if ( ! $this->is_valid() ) {
			return new \WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		if ( ! $this->get_media()->is_supported() ) {
			return new \WP_Error( 'media_not_supported', __( 'This media is not supported.', 'imagify' ) );
		}

		return new \WP_Error( 'no_sizes', __( 'No thumbnails seem to be missing.', 'imagify' ) );
	}
}
