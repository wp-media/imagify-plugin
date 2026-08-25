<?php
namespace Imagify\Context;

use Imagify\Traits\InstanceGetterTrait;

/**
 * Context class used for the WP media library.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
final class WP extends AbstractContext {
	use InstanceGetterTrait;

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
	protected $resizing_threshold = 0;

	/**
	 * True once WordPress has said the browser is scaling the upload being created.
	 *
	 * Set on `rest_after_insert_attachment`, read by the threshold filter later in the same
	 * request. One request creates one attachment, so there is nothing to key it by.
	 *
	 * @var   bool
	 * @since 2.3.3
	 */
	protected $browser_is_scaling = false;

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
		if ( get_imagify_option( 'resize_larger' ) ) {
			$this->resizing_threshold = max( 0, get_imagify_option( 'resize_larger_w' ) );
		}

		return $this->resizing_threshold;
	}

	/**
	 * Filter WP's "big images threshold" with Imagify's resizing value.
	 *
	 * Imagify stands down for one case only: the upload WordPress 7.1 handed to the browser,
	 * which supplies its own scaled file through the sideload endpoint. Scaling again on the
	 * server would leave a conflicting "-scaled" file behind and point `original_image` at it
	 * instead of the real upload, which is why core switches its own downscaling off there.
	 *
	 * Nothing is lost by standing down: the value the browser scales to is produced by this
	 * same filter, in {@see WP_REST_Server::get_index()}, so Imagify's setting still governs
	 * the result.
	 *
	 * A `false` coming from anywhere else is deliberately overridden, exactly as before. Only
	 * the browser flow leaves a scaled file behind, so treating every `false` as "already
	 * scaled" would mean an image nobody resized: not WordPress, because it was told not to,
	 * and not Imagify, because it believed the work was done.
	 *
	 * @since 2.3.3
	 *
	 * @param  int|false $threshold The threshold value in pixels, or false to disable resizing.
	 * @return int|false
	 */
	public function filter_big_image_size_threshold( $threshold ) {
		if ( false === $threshold && $this->browser_is_scaling ) {
			return $threshold;
		}

		return $this->get_resizing_threshold();
	}

	/**
	 * Remember that the browser is supplying the scaled version of an attachment.
	 *
	 * Taken from where WordPress declares it rather than guessed: this runs on
	 * `rest_after_insert_attachment`, just before the metadata is generated, and the request
	 * carries `generate_sub_sizes` as `false` exactly when the browser owns the sub sizes.
	 *
	 * @since 2.3.3
	 *
	 * The threshold filter runs later in this same request, so a property is enough for it. The
	 * transient is for the optimization, which runs in a later request of its own.
	 *
	 * @param object $attachment Inserted or updated attachment object. A \WP_Post when WordPress fires this.
	 * @param object $request    Request object. A \WP_REST_Request when WordPress fires this.
	 * @param bool   $creating   True when creating an attachment, false when updating.
	 */
	public function maybe_flag_client_side_scaling( $attachment, $request, $creating ) {
		if ( ! $creating || ! is_object( $attachment ) || ! isset( $attachment->ID ) ) {
			return;
		}

		if ( ! is_object( $request ) || ! is_callable( [ $request, 'get_param' ] ) ) {
			return;
		}

		if ( false !== $request->get_param( 'generate_sub_sizes' ) ) {
			return;
		}

		$this->browser_is_scaling = true;

		self::flag_client_side_scaling( $attachment->ID );
	}

	/**
	 * Remember that the browser is supplying the scaled version of an attachment.
	 *
	 * Optimization runs in a later, asynchronous request, where the filters WordPress
	 * set up during the upload are long gone, so the state has to be stored.
	 *
	 * The flag is left to expire rather than deleted after use: it is read once per size
	 * being optimized, so deleting it on the first read would let the remaining sizes
	 * resize the file. An hour is far longer than the queue needs, and the pattern is
	 * registered in {@see \Imagify\Tools\InternalStateList} so a reset clears it.
	 *
	 * @since 2.3.3
	 *
	 * @param int $attachment_id Attachment post ID.
	 */
	public static function flag_client_side_scaling( $attachment_id ) {
		set_transient( self::get_client_side_scaling_flag( $attachment_id ), 1, HOUR_IN_SECONDS );
	}

	/**
	 * Tell if the browser supplied the scaled version of an attachment.
	 *
	 * @since 2.3.3
	 *
	 * @param  int $attachment_id Attachment post ID.
	 * @return bool
	 */
	public static function is_client_side_scaled( $attachment_id ) {
		return (bool) get_transient( self::get_client_side_scaling_flag( $attachment_id ) );
	}

	/**
	 * Get the transient name used to flag client side scaling for an attachment.
	 *
	 * @since 2.3.3
	 *
	 * @param  int $attachment_id Attachment post ID.
	 * @return string
	 */
	private static function get_client_side_scaling_flag( $attachment_id ) {
		return 'imagify_client_side_scaled_' . (int) $attachment_id;
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
