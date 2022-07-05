<?php
namespace Imagify\Context;

/**
 * Context class used for the WP media library.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class WP extends AbstractContext {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * Context "short name".
	 *
	 * @var    string
	 * @since  1.9
	 * @author Grégory Viguier
	 */
	protected $context = 'wp';

	/**
	 * Images max width for this context. This is used when resizing.
	 *
	 * @var    int
	 * @since  1.9.8
	 * @author Grégory Viguier
	 */
	protected $resizing_threshold;

	/**
	 * Get the thumbnail sizes for this context, except the full size.
	 *
	 * @since  1.9
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
	 * Get images max width for this context. This is used when resizing.
	 * 0 means to not resize.
	 *
	 * @since  1.9.8
	 * @author Grégory Viguier
	 *
	 * @return int
	 */
	public function get_resizing_threshold() {
		if ( isset( $this->resizing_threshold ) ) {
			return $this->resizing_threshold;
		}

		if ( ! get_imagify_option( 'resize_larger' ) ) {
			$this->resizing_threshold = 0;
		} else {
			$this->resizing_threshold = max( 0, get_imagify_option( 'resize_larger_w' ) );
		}

		return $this->resizing_threshold;
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
		static $edit_attachment_cap;

		switch ( $describer ) {
			case 'manage':
				$capacity = imagify_is_active_for_network() ? 'manage_network_options' : 'manage_options';
				break;

			case 'bulk-optimize':
				$capacity = 'manage_options';
				break;

			case 'optimize':
			case 'restore':
				// This is a generic capacity: don't use it unless you have no other choices!
				if ( ! isset( $edit_attachment_cap ) ) {
					$edit_attachment_cap = get_post_type_object( 'attachment' );
					$edit_attachment_cap = $edit_attachment_cap ? $edit_attachment_cap->cap->edit_posts : 'edit_posts';
				}

				$capacity = $edit_attachment_cap;
				break;

			case 'manual-optimize':
			case 'manual-restore':
				// Must be used with an Attachment ID.
				$capacity = 'edit_post';
				break;

			case 'auto-optimize':
				$capacity = 'upload_files';
				break;

			default:
				$capacity = $describer;
		}

		return $this->filter_capacity( $capacity, $describer );
	}
}
