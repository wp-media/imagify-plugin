<?php
namespace Imagify\Media;

use Imagify\CDN\PushCDNInterface;
use Imagify\Context\ContextInterface;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Abstract used for "media groups" (aka attachments).
 *
 * @since  1.9
 * @author Grégory Viguier
 */
abstract class AbstractMedia implements MediaInterface {

	/**
	 * The media ID.
	 *
	 * @var    int
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $id;

	/**
	 * Context (where the media "comes from").
	 *
	 * @var    ContextInterface
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $context;

	/**
	 * CDN to use.
	 *
	 * @var    PushCDNInterface
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $cdn;

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
	 * Tell if the file is an image.
	 *
	 * @var    bool
	 * @since  1.9
	 * @access protected
	 * @see    $this->is_image()
	 * @author Grégory Viguier
	 */
	protected $is_image;

	/**
	 * Tell if the file is a pdf.
	 *
	 * @var    bool
	 * @since  1.9
	 * @access protected
	 * @see    $this->is_pdf()
	 * @author Grégory Viguier
	 */
	protected $is_pdf;

	/**
	 * Store the file mime type + file extension (if the file is supported).
	 *
	 * @var    array
	 * @since  1.9
	 * @access protected
	 * @see    $this->get_file_type()
	 * @author Grégory Viguier
	 */
	protected $file_type;

	/**
	 * Filesystem object.
	 *
	 * @var    object Imagify_Filesystem
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

	/**
	 * The constructor.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int $id The media ID.
	 */
	public function __construct( $id ) {
		$this->id         = (int) $id;
		$this->filesystem = \Imagify_Filesystem::get_instance();
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
		return $this->id;
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
		return $this->get_id() > 0;
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
		return $this->get_context_instance()->get_name();
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
		if ( $this->context ) {
			if ( is_string( $this->context ) ) {
				$this->context = imagify_get_context( $this->context );
			}

			return $this->context;
		}

		$class_name    = get_class( $this );
		$class_name    = '\\' . trim( $class_name, '\\' );
		$class_name    = str_replace( '\\Media\\', '\\Context\\', $class_name );
		$this->context = new $class_name();

		return $this->context;
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
		if ( isset( $this->cdn ) ) {
			return $this->cdn;
		}

		if ( ! $this->is_valid() ) {
			$this->cdn = false;
			return $this->cdn;
		}

		$media_id = $this->get_id();
		$context  = $this->get_context_instance();

		/**
		 * The CDN to use for this media.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param bool|PushCDNInterface $cdn      A PushCDNInterface instance. False if no CDN is used.
		 * @param int                   $media_id The media ID.
		 * @param ContextInterface      $context  The context object.
		 */
		$this->cdn = apply_filters( 'imagify_cdn', false, $media_id, $context );

		if ( ! $this->cdn || ! $this->cdn instanceof PushCDNInterface ) {
			$this->cdn = false;
			return $this->cdn;
		}

		return $this->cdn;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** ORIGINAL FILE =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

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
		if ( ! $this->is_valid() ) {
			return false;
		}

		$backup_path = $this->get_raw_original_path();

		if ( ! $backup_path || ! $this->filesystem->exists( $backup_path ) ) {
			return false;
		}

		return $backup_path;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** BACKUP FILE ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

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
		if ( ! $this->is_valid() ) {
			return false;
		}

		$backup_path = $this->get_raw_backup_path();

		if ( ! $backup_path || ! $this->filesystem->exists( $backup_path ) ) {
			return false;
		}

		return $backup_path;
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
		return (bool) $this->get_backup_path();
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
		return (bool) $this->get_mime_type();
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
		if ( isset( $this->is_image ) ) {
			return $this->is_image;
		}

		$this->is_image = strpos( (string) $this->get_mime_type(), 'image/' ) === 0;

		return $this->is_image;
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
		if ( isset( $this->is_pdf ) ) {
			return $this->is_pdf;
		}

		$this->is_pdf = 'application/pdf' === $this->get_mime_type();

		return $this->is_pdf;
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
		return $this->get_file_type()->ext;
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
		return $this->get_file_type()->type;
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
		return imagify_get_mime_types( $this->get_context_instance()->get_allowed_mime_types() );
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
		if ( ! $this->is_image() ) {
			// The media is not a supported image.
			return false;
		}

		$dimensions = $this->filesystem->get_image_size( $this->get_raw_original_path() );

		if ( ! $dimensions ) {
			// Could not get the new dimensions.
			return false;
		}

		$context = $this->get_context();

		/**
		 * Triggered before updating an image width and height into its metadata.
		 *
		 * @since  1.9
		 * @see    Imagify_Filesystem->get_image_size()
		 * @author Grégory Viguier
		 *
		 * @param int   $media_id   The media ID.
		 * @param array $dimensions {
		 *     An array with, among other data:
		 *
		 *     @type int $width  The image width.
		 *     @type int $height The image height.
		 * }
		 */
		do_action( "imagify_before_update_{$context}_media_data_dimensions", $this->get_id(), $dimensions );

		$this->update_media_data_dimensions( $dimensions );

		/**
		 * Triggered after updating an image width and height into its metadata.
		 *
		 * @since  1.9
		 * @see    Imagify_Filesystem->get_image_size()
		 * @author Grégory Viguier
		 *
		 * @param int   $media_id   The media ID.
		 * @param array $dimensions {
		 *     An array with, among other data:
		 *
		 *     @type int $width  The image width.
		 *     @type int $height The image height.
		 * }
		 */
		do_action( "imagify_after_update_{$context}_media_data_dimensions", $this->get_id(), $dimensions );

		return true;
	}

	/**
	 * Update the media data dimensions.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $dimensions {
	 *     An array containing width and height.
	 *
	 *     @type int $width  The image width.
	 *     @type int $height The image height.
	 * }
	 */
	abstract protected function update_media_data_dimensions( $dimensions );

	/**
	 * Get the file mime type + file extension (if the file is supported by Imagify).
	 * This test is ran against the original file.
	 *
	 * @since  1.9
	 * @access protected
	 * @see    wp_check_filetype()
	 * @author Grégory Viguier
	 *
	 * @return object
	 */
	protected function get_file_type() {
		if ( isset( $this->file_type ) ) {
			return $this->file_type;
		}

		$this->file_type = (object) [
			'ext'  => '',
			'type' => '',
		];

		if ( ! $this->is_valid() ) {
			return $this->file_type;
		}

		$path = $this->get_raw_original_path();

		if ( ! $path ) {
			return $this->file_type;
		}

		$this->file_type = (object) wp_check_filetype( $path, $this->get_allowed_mime_types() );

		return $this->file_type;
	}

	/**
	 * Filter the result of $this->get_media_files().
	 *
	 * @since  1.9
	 * @access protected
	 * @see    $this->get_media_files()
	 * @author Grégory Viguier
	 *
	 * @param  array $files An array with the size names as keys ('full' is used for the original file), and arrays of data as values.
	 * @return array
	 */
	protected function filter_media_files( $files ) {
		/**
		 * Filter the media files.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param array          $files An array with the size names as keys ('full' is used for the original file), and arrays of data as values.
		 * @param MediaInterface $media This instance.
		 */
		return (array) apply_filters( 'imagify_media_files', $files, $this );
	}
}
