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
}
