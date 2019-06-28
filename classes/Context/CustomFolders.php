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
	use \Imagify\Traits\InstanceGetterTrait;

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

	/**
	 * Tell if the optimization process is allowed resize in this context.
	 *
	 * @var    bool
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $can_resize = false;

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
	public function get_capacity( $describer ) {
		switch ( $describer ) {
			case 'manage':
				$capacity = imagify_is_active_for_network() ? 'manage_network_options' : 'manage_options';
				break;

			case 'bulk-optimize':
			case 'optimize':
			case 'restore':
			case 'manual-optimize':
			case 'manual-restore':
			case 'auto-optimize':
				$capacity = is_multisite() ? 'manage_network_options' : 'manage_options';
				break;

			default:
				$capacity = $describer;
		}

		return $this->filter_capacity( $capacity, $describer );
	}
}
