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
	use \Imagify\Traits\InstanceGetterTrait;

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

	/**
	 * Tell if the optimization process is allowed to backup in this context.
	 *
	 * @var    bool
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $can_backup = true;

	/**
	 * Tell if the optimization process is allowed to keep exif in this context.
	 *
	 * @var    bool
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $can_keep_exif = true;

	/**
	 * Get images max width for this context. This is used when resizing.
	 * 0 means to not resize.
	 *
	 * @since  1.9.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int
	 */
	public function get_resizing_threshold() {
		return 0;
	}

	/**
	 * Get user capacity to operate Imagify in this context.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $describer Capacity describer. Possible values are like 'manage', 'bulk-optimize', 'manual-optimize', 'auto-optimize'.
	 * @return string
	 */
	public function get_capacity( $describer = 'manage' ) {
		switch ( $describer ) {
			case 'manage':
				$capacity = 'NextGEN Change options';
				break;

			case 'bulk-optimize':
				$capacity = 'NextGEN Manage others gallery';
				break;

			case 'optimize':
			case 'restore':
			case 'manual-optimize':
			case 'manual-restore':
				$capacity = 'NextGEN Manage gallery';
				break;

			case 'auto-optimize':
				$capacity = 'NextGEN Upload images';
				break;

			default:
				$capacity = $describer;
		}

		return $this->filter_capacity( $capacity, $describer );
	}
}
