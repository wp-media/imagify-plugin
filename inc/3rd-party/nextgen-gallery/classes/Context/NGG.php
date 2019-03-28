<?php
namespace Imagify\ThirdParty\NGG\Context;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Context class used for the WP media library.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class NGG extends \Imagify\Context\AbstractContext {
	use \Imagify\Traits\FakeSingletonTrait;

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
	 * Type of files this context allows.
	 *
	 * @var    string Possible values are:
	 *     - 'all' to allow all types.
	 *     - 'image' to allow only images.
	 *     - 'not-image' to allow only pdf files.
	 * @since  1.9
	 * @access protected
	 * @see    imagify_get_mime_types()
	 * @author Grégory Viguier
	 */
	protected $allowed_mime_types = 'image';

	/**
	 * The thumbnail sizes for this context, except the full size.
	 *
	 * @var    array {
	 *     Data for the currently registered thumbnail sizes.
	 *     Size names are used as array keys.
	 *
	 *     @type int    $width  The image width.
	 *     @type int    $height The image height.
	 *     @type bool   $crop   True to crop, false to resize.
	 *     @type string $name   The size name.
	 * }
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	protected $thumbnail_sizes = [];
}
