<?php
namespace Imagify\Media;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Fallback class for "media groups" (aka attachments).
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class Noop implements MediaInterface {

	/**
	 * Tell if the given entry can be accepted in the constructor.
	 * For example it can include `is_numeric( $id )` if the constructor accepts integers.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  mixed $id Whatever.
	 * @return bool
	 */
	public static function constructor_accepts( $id ) {
		return false;
	}

	/**
	 * Get the media ID.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int
	 */
	public function get_id() {
		return 0;
	}

	/**
	 * Tell if the current media is valid.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_valid() {
		return false;
	}

	/**
	 * Get the media context name.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_context() {
		return 'noop';
	}

	/**
	 * Get the media context instance.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return ContextInterface
	 */
	public function get_context_instance() {
		return \Imagify\Context\Noop::get_instance();
	}

	/**
	 * Get the CDN instance.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|PushCDNInterface A PushCDNInterface instance. False if no CDN is used.
	 */
	public function get_cdn() {
		return false;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** ORIGINAL FILE =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the original media's URL.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file URL. False on failure.
	 */
	public function get_original_url() {
		return false;
	}

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
		return false;
	}

	/**
	 * Get the original media's path if the file exists.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file path. False if it doesn't exist.
	 */
	public function get_original_path() {
		return false;
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
		return false;
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
		return false;
	}

	/**
	 * Get the backup file path if the file exists.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file path. False if it doesn't exist.
	 */
	public function get_backup_path() {
		return false;
	}

	/**
	 * Check if the media has a backup of the original file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool True if the media has a backup.
	 */
	public function has_backup() {
		return false;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** THUMBNAILS ============================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Create the media thumbnails.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|WP_Error True on success. A \WP_Error instance on failure.
	 */
	public function generate_thumbnails() {
		return new \WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** MEDIA DATA ============================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if the current media type is supported.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_supported() {
		return false;
	}

	/**
	 * Tell if the current media refers to an image, based on file extension.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool Returns false in case it's an image but not in a supported format (bmp for example).
	 */
	public function is_image() {
		return false;
	}

	/**
	 * Tell if the current media refers to a pdf, based on file extension.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_pdf() {
		return false;
	}

	/**
	 * Get the original file extension (if supported by Imagify).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|null
	 */
	public function get_extension() {
		return '';
	}

	/**
	 * Get the original file mime type (if supported by Imagify).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_mime_type() {
		return '';
	}

	/**
	 * Get the file mime type + file extension (if the file is supported by Imagify).
	 * This test is ran against the original file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_allowed_mime_types() {
		return imagify_get_mime_types( 'all' );
	}

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
		return false;
	}

	/**
	 * Get the list of the files of this media, including the original file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array {
	 *     An array with the size names as keys ('full' is used for the original file), and arrays of data as values:
	 *
	 *     @type string $path      Absolute path to the file.
	 *     @type int    $width     The file width.
	 *     @type int    $height    The file height.
	 *     @type string $mime-type The file mime type.
	 * }
	 */
	public function get_media_files() {
		return [];
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
		return [
			'width'  => 0,
			'height' => 0,
		];
	}

	/**
	 * If the media is an image, update the dimensions in the database with the current file dimensions.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool True on success. False on failure.
	 */
	public function update_dimensions() {
		return false;
	}
}
