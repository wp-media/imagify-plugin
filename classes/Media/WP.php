<?php
namespace Imagify\Media;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Media class for the medias in the WP library.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class WP extends AbstractMedia {
	use \Imagify\Deprecated\Traits\Media\WPDeprecatedTrait;

	/**
	 * Tell if we’re playing in WP 5.3’s garden.
	 *
	 * @var    bool
	 * @since  1.9.8
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $is_wp53;

	/**
	 * The constructor.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int|\WP_Post $id The attachment ID, or \WP_Post object.
	 */
	public function __construct( $id ) {
		if ( ! static::constructor_accepts( $id ) ) {
			parent::__construct( 0 );
			return;
		}

		if ( is_numeric( $id ) ) {
			$id = get_post( (int) $id );
		}

		if ( ! $id || 'attachment' !== $id->post_type ) {
			parent::__construct( 0 );
			return;
		}

		parent::__construct( $id->ID );
	}

	/**
	 * Tell if the given entry can be accepted in the constructor.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  mixed $id Whatever.
	 * @return bool
	 */
	public static function constructor_accepts( $id ) {
		return $id && ( is_numeric( $id ) || $id instanceof \WP_Post );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** ORIGINAL FILE =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the original file path, even if the file doesn't exist.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file path. False on failure.
	 */
	public function get_raw_original_path() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		if ( $this->get_cdn() ) {
			return $this->get_cdn()->get_file_path( 'original' );
		}

		if ( $this->is_wp_53() ) {
			// `wp_get_original_image_path()` may return false.
			$path = wp_get_original_image_path( $this->id );
		} else {
			$path = false;
		}

		if ( ! $path ) {
			$path = get_attached_file( $this->id );
		}

		return $path ? $path : false;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** FULL SIZE FILE ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the URL of the media’s full size file.
	 *
	 * @since  1.9.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file URL. False on failure.
	 */
	public function get_fullsize_url() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		if ( $this->get_cdn() ) {
			return $this->get_cdn()->get_file_url();
		}

		$url = wp_get_attachment_url( $this->id );

		return $url ? $url : false;
	}

	/**
	 * Get the path to the media’s full size file, even if the file doesn't exist.
	 *
	 * @since  1.9.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file path. False on failure.
	 */
	public function get_raw_fullsize_path() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		if ( $this->get_cdn() ) {
			return $this->get_cdn()->get_file_path();
		}

		$path = get_attached_file( $this->id );

