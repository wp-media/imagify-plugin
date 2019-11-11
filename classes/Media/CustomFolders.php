<?php
namespace Imagify\Media;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Media class for the custom folders.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class CustomFolders extends AbstractMedia {
	use \Imagify\Traits\MediaRowTrait;
	use \Imagify\Deprecated\Traits\Media\CustomFoldersDeprecatedTrait;

	/**
	 * Context (where the media "comes from").
	 *
	 * @var    string
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $context = 'custom-folders';

	/**
	 * The attachment SQL DB class.
	 *
	 * @var    string
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $db_class_name = 'Imagify_Files_DB';

	/**
	 * Tell if the media/context is network-wide.
	 *
	 * @var    bool
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $is_network_wide = true;

	/**
	 * The constructor.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int|array|object $id The file ID. It can also be an array or object representing the file data.
	 */
	public function __construct( $id ) {
		if ( ! static::constructor_accepts( $id ) ) {
			$this->invalidate_row();
			parent::__construct( 0 );
			return;
		}

		if ( is_numeric( $id ) ) {
			$this->id = (int) $id;
			$this->get_row();
		} else {
			$prim_key  = $this->get_row_db_instance()->get_primary_key();
			$this->row = (array) $id;
			$this->id  = $this->row[ $prim_key ];
		}

		parent::__construct( $this->id );
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
		return $id && ( is_numeric( $id ) || is_array( $id ) || is_object( $id ) );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** ORIGINAL FILE =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the original media's path.
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

		$row = $this->get_row();

		if ( ! $row || empty( $row['path'] ) ) {
			return false;
		}

		return \Imagify_Files_Scan::remove_placeholder( $row['path'] );
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

		$row = $this->get_row();

		if ( ! $row || empty( $row['path'] ) ) {
			return false;
		}

		return \Imagify_Files_Scan::remove_placeholder( $row['path'], 'url' );
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

		$row = $this->get_row();

		if ( ! $row || empty( $row['path'] ) ) {
			return false;
		}

		return \Imagify_Files_Scan::remove_placeholder( $row['path'] );
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

		return site_url( $this->filesystem->make_path_relative( $this->get_raw_backup_path() ) );
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

		return \Imagify_Custom_Folders::get_file_backup_path( $this->get_raw_original_path() );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** THUMBNAILS ============================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Create the media thumbnails.
	 * And since this context does not support thumbnails...
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
		return $this->is_valid();
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
		$sizes      = [
			'full' => [
				'size'      => 'full',
				'path'      => $fullsize_path,
				'width'     => $dimensions['width'],
				'height'    => $dimensions['height'],
				'mime-type' => $this->get_mime_type(),
				'disabled'  => false,
			],
		];

		return $this->filter_media_files( $sizes );
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

		$row = $this->get_row();

		return [
			'width'  => ! empty( $row['width'] )  ? $row['width']  : 0,
			'height' => ! empty( $row['height'] ) ? $row['height'] : 0,
		];
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
	protected function update_media_data_dimensions( $dimensions ) {
		$row = $this->get_row();

		if ( ! is_array( $row ) ) {
			$row = [];
		}

		if ( isset( $row['width'], $row['height'] ) && $row['width'] === $dimensions['width'] && $row['height'] === $dimensions['height'] ) {
			return;
		}

		$row['width']  = $dimensions['width'];
		$row['height'] = $dimensions['height'];

		$this->update_row( $row );
	}
}
