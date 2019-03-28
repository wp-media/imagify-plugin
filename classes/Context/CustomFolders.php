<?php
namespace Imagify\Context;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Context class used for the custom folders.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class CustomFolders extends AbstractContext {
	use \Imagify\Traits\FakeSingletonTrait;

	/**
	 * Context "short name".
	 *
	 * @var    string
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $context = 'custom-folders';

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
