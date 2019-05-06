<?php
namespace Imagify\Optimization\Process;

use Imagify\Optimization\File;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Optimization class for the attachments in the WP library.
 * This class constructor accepts:
 * - A post ID (int).
 * - A \WP_Post object.
 * - A \Imagify\Media\MediaInterface object.
 * - A \Imagify\Media\DataInterface object.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class WP extends AbstractProcess {

	/** ----------------------------------------------------------------------------------------- */
	/** MISSING THUMBNAILS ====================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the sizes for this media that have not get through optimization.
	 * No sizes are returned if the file is not optimized, has no backup, or is not an image.
	 * The 'full' size os never returned.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array|WP_Error {
	 *     A WP_Error object on failure.
	 *     An array of data for the thumbnail sizes on success.
	 *     Size names are used as array keys.
	 *
	 *     @type int    $width  The image width.
	 *     @type int    $height The image height.
	 *     @type bool   $crop   True to crop, false to resize.
	 *     @type string $name   The size name.
	 *     @type string $file   The name the thumbnail "should" have.
	 * }
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

		// Compare registered sizes and optimized sizes.
		$context_sizes   = $media->get_context_instance()->get_thumbnail_sizes();
		$optimized_sizes = $data->get_optimization_data();
		$missing_sizes   = array_diff_key( $context_sizes, $optimized_sizes['sizes'] );

		if ( ! $missing_sizes ) {
			// We have everything we need.
			return [];
		}

		$media_sizes = $media->get_media_files();
		$full_size   = $media_sizes['full'];

		if ( ! $full_size['path'] || ! $full_size['width'] || ! $full_size['height'] ) {
			return [];
		}

		$file_name = $this->filesystem->path_info( $full_size['path'] );
		$file_name = $file_name['file_base'] . '-{%suffix%}.' . $file_name['extension'];

		// Test if the missing sizes are needed.
		foreach ( $missing_sizes as $size_name => $size_data ) {
			if ( $full_size['width'] === $size_data['width'] && $full_size['height'] === $size_data['height'] ) {
				// Same dimensions as the full size.
				unset( $missing_sizes[ $size_name ] );
				continue;
			}

			if ( ! empty( $media_sizes[ $size_name ]['disabled'] ) ) {
				// This size must not be optimized.
				unset( $missing_sizes[ $size_name ] );
				continue;
			}

			$resize_result = image_resize_dimensions( $full_size['width'], $full_size['height'], $size_data['width'], $size_data['height'], $size_data['crop'] );

			if ( ! $resize_result ) {
				// This thumbnail is not needed, it is smaller than this size.
				unset( $missing_sizes[ $size_name ] );
				continue;
			}

			// Provide what should be the file name.
			list( , , , , $new_width, $new_height ) = $resize_result;
			$missing_sizes[ $size_name ]['file']    = str_replace( '{%suffix%}', "{$new_width}x{$new_height}", $file_name );
		}

		return $missing_sizes;
	}

	/**
	 * Optimize missing thumbnail sizes.
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

		$missing_sizes = $this->get_missing_sizes();

		if ( ! $missing_sizes ) {
			return new \WP_Error( 'no_sizes', __( 'No thumbnails seem to be missing.', 'imagify' ) );
		}

		if ( is_wp_error( $missing_sizes ) ) {
			return $missing_sizes;
		}

		if ( $this->is_locked() ) {
			return new \WP_Error( 'media_locked', __( 'This media is already being processed.', 'imagify' ) );
		}

		$this->lock();

		// Create the missing thumbnails.
		$sizes = $this->create_missing_thumbnails( $missing_sizes );

		if ( ! $sizes ) {
			$this->unlock();
			return new \WP_Error( 'thumbnail_creation_failed', __( 'The thumbnails failed to be created.', 'imagify' ) );
		}

		$optimization_level = $this->get_data()->get_optimization_level();

		if ( false === $optimization_level ) {
			$this->unlock();
			return new \WP_Error( 'optimization_level_not_set', __( 'The optimization level of this media seems to have disappear from the database. You should restore this media and then launch a new optimization.', 'imagify' ) );
		}

		$args = [
			'hook_suffix' => 'optimize_missing_thumbnails',
			'locked'      => true,
		];

		// Optimize.
		return $this->optimize_sizes( array_keys( $sizes ), $optimization_level, $args );
	}

	/**
	 * Create all missing thumbnails if they don't exist and update the attachment metadata.
	 *
	 * @since  1.9
	 * @access protected
	 * @see    $this->get_missing_sizes()
	 * @author Grégory Viguier
	 *
	 * @param  array $missing_sizes array {
	 *     An array of data for the thumbnail sizes on success.
	 *     Size names are used as array keys.
	 *
	 *     @type int    $width  The image width.
	 *     @type int    $height The image height.
	 *     @type bool   $crop   True to crop, false to resize.
	 *     @type string $name   The size name.
	 *     @type string $file   The name the thumbnail "should" have.
	 * }
	 * @return array {
	 *     An array of thumbnail data (those without errors):
	 *
	 *     @type string $file      File name.
	 *     @type int    $width     The image width.
	 *     @type int    $height    The image height.
	 *     @type string $mime-type The mime type.
	 * }
	 */
	protected function create_missing_thumbnails( $missing_sizes ) {
		if ( ! $missing_sizes ) {
			return [];
		}

		$media             = $this->get_media();
		$media_id          = $media->get_id();
		$metadata          = wp_get_attachment_metadata( $media_id );
		$metadata['sizes'] = ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ? $metadata['sizes'] : [];

		$destination_dir = $this->filesystem->dir_path( $media->get_raw_original_path() );
		$file            = new File( $media->get_backup_path() );
		$without_errors  = [];
		$has_new_data    = false;

		// Create the missing thumbnails.
		foreach ( $missing_sizes as $size_name => $thumbnail_data ) {
			// The path to the destination file.
			$thumbnail_data['path'] = $destination_dir . $thumbnail_data['file'];

			if ( ! $this->filesystem->exists( $thumbnail_data['path'] ) ) {
				$result = $file->create_thumbnail( $thumbnail_data );

				if ( is_array( $result ) ) {
					// New file.
					$metadata['sizes'][ $size_name ] = $result;
					$has_new_data                    = true;
				}
			} else {
				$result = true;
			}

			if ( ! empty( $metadata['sizes'][ $size_name ] ) && ! is_wp_error( $result ) ) {
				// Not an error.
				$without_errors[ $size_name ] = $metadata['sizes'][ $size_name ];
			}
		}

		// Save the new data into the attachment metadata.
		if ( $has_new_data ) {
			/**
			 * Here we don't use wp_update_attachment_metadata() to prevent triggering unwanted hooks.
			 */
			update_post_meta( $media_id, '_wp_attachment_metadata', $metadata );
		}

		return $without_errors;
	}
}
