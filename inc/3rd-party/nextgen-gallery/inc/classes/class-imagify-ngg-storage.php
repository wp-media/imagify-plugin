<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Imagify NextGen Gallery storage class.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
class Imagify_NGG_Storage extends Mixin {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.2';

	/**
	 * Delete a gallery AND all the pictures associated to this gallery!
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @param  int|object $gallery A gallery ID or object.
	 * @return bool                Whetther tha gallery was been deleted or not.
	 */
	public function delete_gallery( $gallery ) {
		$gallery_id = is_numeric( $gallery ) ? $gallery : $gallery->{$gallery->id_field};
		$images_id  = nggdb::get_ids_from_gallery( $gallery_id );

		foreach ( $images_id as $pid ) {
			Imagify_NGG_DB::get_instance()->delete( $pid );
		}

		return $this->call_parent( 'delete_gallery', $gallery );
	}

	/**
	 * Generates a specific size for an image.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @param  int|object $image         An image ID or NGG object.
	 * @param  string     $size          An image size.
	 * @param  array      $params        An array of parameters.
	 * @param  bool       $skip_defaults Whatever NGG does with default settings.
	 * @return bool|object               An object on success. False on failure.
	 */
	public function generate_image_size( $image, $size, $params = null, $skip_defaults = false ) {
		// $image could be an object or an (int) image ID.
		if ( is_numeric( $image ) ) {
			$image = $this->object->_image_mapper->find( $image );
		}

		// If a user adds a watermark, rotates or resizes an image, we restore it.
		// TO DO - waiting for a hook to be able to re-optimize the original size after restoring.
		if ( isset( $image->pid ) && ( true === $params['watermark'] || ( isset( $params['rotation'] ) || isset( $params['flip'] ) ) || ( ! empty( $params['width'] ) || ! empty( $params['height'] ) ) ) ) {
			$attachment = new Imagify_NGG_Attachment( $image->pid );

			if ( $attachment->is_optimized() ) {
				Imagify_NGG_DB::get_instance()->delete( $image->pid );
			}
		}

		return $this->call_parent( 'generate_image_size', $image, $size, $params, $skip_defaults );
	}

	/**
	 * Recover image from backup.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @param  int|object $image An image ID or NGG object.
	 * @return string|bool       Result code on success. False on failure.
	 */
	public function recover_image( $image ) {
		// $image could be an object or an (int) image ID.
		if ( is_numeric( $image ) ) {
			$image = $this->object->_image_mapper->find( $image );
		}

		if ( ! $image ) {
			return false;
		}

		// Remove Imagify data.
		if ( isset( $image->pid ) ) {
			Imagify_NGG_DB::get_instance()->delete( $image->pid );
		}

		return $this->call_parent( 'recover_image', $image );
	}
}
