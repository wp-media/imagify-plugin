<?php
namespace Imagify\ThirdParty\NGG\Media;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Media class for the medias from NextGen Gallery.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class NGG extends \Imagify\Media\AbstractMedia {
	use \Imagify\Deprecated\Traits\Media\NGGDeprecatedTrait;

	/**
	 * Context (where the media "comes from").
	 *
	 * @var    string
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $context = 'ngg';

	/**
	 * The image object.
	 *
	 * @var    object A \nggImage object.
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $image;

	/**
	 * The storage object used by NGG.
	 *
	 * @var    object A \C_Gallery_Storage object (by default).
	 * @since  1.8.
	 * @access protected
	 */
	protected $storage;

	/**
	 * The constructor.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int|\nggImage|\nggdb|\StdClass $id The NGG image ID, \nggImage object, \nggdb object, or an anonym object containing a pid property.
	 */
	public function __construct( $id ) {
		if ( ! static::constructor_accepts( $id ) ) {
			parent::__construct( 0 );
			return;
		}

		if ( is_numeric( $id ) ) {
			$this->image = \nggdb::find_image( (int) $id );
			$id          = ! empty( $this->image->pid ) ? (int) $this->image->pid : 0;
		} elseif ( $id instanceof \nggImage ) {
			$this->image = $id;
			$id          = (int) $id->pid;
		} elseif ( is_object( $id ) ) {
			$this->image = \nggdb::find_image( (int) $id->pid );
			$id          = ! empty( $this->image->pid ) ? (int) $this->image->pid : 0;
		} else {
			$id = 0;
		}

		if ( ! $id ) {
			$this->image = null;

			parent::__construct( 0 );
			return;
		}

		parent::__construct( $id );

		// NGG storage.
		if ( ! empty( $this->image->_ngiw ) ) {
			$this->storage = $this->image->_ngiw->get_storage()->object;
		} else {
			$this->storage = \C_Gallery_Storage::get_instance()->object;
		}

		// Load nggAdmin class.
		$ngg_admin_functions_path = WP_PLUGIN_DIR . '/' . NGGFOLDER . '/products/photocrati_nextgen/modules/ngglegacy/admin/functions.php';

		if ( ! class_exists( 'nggAdmin' ) && $this->filesystem->exists( $ngg_admin_functions_path ) ) {
			require_once $ngg_admin_functions_path;
		}
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
		if ( ! $id ) {
			return false;
		}

		if ( is_numeric( $id ) || $id instanceof \nggImage ) {
			return true;
		}

		return is_object( $id ) && ! empty( $id->pid ) && is_numeric( $id->pid );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** NGG SPECIFICS =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the NGG image object.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return \nggImage
	 */
	public function get_ngg_image() {
		return $this->image;
	}

	/**
	 * Get the NGG storage object.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return \C_Gallery_Storage
	 */
	public function get_ngg_storage() {
		return $this->storage;
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

		return ! empty( $this->image->imagePath ) ? $this->image->imagePath : false;
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

		return ! empty( $this->image->imageURL ) ? $this->image->imageURL : false;
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

		return ! empty( $this->image->imagePath ) ? $this->image->imagePath : false;
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

		return site_url( '/' . $this->filesystem->make_path_relative( $this->get_raw_backup_path() ) );
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

		return get_imagify_ngg_attachment_backup_path( $this->get_raw_original_path() );
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
		if ( ! $this->is_valid() ) {
			return new \WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$image_data = $this->storage->_image_mapper->find( $this->get_id() ); // stdClass Object.

		if ( ! $image_data ) {
			// ¯\(°_o)/¯
			return new \WP_Error( 'no_ngg_image', __( 'Image not found in NextGen Gallery data.', 'imagify' ) );
		}

		if ( empty( $image_data->meta_data['backup'] ) || ! is_array( $image_data->meta_data['backup'] ) ) {
			$full_path = $this->storage->get_image_abspath( $image_data );

			$image_data->meta_data['backup'] = [
				'filename'  => $this->filesystem->file_name( $full_path ), // Yes, $full_path.
				'width'     => $image_data->meta_data['width'],            // Original image width.
				'height'    => $image_data->meta_data['height'],           // Original image height.
				'generated' => microtime(),
			];
		}

		$backup_path = $this->storage->get_image_abspath( $image_data, 'backup' );
		$failed      = [];

		foreach ( $this->get_media_files() as $size_name => $size_data ) {
			if ( 'full' === $size_name ) {
				continue;
			}

			$params    = $this->storage->get_image_size_params( $image_data, $size_name );
			$thumbnail = @$this->storage->generate_image_clone( // Don't remove this @ or the sky will fall.
				$backup_path,
				$this->storage->get_image_abspath( $image_data, $size_name ),
				$params
			);

			if ( ! $thumbnail ) {
				// Failed.
				$failed[] = $size_name;
				unset( $image_data->meta_data[ $size_name ] );
				continue;
			}

			$size_meta = [
				'width'     => 0,
				'height'    => 0,
				'filename'  => \M_I18n::mb_basename( $thumbnail->fileName ),
				'generated' => microtime(),
			];

			$dimensions = $this->filesystem->get_image_size( $thumbnail->fileName );

			if ( $dimensions ) {
				$size_meta['width']  = $dimensions['width'];
				$size_meta['height'] = $dimensions['height'];
			}

			if ( isset( $params['crop_frame'] ) ) {
				$size_meta['crop_frame'] = $params['crop_frame'];
			}

			$image_data->meta_data[ $size_name ] = $size_meta;
		} // End foreach().

		// Keep our property up to date.
		$this->image->_ngiw->_cache['meta_data'] = $image_data->meta_data;
		$this->image->_ngiw->_orig_image         = $image_data;

		$post_id = $this->storage->_image_mapper->save( $image_data );

		if ( ! $post_id ) {
			return new \WP_Error( 'meta_data_not_saved', __( 'Related NextGen Gallery data could not be saved.', 'imagify' ) );
		}

		if ( $failed ) {
			return new \WP_Error(
				'thumbnail_restore_failed',
				sprintf( _n( '%n thumbnail could not be restored.', '%n thumbnails could not be restored.', count( $failed ), 'imagify' ), count( $failed ) ),
				[ 'failed_thumbnails' => $failed ]
			);
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
		static $sizes;

		if ( ! $this->is_valid() ) {
			return false;
		}

		if ( ! isset( $sizes ) ) {
			$sizes = $this->get_media_files();
		}

		return $sizes && ! empty( $this->image->imagePath );
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

		if ( ! $this->is_image() ) {
			return $this->filter_media_files( $all_sizes );
		}

		// Remove common values (that have no value for us here, lol). Also remove 'full' and 'backup'.
		$image_data = array_diff_key( $this->image->meta_data, [
			'full'              => 1,
			'backup'            => 1,
			'width'             => 1,
			'height'            => 1,
			'md5'               => 1,
			'aperture'          => 1,
			'credit'            => 1,
			'camera'            => 1,
			'caption'           => 1,
			'created_timestamp' => 1,
			'copyright'         => 1,
			'focal_length'      => 1,
			'iso'               => 1,
			'shutter_speed'     => 1,
			'flash'             => 1,
			'title'             => 1,
			'keywords'          => 1,
			'saved'             => 1,
		] );

		if ( ! $image_data ) {
			return $this->filter_media_files( $all_sizes );
		}

		$ngg_data = $this->storage->_image_mapper->find( $this->get_id() );

		foreach ( $image_data as $size => $size_data ) {
			if ( ! isset( $size_data['width'], $size_data['height'], $size_data['filename'], $size_data['generated'] ) ) {
				continue;
			}

			$file_type = (object) wp_check_filetype( $size_data['filename'], $this->get_allowed_mime_types() );

			if ( ! $file_type->type ) {
				continue;
			}

			$all_sizes[ $size ] = [
				'size'      => $size,
				'path'      => $this->storage->get_image_abspath( $ngg_data, $size ),
				'width'     => (int) $size_data['width'],
				'height'    => (int) $size_data['height'],
				'mime-type' => $file_type->type,
				'disabled'  => false,
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

		return [
			'width'  => ! empty( $this->image->meta_data['width'] )  ? (int) $this->image->meta_data['width']  : 0,
			'height' => ! empty( $this->image->meta_data['height'] ) ? (int) $this->image->meta_data['height'] : 0,
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
		$changed = false;
		$data    = [
			'width'  => $dimensions['width'],
			'height' => $dimensions['height'],
			'md5'    => md5_file( $this->get_raw_fullsize_path() ),
		];

		foreach ( $data as $k => $v ) {
			if ( ! isset( $this->image->meta_data[ $k ] ) || $this->image->meta_data[ $k ] !== $v ) {
				$this->image->meta_data[ $k ] = $v;
				$changed = true;
			}
		}

		if ( $changed ) {
			\nggdb::update_image_meta( $this->id, $this->image->meta_data );
		}
	}
}
