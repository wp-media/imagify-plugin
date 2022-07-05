<?php
namespace Imagify\Context;

use \Imagify\Traits\InstanceGetterTrait;

/**
 * Context class used for the custom folders.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class CustomFolders extends AbstractContext {
	use InstanceGetterTrait;

	/**
	 * Context "short name".
	 *
	 * @var    string
	 * @since  1.9
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
	 * @author Grégory Viguier
	 */
	protected $thumbnail_sizes = [];

	/**
	 * Get images max width for this context. This is used when resizing.
	 * 0 means to not resize.
	 *
	 * @since  1.9.8
	 * @author Grégory Viguier
	 *
	 * @return int
	 */
	public function get_resizing_threshold() {
		return 0;
	}

	/**
	 * Tell if the optimization process is allowed to backup in this context.
	 *
	 * @since  1.9
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
	 * Get user capacity to operate Imagify in this context.
	 *
	 * @since  1.9
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
