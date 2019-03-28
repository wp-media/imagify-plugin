<?php
namespace Imagify\ThirdParty\NGG\Optimization\Process;

use Imagify\Optimization\File;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Optimization class for NextGen Gallery.
 * This class constructor accepts:
 * - A NGG image ID (int).
 * - A \nggImage object.
 * - A \nggdb object.
 * - An anonym object containing a pid property (and everything else).
 * - A \Imagify\Media\MediaInterface object.
 *
 * @since  1.9
 * @see    Imagify\ThirdParty\NGG\Media\NGG
 * @author Grégory Viguier
 */
class NGG extends \Imagify\Optimization\Process\AbstractProcess {

	/**
	 * Tell if the current can optimize/restore/etc.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $describer Capacity describer. Possible values are 'bulk-optimize', 'manual-optimize', 'auto-optimize', 'bulk-restore', and 'manual-restore'.
	 * @return bool
	 */
	public function current_user_can( $describer ) {
		static $user_can_per_gallery = [];

		$describer = $this->normalize_capacity_describer( $describer );

		if ( ! $describer ) {
			return false;
		}

		if ( 'manual-optimize' !== $describer ) {
			return imagify_get_capacity( $describer );
		}

		$image = $this->get_media()->get_ngg_image();

		if ( isset( $user_can_per_gallery[ $image->galleryid ] ) ) {
			return $user_can_per_gallery[ $image->galleryid ];
		}

		$gallery_mapper = \C_Gallery_Mapper::get_instance();
		$gallery        = $gallery_mapper->find( $image->galleryid, false );

		if ( get_current_user_id() === $gallery->author || current_user_can( 'NextGEN Manage others gallery' ) ) {
			// The user created this gallery or can edit others galleries.
			$user_can_per_gallery[ $image->galleryid ] = true;
			return $user_can_per_gallery[ $image->galleryid ];
		}

		// The user can't edit this gallery.
		$user_can_per_gallery[ $image->galleryid ] = false;
		return $user_can_per_gallery[ $image->galleryid ];
	}

	/**
	 * Tell if a size can be resized.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $size The size name.
	 * @param  File   $file A File instance.
	 * @return bool
	 */
	protected function can_resize( $size, $file ) {
		return false;
	}

	/**
	 * Tell if a size can be backuped.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $size The size name.
	 * @return bool
	 */
	protected function can_backup( $size ) {
		return 'full' === $size;
	}

	/**
	 * Tell if a size should keep exif.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $size The size name.
	 * @return bool
	 */
	protected function can_keep_exif( $size ) {
		return 'full' === $size;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** MISSING THUMBNAILS ====================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the sizes for this media that have not get through optimization.
	 * Since this context doesn't handle this feature, this will always return an empty array, unless an error is triggered.
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
	 * Since this context doesn't handle this feature, this will always return a \WP_Error object.
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
