<?php
namespace Imagify\Context;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Context class used for the WP media library.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class WP extends AbstractContext {
	use \Imagify\Traits\FakeSingletonTrait;

	/**
	 * Context "short name".
	 *
	 * @var    string
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $context = 'wp';

	/**
	 * Get the thumbnail sizes for this context, except the full size.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array {
	 *     Data for the currently registered thumbnail sizes.
	 *     Size names are used as array keys.
	 *
	 *     @type int    $width  The image width.
	 *     @type int    $height The image height.
	 *     @type bool   $crop   True to crop, false to resize.
	 *     @type string $name   The size name.
	 * }
	 */
	public function get_thumbnail_sizes() {
		if ( isset( $this->thumbnail_sizes ) ) {
			return $this->thumbnail_sizes;
		}

		$this->thumbnail_sizes = get_imagify_thumbnail_sizes();

		return $this->thumbnail_sizes;
	}

	/**
	 * Tell if the optimization process is allowed resize in this context.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function can_resize() {
		if ( isset( $this->can_resize ) ) {
			return $this->can_resize;
		}

		$this->can_resize = get_imagify_option( 'resize_larger' ) && get_imagify_option( 'resize_larger_w' ) > 0;

		return $this->can_resize;
	}

	/**
	 * Tell if the optimization process is allowed to backup in this context.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function can_backup() {
		if ( isset( $this->can_backup ) ) {
			return $this->can_backup;
		}

		$this->can_backup = get_imagify_option( 'backup' );

		return $this->can_backup;
	}

	/**
	 * Tell if the optimization process is allowed to keep exif in this context.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function can_keep_exif() {
		if ( isset( $this->can_keep_exif ) ) {
			return $this->can_keep_exif;
		}

		$this->can_keep_exif = get_imagify_option( 'exif' );

		return $this->can_keep_exif;
	}
}
