<?php
namespace Imagify\Context;

use \Imagify\Traits\InstanceGetterTrait;

/**
 * Fallback class for contexts.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class Noop implements ContextInterface {
	use InstanceGetterTrait;

	/**
	 * Get the context "short name".
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_name() {
		return 'noop';
	}

	/**
	 * Tell if the context is network-wide.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_network_wide() {
		return false;
	}

	/**
	 * Get the type of files this context allows.
	 *
	 * @since  1.9
	 * @see    imagify_get_mime_types()
	 * @author Grégory Viguier
	 *
	 * @return string Possible values are:
	 *                - 'all' to allow all types.
	 *                - 'image' to allow only images.
	 *                - 'not-image' to allow only pdf files.
	 */
	public function get_allowed_mime_types() {
		return 'all';
	}

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
		return [];
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
		return 0;
	}

	/**
	 * Tell if the optimization process is allowed resize in this context.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function can_resize() {
		return false;
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
		return false;
	}

	/**
	 * Tell if the current user is allowed to operate Imagify in this context.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 *
	 * @param  string $describer Capacity describer. See $this->get_capacity() for possible values. Can also be a "real" user capacity.
	 * @param  int    $media_id  A media ID.
	 * @return bool
	 */
	public function current_user_can( $describer, $media_id = null ) {
		return false;
	}

	/**
	 * Tell if a user is allowed to operate Imagify in this context.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 *
	 * @param  int    $user_id   A user ID.
	 * @param  string $describer Capacity describer. See $this->get_capacity() for possible values. Can also be a "real" user capacity.
	 * @param  int    $media_id  A media ID.
	 * @return bool
	 */
	public function user_can( $user_id, $describer, $media_id = null ) {
		return false;
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
		return 'noop';
	}
}
