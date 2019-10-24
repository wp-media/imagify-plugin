<?php
namespace Imagify\Media;

use Imagify\CDN\PushCDNInterface;
use Imagify\Context\ContextInterface;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Interface to use for "media groups" (aka attachments).
 *
 * @since  1.9
 * @author Grégory Viguier
 */
interface MediaInterface {

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
	public static function constructor_accepts( $id );

	/**
	 * Get the media ID.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int
	 */
	public function get_id();

	/**
	 * Tell if the current media is valid.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_valid();

	/**
	 * Get the media context name.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_context();

	/**
	 * Get the media context instance.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return ContextInterface
	 */
	public function get_context_instance();

	/**
	 * Get the CDN instance.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|PushCDNInterface A PushCDNInterface instance. False if no CDN is used.
	 */
	public function get_cdn();


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
	public function get_raw_original_path();

	/**
	 * Get the original media's path if the file exists.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file path. False if it doesn't exist.
	 */
	public function get_original_path();


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
	public function get_fullsize_url();

	/**
	 * Get the path to the media’s full size file, even if the file doesn't exist.
	 *
	 * @since  1.9.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file path. False on failure.
	 */
	public function get_raw_fullsize_path();

	/**
	 * Get the path to the media’s full size file if the file exists.
	 *
	 * @since  1.9.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file path. False if it doesn't exist.
	 */
	public function get_fullsize_path();


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
	public function get_backup_url();

	/**
	 * Get the backup file path, even if the file doesn't exist.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file path. False on failure.
	 */
	public function get_raw_backup_path();

	/**
	 * Get the backup file path if the file exists.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file path. False if it doesn't exist.
	 */
	public function get_backup_path();

	/**
	 * Check if the media has a backup of the original file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool True if the media has a backup.
	 */
	public function has_backup();


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
	public function generate_thumbnails();


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
	public function is_supported();

	/**
	 * Tell if the current media refers to an image, based on file extension.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool Returns false in case it's an image but not in a supported format (bmp for example).
	 */
	public function is_image();

	/**
	 * Tell if the current media refers to a pdf, based on file extension.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_pdf();

	/**
	 * Get the original file extension (if supported by Imagify).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|null
	 */
	public function get_extension();

	/**
	 * Get the original file mime type (if supported by Imagify).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_mime_type();

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
	public function get_allowed_mime_types();

	/**
	 * Tell if the current media has the required data (the data containing the file paths and thumbnails).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function has_required_media_data();

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
	public function get_media_files();

	/**
	 * If the media is an image, get its width and height.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_dimensions();

	/**
	 * If the media is an image, update the dimensions in the database with the current file dimensions.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool True on success. False on failure.
	 */
	public function update_dimensions();
}
