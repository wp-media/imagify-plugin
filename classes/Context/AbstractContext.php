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
}
