<?php
namespace Imagify\Context;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Abstract used for contexts.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
abstract class AbstractContext implements ContextInterface {

	/**
	 * Context "short name".
	 *
	 * @var    string
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $context;

	/**
	 * Tell if the media/context is network-wide.
	 *
	 * @var    bool
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $is_network_wide = false;

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
	protected $allowed_mime_types = 'all';

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
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $thumbnail_sizes;

	/**
	 * Tell if the optimization process is allowed to backup in this context.
	 *
	 * @var    bool
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $can_backup;

	/**
	 * Tell if the optimization process is allowed to keep exif in this context.
	 *
	 * @var    bool
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $can_keep_exif;

	/**
	 * Get the context "short name".
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->context;
	}

	/**
	 * Tell if the context is network-wide.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_network_wide() {
		return $this->is_network_wide;
	}

	/**
	 * Get the type of files this context allows.
	 *
	 * @since  1.9
	 * @access protected
	 * @see    imagify_get_mime_types()
	 * @author Grégory Viguier
	 *
	 * @return string Possible values are:
	 *                - 'all' to allow all types.
	 *                - 'image' to allow only images.
	 *                - 'not-image' to allow only pdf files.
	 */
	public function get_allowed_mime_types() {
		return $this->allowed_mime_types;
	}

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
		return $this->get_resizing_threshold() > 0;
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
		return $this->can_keep_exif;
	}

	/**
	 * Tell if the current user is allowed to operate Imagify in this context.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $describer Capacity describer. See $this->get_capacity() for possible values. Can also be a "real" user capacity.
	 * @param  int    $media_id  A media ID.
	 * @return bool
	 */
	public function current_user_can( $describer, $media_id = null ) {
		return $this->user_can( null, $describer, $media_id );
	}

	/**
	 * Tell if a user is allowed to operate Imagify in this context.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int|\WP_User $user_id   A user ID or \WP_User object. Fallback to the current user ID.
	 * @param  string       $describer Capacity describer. See $this->get_capacity() for possible values. Can also be a "real" user capacity.
	 * @param  int          $media_id  A media ID.
	 * @return bool
	 */
	public function user_can( $user_id, $describer, $media_id = null ) {
		$current_user_id = get_current_user_id();

		if ( ! $user_id ) {
			$user    = $current_user_id;
			$user_id = $current_user_id;
		} elseif ( $user_id instanceof \WP_User ) {
			$user    = $user_id;
			$user_id = (int) $user->ID;
		} elseif ( is_numeric( $user_id ) ) {
			$user    = (int) $user_id;
			$user_id = $user;
		} else {
			$user_id = 0;
		}

		if ( ! $user_id ) {
			return false;
		}

		$media_id = $media_id ? (int) $media_id : null;
		$capacity = $this->get_capacity( $describer );

		if ( $user_id === $current_user_id ) {
			$user_can = current_user_can( $capacity, $media_id );

			/**
			 * Tell if the current user is allowed to operate Imagify in this context.
			 *
			 * @since 1.6.11
			 * @since 1.9 Added the context name as parameter.
			 *
			 * @param bool   $user_can  Tell if the current user is allowed to operate Imagify in this context.
			 * @param string $capacity  The user capacity.
			 * @param string $describer Capacity describer. See $this->get_capacity() for possible values. Can also be a "real" user capacity.
			 * @param int    $media_id  A media ID.
			 * @param string $context   The context name.
			 */
			$user_can = (bool) apply_filters( 'imagify_current_user_can', $user_can, $capacity, $describer, $media_id, $this->get_name() );
		} else {
			$user_can = user_can( $user, $capacity, $media_id );
		}

		/**
		 * Tell if the given user is allowed to operate Imagify in this context.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param bool   $user_can  Tell if the given user is allowed to operate Imagify in this context.
		 * @param int    $user_id   The user ID.
		 * @param string $capacity  The user capacity.
		 * @param string $describer Capacity describer. See $this->get_capacity() for possible values. Can also be a "real" user capacity.
		 * @param int    $media_id  A media ID.
		 * @param string $context   The context name.
		 */
		return (bool) apply_filters( 'imagify_user_can', $user_can, $user_id, $capacity, $describer, $media_id, $this->get_name() );
	}

	/**
	 * Filter a user capacity used to operate Imagify in this context.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $capacity  The user capacity.
	 * @param  string $describer Capacity describer. Possible values are like 'manage', 'bulk-optimize', 'manual-optimize', 'auto-optimize'.
	 * @return string
	 */
	protected function filter_capacity( $capacity, $describer ) {
		/**
		 * Filter a user capacity used to operate Imagify in this context.
		 *
		 * @since 1.0
		 * @since 1.6.5  Added $force_mono parameter.
		 * @since 1.6.11 Replaced $force_mono by $describer.
		 * @since 1.9    Added the context name as parameter.
		 *
		 * @param string $capacity  The user capacity.
		 * @param string $describer Capacity describer. Possible values are like 'manage', 'bulk-optimize', 'manual-optimize', 'auto-optimize'.
		 * @param string $context   The context name.
		 */
		return (string) apply_filters( 'imagify_capacity', $capacity, $describer, $this->get_name() );
	}
}