		return $path ? $path : false;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** BACKUP FILE ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the backup URL, even if the file doesn't exist.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file URL. False on failure.
	 */
	public function get_backup_url() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		return get_imagify_attachment_url( $this->get_raw_backup_path() );
	}

	/**
	 * Get the backup file path, even if the file doesn't exist.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file path. False on failure.
	 */
	public function get_raw_backup_path() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		return get_imagify_attachment_backup_path( $this->get_raw_original_path() );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** THUMBNAILS ============================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Create the media thumbnails.
	 * With WP 5.3+, this will also generate a new full size file if the original file is wider or taller than a defined threshold.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|WP_Error True on success. A \WP_Error instance on failure.
	 */
	public function generate_thumbnails() {
		if ( ! $this->is_valid() ) {
			return new \WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		// Store the path to the current full size file before generating the thumbnails.
		$old_full_size_path = $this->get_raw_fullsize_path();
		$metadata           = wp_generate_attachment_metadata( $this->get_id(), $this->get_raw_original_path() );

		if ( empty( $metadata['file'] ) ) {
			// Σ(ﾟДﾟ).
			update_post_meta( $this->get_id(), '_wp_attachment_metadata', $metadata );

			return true;
		}

		/**
		 * Don't change the full size file name.
		 * WP 5.3+ will rename the full size file if the resizing threshold has changed (not the same as the one used to generate it previously).
		 * This will force WP to keep the previous file name.
		 */
		$old_full_size_file_name = $this->filesystem->file_name( $old_full_size_path );
		$new_full_size_file_name = $this->filesystem->file_name( $metadata['file'] );

		if ( $new_full_size_file_name !== $old_full_size_file_name ) {
			$new_full_size_path = $this->filesystem->dir_path( $old_full_size_path ) . $new_full_size_file_name;

			$moved = $this->filesystem->move( $new_full_size_path, $old_full_size_path, true );

			if ( $moved ) {
				$metadata['file'] = $this->filesystem->dir_path( $metadata['file'] ) . $old_full_size_file_name;
				update_post_meta( $this->get_id(), '_wp_attached_file', $metadata['file'] );
			}
		}

		update_post_meta( $this->get_id(), '_wp_attachment_metadata', $metadata );

		return true;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** MEDIA DATA ============================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if the current media has the required data (the data containing the file paths and thumbnails).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function has_required_media_data() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		$file = get_post_meta( $this->id, '_wp_attached_file', true );

		if ( ! $file || preg_match( '@://@', $file ) || preg_match( '@^.:\\\@', $file ) ) {
			return false;
		}

		return (bool) wp_get_attachment_metadata( $this->id, true );
	}

	/**
	 * Get the list of the files of this media, including the full size file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array {
	 *     An array with the size names as keys ('full' is used for the full size file), and arrays of data as values:
	 *
	 *     @type string $size      The size name.
	 *     @type string $path      Absolute path to the file.
	 *     @type int    $width     The file width.
	 *     @type int    $height    The file height.
	 *     @type string $mime-type The file mime type.
	 *     @type bool   $disabled  True if the size is disabled in the plugin’s settings.
	 * }
	 */
	public function get_media_files() {
		if ( ! $this->is_valid() ) {
			return [];
		}

		$fullsize_path = $this->get_raw_fullsize_path();

		if ( ! $fullsize_path ) {
			return [];
		}

		$dimensions = $this->get_dimensions();
		$all_sizes  = [
			'full' => [
				'size'      => 'full',
				'path'      => $fullsize_path,
				'width'     => $dimensions['width'],
				'height'    => $dimensions['height'],
				'mime-type' => $this->get_mime_type(),
				'disabled'  => false,
			],
		];

		if ( $this->is_image() ) {
			$sizes = wp_get_attachment_metadata( $this->id, true );
			$sizes = ! empty( $sizes['sizes'] ) && is_array( $sizes['sizes'] ) ? $sizes['sizes'] : [];
			$sizes = array_intersect_key( $sizes, $this->get_context_instance()->get_thumbnail_sizes() );
		} else {
			$sizes = [];
		}

		if ( ! $sizes ) {
			return $all_sizes;
		}

		$dir_path              = $this->filesystem->dir_path( $fullsize_path );
		$disallowed_sizes      = get_imagify_option( 'disallowed-sizes' );
		$is_active_for_network = imagify_is_active_for_network();

		foreach ( $sizes as $size => $size_data ) {
			$all_sizes[ $size ] = [
				'size'      => $size,
				'path'      => $dir_path . $size_data['file'],
				'width'     => $size_data['width'],
				'height'    => $size_data['height'],
				'mime-type' => $size_data['mime-type'],
				'disabled'  => ! $is_active_for_network && isset( $disallowed_sizes[ $size ] ),
			];
		}

		return $this->filter_media_files( $all_sizes );
	}

	/**
	 * If the media is an image, get its width and height.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_dimensions() {
		if ( ! $this->is_image() ) {
			return [
				'width'  => 0,
				'height' => 0,
			];
		}

		$values = wp_get_attachment_image_src( $this->id, 'full' );

		return [
			'width'  => $values[1],
			'height' => $values[2],
		];
	}

	/**
	 * Update the media data dimensions.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param array $dimensions {
	 *     An array containing width and height.
	 *
	 *     @type int $width  The image width.
	 *     @type int $height The image height.
	 * }
	 */
	protected function update_media_data_dimensions( $dimensions ) {
		$metadata = wp_get_attachment_metadata( $this->id );

		if ( ! is_array( $metadata ) ) {
			$row = [];
		}

		if ( isset( $metadata['width'], $metadata['height'] ) && $metadata['width'] === $dimensions['width'] && $metadata['height'] === $dimensions['height'] ) {
			return;
		}

		$metadata['width']  = $dimensions['width'];
		$metadata['height'] = $dimensions['height'];

		update_post_meta( $this->get_id(), '_wp_attachment_metadata', $metadata );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** INTERNAL TOOLS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if we’re playing in WP 5.3’s garden.
	 *
	 * @since  1.9.8
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	protected function is_wp_53() {
		if ( isset( $this->is_wp53 ) ) {
			return $this->is_wp53;
		}

		$this->is_wp53 = function_exists( 'wp_get_original_image_path' );

		return $this->is_wp53;
	}
}
