<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Imagify NextGen Gallery attachment class.
 *
 * @since  1.5
 * @since  1.9 Deprecated
 * @author Jonathan Buttigieg
 * @deprecated
 */
class Imagify_NGG_Attachment extends Imagify_Attachment {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.4';

	/**
	 * The attachment SQL DB class.
	 *
	 * @var    string
	 * @since  1.7
	 * @access protected
	 */
	protected $db_class_name = '\Imagify\ThirdParty\NGG\DB';

	/**
	 * The image object.
	 *
	 * @var    object A nggImage object.
	 * @since  1.5
	 * @since  1.7 Not public anymore.
	 * @access protected
	 */
	protected $image;

	/**
	 * The storage object used by NGG.
	 *
	 * @var    object A C_Gallery_Storage object (by default).
	 * @since  1.8.
	 * @access protected
	 */
	protected $storage;

	/**
	 * Tell if the file mime type can be optimized by Imagify.
	 *
	 * @var    bool
	 * @since  1.6.9
	 * @since  1.7 Not public anymore.
	 * @access protected
	 * @see    $this->is_mime_type_supported()
	 */
	protected $is_mime_type_supported;

	/**
	 * The constructor.
	 *
	 * @since  1.5
	 * @author Jonathan Buttigieg
	 *
	 * @param int|object $id An image attachment ID or a NGG object.
	 */
	public function __construct( $id ) {
		imagify_deprecated_class( get_class( $this ), '1.9', '\\Imagify\\ThirdParty\\NGG\\Optimization\\Process\\NGG( $id )' );

		if ( is_object( $id ) ) {
			if ( $id instanceof nggImage ) {
				$this->image = $id;
				$this->id    = (int) $id->pid;
			} else {
				$this->image = nggdb::find_image( (int) $id->pid );
				$this->id    = ! empty( $this->image->pid ) ? (int) $this->image->pid : 0;
			}
		} else {
			$this->image = nggdb::find_image( absint( $id ) );
			$this->id    = ! empty( $this->image->pid ) ? (int) $this->image->pid : 0;
		}

		$this->get_row();

		if ( ! empty( $this->image->_ngiw ) ) {
			$this->storage = $this->image->_ngiw->get_storage()->object;
		} else {
			$this->storage = C_Gallery_Storage::get_instance()->object;
		}

		$this->filesystem                   = Imagify_Filesystem::get_instance();
		$this->optimization_state_transient = 'imagify-ngg-async-in-progress-' . $this->id;

		// Load nggAdmin class.
		$ngg_admin_functions_path = WP_PLUGIN_DIR . '/' . NGGFOLDER . '/products/photocrati_nextgen/modules/ngglegacy/admin/functions.php';

		if ( ! class_exists( 'nggAdmin' ) && $this->filesystem->exists( $ngg_admin_functions_path ) ) {
			require_once $ngg_admin_functions_path;
		}
	}

	/**
	 * Get the original attachment path.
	 *
	 * @since  1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return string
	 */
	public function get_original_path() {
		if ( ! $this->is_valid() ) {
			return '';
		}

		return $this->image->imagePath;
	}

	/**
	 * Get the original attachment URL.
	 *
	 * @since  1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return string
	 */
	public function get_original_url() {
		if ( ! $this->is_valid() ) {
			return '';
		}

		return $this->image->imageURL;
	}

	/**
	 * Get the attachment backup file path, even if the file doesn't exist.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return string|bool The file path. False on failure.
	 */
	public function get_raw_backup_path() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		return get_imagify_ngg_attachment_backup_path( $this->get_original_path() );
	}

	/**
	 * Get the attachment backup URL.
	 *
	 * @since  1.6.8
	 * @author Grégory Viguier
	 *
	 * @return string|false
	 */
	public function get_backup_url() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		return site_url( '/' ) . $this->filesystem->make_path_relative( $this->get_raw_backup_path() );
	}

	/**
	 * Get the attachment optimization data.
	 *
	 * @since  1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return array
	 */
	public function get_data() {
		$row = $this->get_row();
		return isset( $row['data'] ) ? $row['data'] : array();
	}

	/**
	 * Get the attachment optimization level.
	 *
	 * @since  1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return int|bool
	 */
	public function get_optimization_level() {
		$row = $this->get_row();
		return isset( $row['optimization_level'] ) ? (int) $row['optimization_level'] : false;
	}

	/**
	 * Get the attachment optimization status (success or error).
	 *
	 * @since  1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return string|bool
	 */
	public function get_status() {
		$row = $this->get_row();
		return isset( $row['status'] ) ? $row['status'] : false;
	}

	/**
	 * Delete the data related to optimization.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function delete_imagify_data() {
		if ( ! $this->get_row() ) {
			return;
		}

		$this->delete_row();
	}

	/**
	 * Get width and height of the original image.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_dimensions() {
		return array(
			'width'  => ! empty( $this->image->meta_data['width'] )  ? (int) $this->image->meta_data['width']  : 0,
			'height' => ! empty( $this->image->meta_data['height'] ) ? (int) $this->image->meta_data['height'] : 0,
		);
	}

	/**
	 * Get the file mime type + file extension (if the file is supported).
	 *
	 * @since  1.8
	 * @access public
	 * @see    wp_check_filetype()
	 * @author Grégory Viguier
	 *
	 * @return object
	 */
	public function get_file_type() {
		if ( isset( $this->file_type ) ) {
			return $this->file_type;
		}

		if ( ! $this->is_valid() ) {
			$this->file_type = (object) array(
				'ext'  => '',
				'type' => '',
			);
			return $this->file_type;
		}

		$this->file_type = (object) wp_check_filetype( $this->get_original_path(), imagify_get_mime_types( 'image' ) );

		return $this->file_type;
	}

	/**
	 * Tell if the current attachment has the required WP metadata.
	 *
	 * @since  1.6.12
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function has_required_metadata() {
		static $sizes;

		if ( ! isset( $sizes ) ) {
			$sizes = $this->get_image_sizes();
		}

		return $sizes && $this->get_original_path();
	}

	/**
	 * Update the metadata size of the attachment.
	 *
	 * @since 1.5
	 *
	 * @access public
	 * @return void
	 */
	public function update_metadata_size() {
		$size = $this->filesystem->get_image_size( $this->get_original_path() );

		if ( ! $size ) {
			return;
		}

		$this->image->meta_data['width']          = $size['width'];
		$this->image->meta_data['height']         = $size['height'];
		$this->image->meta_data['full']['width']  = $size['width'];
		$this->image->meta_data['full']['height'] = $size['height'];

		nggdb::update_image_meta( $this->id, $this->image->meta_data );
	}

	/**
	 * Fills statistics data with values from $data array.
	 *
	 * @since  1.5
	 * @since  1.6.5 Not static anymore.
	 * @since  1.6.6 Removed the attachment ID parameter.
	 * @since  1.7   Removed the image URL parameter.
	 * @author Jonathan Buttigieg
	 * @access public
	 *
	 * @param  array  $data      The statistics data.
	 * @param  object $response  The API response.
	 * @param  string $size      The attachment size key.
	 * @return bool|array        False if the original size has an error or an array contains the data for other result.
	 */
	public function fill_data( $data, $response, $size = 'full' ) {
		$data          = is_array( $data ) ? $data : array();
		$data['sizes'] = ! empty( $data['sizes'] ) && is_array( $data['sizes'] ) ? $data['sizes'] : array();

		if ( empty( $data['stats'] ) ) {
			$data['stats'] = array(
				'original_size'  => 0,
				'optimized_size' => 0,
				'percent'        => 0,
			);
		}

		if ( is_wp_error( $response ) ) {
			// Error or already optimized.
			$error        = $response->get_error_message();
			$error_status = 'error';

			$data['sizes'][ $size ] = array(
				'success' => false,
				'error'   => $error,
			);

			// Update the error status for the original size.
			if ( 'full' === $size ) {
				if ( false !== strpos( $error, 'This image is already compressed' ) ) {
					$error_status = 'already_optimized';
				}

				$this->update_row( array(
					// The pid column is needed in case the row doesn't exist yet.
					'pid'    => $this->id,
					'status' => $error_status,
					'data'   => $data,
				) );

				return false;
			}

			return $data;
		}

		// Success.
		$old_data      = $this->get_data();
		$original_size = ! empty( $old_data['sizes'][ $size ]['original_size'] ) ? (int) $old_data['sizes'][ $size ]['original_size'] : 0;

		$response = (object) array_merge( array(
			'original_size' => 0,
			'new_size'      => 0,
			'percent'       => 0,
		), (array) $response );

		if ( ! empty( $response->original_size ) && ! $original_size ) {
			$original_size = (int) $response->original_size;
		}

		if ( ! empty( $response->new_size ) ) {
			$optimized_size = (int) $response->new_size;
		} else {
			$file_path      = $this->get_original_path();
			$file_path      = $file_path && $this->filesystem->exists( $file_path ) ? $file_path : false;
			$optimized_size = $file_path ? $this->filesystem->size( $file_path ) : 0;
		}

		if ( $original_size && $optimized_size ) {
			$percent = round( ( $original_size - $optimized_size ) / $original_size * 100, 2 );
		} elseif ( ! empty( $response->percent ) ) {
			$percent = round( $response->percent, 2 );
		} else {
			$percent = 0;
		}

		$data['sizes'][ $size ] = array(
			'success'        => true,
			'original_size'  => $original_size,
			'optimized_size' => $optimized_size,
			'percent'        => $percent,
		);

		$data['stats']['original_size']  += $original_size;
		$data['stats']['optimized_size'] += $optimized_size;
		$data['stats']['percent']         = round( ( ( $data['stats']['original_size'] - $data['stats']['optimized_size'] ) / $data['stats']['original_size'] ) * 100, 2 );

		return $data;
	}

	/**
	 * Optimize all sizes with Imagify.
	 *
	 * @since  1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @param  int   $optimization_level  The optimization level (2=ultra, 1=aggressive, 0=normal).
	 * @param  array $metadata            The attachment meta data (not used here).
	 * @return array $data                The optimization data.
	 */
	public function optimize( $optimization_level = null, $metadata = array() ) {
		// Check if the attachment extension is allowed.
		if ( ! $this->is_extension_supported() ) {
			return;
		}

		$optimization_level = isset( $optimization_level ) ? (int) $optimization_level : get_imagify_option( 'optimization_level' );

		// To avoid issue with "original_size" at 0 in "_imagify_data".
		if ( 0 === (int) $this->get_stats_data( 'original_size' ) ) {
			$this->delete_imagify_data();
		}

		// Check if the full size is already optimized.
		if ( $this->is_optimized() && $this->get_optimization_level() === $optimization_level ) {
			return;
		}

		// Get file path for original image.
		$attachment_path = $this->get_original_path();
		$attachment_url  = $this->get_original_url();

		/**
		 * Fires before optimizing an attachment.
		 *
		 * @since  1.5
		 * @author Jonathan Buttigieg
		 *
		 * @param int $id The image ID
		 */
		do_action( 'before_imagify_ngg_optimize_attachment', $this->id );

		$this->set_running_status();

		// Optimize the original size.
		$response = do_imagify( $attachment_path, array(
			'optimization_level' => $optimization_level,
			'context'            => 'NGG',
			'keep_exif'          => true,
			'original_size'      => $this->get_original_size( false ),
			'backup_path'        => $this->get_raw_backup_path(),
		) );

		$data = $this->fill_data( null, $response );

		/**
		 * Filter the optimization data of the full size.
		 *
		 * @since  1.8
		 * @author Grégory Viguier
		 *
		 * @param array  $data               The statistics data.
		 * @param object $response           The API response.
		 * @param int    $id                 The attachment ID.
		 * @param string $attachment_path    The attachment path.
		 * @param string $attachment_url     The attachment URL.
		 * @param string $size_key           The attachment size key. The value is obviously 'full' but it's kept for concistancy with other filters.
		 * @param int    $optimization_level The optimization level.
		 */
		$data = apply_filters( 'imagify_fill_ngg_full_size_data', $data, $response, $this->id, $attachment_path, $attachment_url, 'full', $optimization_level );

		// Save the optimization level.
		$this->update_row( array(
			// The pid column is needed in case the row doesn't exist yet.
			'pid'                => $this->id,
			'optimization_level' => $optimization_level,
		) );

		if ( ! $data ) {
			// Error or already optimized.
			$this->delete_running_status();
			return;
		}

		// Optimize thumbnails.
		$data = $this->optimize_thumbnails( $optimization_level, $data );

		// Save the status to success.
		$this->update_row( array(
			'status' => 'success',
		) );

		/**
		 * Update NGG meta data.
		 */
		$image_data = $this->storage->_image_mapper->find( $this->id );

		if ( ! $image_data ) {
			$this->delete_running_status();
			return $data;
		}

		$dimensions = $this->filesystem->get_image_size( $attachment_path );
		$md5        = md5_file( $attachment_path );

		if ( ( $dimensions || $md5 ) && ( empty( $image_data->meta_data['full'] ) || ! is_array( $image_data->meta_data['full'] ) ) ) {
			$image_data->meta_data['full'] = array(
				'width'  => 0,
				'height' => 0,
				'md5'    => '',
			);
		}

		if ( $dimensions ) {
			$image_data->meta_data['width']  = $dimensions['width'];
			$image_data->meta_data['height'] = $dimensions['height'];
			$image_data->meta_data['full']['width']  = $dimensions['width'];
			$image_data->meta_data['full']['height'] = $dimensions['height'];
		}

		if ( $md5 ) {
			$image_data->meta_data['md5'] = $md5;
			$image_data->meta_data['full']['md5'] = $md5;
		}

		/**
		 * Fires after optimizing an attachment.
		 *
		 * @since 1.5
		 *
		 * @param int    $id    The attachment ID.
		 * @param array  $data  The optimization data.
		*/
		do_action( 'after_imagify_ngg_optimize_attachment', $this->id, $data );

		$this->delete_running_status();

		return $data;
	}

	/**
	 * Optimize all thumbnails of an image.
	 *
	 * @since  1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @param  int   $optimization_level  The optimization level (2=ultra, 1=aggressive, 0=normal).
	 * @param  array $data                The optimization data.
	 * @return array $data                The optimization data.
	 */
	public function optimize_thumbnails( $optimization_level = null, $data = array() ) {
		$sizes = $this->get_image_sizes();
		$data  = $data ? $data : $this->get_data();

		// Stop if the original image has an error.
		if ( $this->has_error() ) {
			return $data;
		}

		$optimization_level = isset( $optimization_level ) ? (int) $optimization_level : get_imagify_option( 'optimization_level' );

		/**
		 * Fires before optimizing all thumbnails.
		 *
		 * @since  1.5
		 * @author Jonathan Buttigieg
		 *
		 * @param int $id The image ID.
		 */
		do_action( 'before_imagify_ngg_optimize_thumbnails', $this->id );

		if ( $sizes ) {
			$image_data = $this->storage->_image_mapper->find( $this->id );

			foreach ( $sizes as $size_key ) {
				if ( 'full' === $size_key || isset( $data['sizes'][ $size_key ]['success'] ) ) {
					continue;
				}

				$thumbnail_path = $this->storage->get_image_abspath( $image_data, $size_key );
				$thumbnail_url  = $this->storage->get_image_url( $image_data, $size_key );

				// Optimize the thumbnail size.
				$response = do_imagify( $thumbnail_path, array(
					'optimization_level' => $optimization_level,
					'context'            => 'NGG',
					'keep_exif'          => true,
					'backup'             => false,
				) );

				$data = $this->fill_data( $data, $response, $size_key );

				/**
				 * Filter the optimization data of a specific thumbnail.
				 *
				 * @since  1.5
				 * @author Jonathan Buttigieg
				 *
				 * @param  array  $data            The statistics data.
				 * @param  object $response        The API response.
				 * @param  int    $id              The image ID.
				 * @param  string $thumbnail_path  The image path.
				 * @param  string $thumbnail_url   The image URL.
				 * @param  string $size_key        The image size key.
				 * @param  bool   $is_aggressive   The optimization level.
				 * @return array  $data            The new optimization data.
				 */
				$data = apply_filters( 'imagify_fill_ngg_thumbnail_data', $data, $response, $this->id, $thumbnail_path, $thumbnail_url, $size_key, $optimization_level );
			}

			$this->update_row( array(
				'data' => $data,
			) );
		} // End if().

		/**
		 * Fires after optimizing all thumbnails.
		 *
		 * @since  1.5
		 * @author Jonathan Buttigieg
		 *
		 * @param int   $id    The image ID.
		 * @param array $data  The optimization data.
		 */
		do_action( 'after_imagify_ngg_optimize_thumbnails', $this->id, $data );

		return $data;
	}

	/**
	 * Optimize one size.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param string $size The thumbnail size.
	 */
	public function optimize_new_thumbnail( $size ) {
		// Check if the attachment extension is allowed.
		if ( ! $this->is_extension_supported() ) {
			return;
		}

		if ( ! $this->is_optimized() ) {
			// The main image is not optimized.
			return;
		}

		$data = $this->get_data();

		if ( isset( $data['sizes'][ $size ]['success'] ) ) {
			// This thumbnail has already been processed.
			return;
		}

		$sizes = $this->get_image_sizes();
		$sizes = array_flip( $sizes );

		if ( ! isset( $sizes[ $size ] ) ) {
			// This size doesn't exist.
			return;
		}

		/**
		 * Fires before optimizing a thumbnail.
		 *
		 * @since  1.8
		 * @author Grégory Viguier
		 *
		 * @param int $id The image ID.
		 */
		do_action( 'before_imagify_ngg_optimize_new_thumbnail', $this->id );

		$this->set_running_status();

		$image_data         = $this->storage->_image_mapper->find( $this->id );
		$thumbnail_path     = $this->storage->get_image_abspath( $image_data, $size );
		$thumbnail_url      = $this->storage->get_image_url( $image_data, $size );
		$optimization_level = $this->get_optimization_level();

		// Optimize the thumbnail size.
		$response = do_imagify( $thumbnail_path, array(
			'optimization_level' => $optimization_level,
			'context'            => 'NGG',
			'keep_exif'          => true,
			'backup'             => false,
		) );

		$data = $this->fill_data( $data, $response, $size );

		/** This filter is documented in inc/3rd-party/nextgen-gallery/inc/classes/class-imagify-ngg-attachment.php. */
		$data = apply_filters( 'imagify_fill_ngg_thumbnail_data', $data, $response, $this->id, $thumbnail_path, $thumbnail_url, $size, $optimization_level );

		$this->update_row( array(
			'data' => $data,
		) );

		/**
		 * Fires after optimizing a thumbnail.
		 *
		 * @since  1.8
		 * @author Grégory Viguier
		 *
		 * @param int   $id    The image ID.
		 * @param array $data  The optimization data.
		 */
		do_action( 'after_imagify_ngg_optimize_new_thumbnail', $this->id, $data );

		$this->delete_running_status();
	}

	/**
	 * Re-optimize the given thumbnail sizes to the same level.
	 * This is not used in this context.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $sizes The sizes to optimize.
	 * @return array|void             A WP_Error object on failure.
	 */
	public function reoptimize_thumbnails( $sizes ) {}

	/**
	 * Process an attachment restoration from the backup file.
	 *
	 * @since  1.5
	 * @since  1.6.9 Doesn't use NGG's recover_image() anymore, these are fundamentally not the same things. This also prevents alt text, description, and tags deletion.
	 * @since  1.6.9 Return true or a WP_Error object.
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return bool|object True on success, a WP_Error object on error.
	 */
	public function restore() {
		// Check if the attachment extension is allowed.
		if ( ! $this->is_extension_supported() ) {
			return new WP_Error( 'mime_not_type_supported', __( 'Mime type not supported.', 'imagify' ) );
		}

		// Stop the process if there is no backup file to restore.
		if ( ! $this->has_backup() ) {
			return new WP_Error( 'no_backup', __( 'Backup image not found.', 'imagify' ) );
		}

		$image_data = $this->storage->_image_mapper->find( $this->id );

		if ( ! $image_data ) {
			return new WP_Error( 'no_image', __( 'Image not found in NextGen Gallery data.', 'imagify' ) );
		}

		/**
		 * Make some more tests before restoring the backup.
		 */
		$full_abspath   = $this->storage->get_image_abspath( $image_data );
		$backup_abspath = $this->storage->get_image_abspath( $image_data, 'backup' );

		if ( $backup_abspath === $full_abspath ) {
			return new WP_Error( 'same_path', __( 'Image path and backup path are identical.', 'imagify' ) );
		}

		if ( ! $this->filesystem->is_writable( $full_abspath ) || ! $this->filesystem->is_writable( $this->filesystem->dir_path( $full_abspath ) ) ) {
			return new WP_Error( 'destination_not_writable', __( 'The image to replace is not writable.', 'imagify' ) );
		}

		/**
		 * Fires before restoring an attachment.
		 *
		 * @since  1.5
		 * @author Jonathan Buttigieg
		 *
		 * @param int $id The attachment ID.
		 */
		do_action( 'before_imagify_ngg_restore_attachment', $this->id );

		if ( ! $this->filesystem->copy( $backup_abspath, $full_abspath, true, FS_CHMOD_FILE ) ) {
			return new WP_Error( 'copy_failed', __( 'Restoration failed.', 'imagify' ) );
		}

		/**
		 * Remove Imagify data.
		 */
		$this->delete_row();

		/**
		 * Fill in the NGG meta data.
		 */
		// 1- Meta data for the backup file.
		$dimensions  = $this->filesystem->get_image_size( $backup_abspath );
		$backup_data = array(
			'backup' => array(
				'filename'  => $this->filesystem->file_name( $full_abspath ), // Yes, $full_abspath.
				'width'     => 0,
				'height'    => 0,
				'generated' => microtime(),
			),
		);

		if ( $dimensions ) {
			$backup_data['backup']['width']  = $dimensions['width'];
			$backup_data['backup']['height'] = $dimensions['height'];
		}

		// 2- Meta data for the full sized image.
		$full_data  = array(
			'width'  => 0,
			'height' => 0,
			'md5'    => '',
			'full'   => array(
				'width'  => 0,
				'height' => 0,
				'md5'    => '',
			),
		);

		$dimensions = $this->filesystem->get_image_size( $full_abspath );

		if ( $dimensions ) {
			$full_data['width']  = $dimensions['width'];
			$full_data['height'] = $dimensions['height'];
			$full_data['full']['width']  = $dimensions['width'];
			$full_data['full']['height'] = $dimensions['height'];
		}

		$md5 = md5_file( $full_abspath );

		if ( $md5 ) {
			$full_data['md5'] = $md5;
			$full_data['full']['md5'] = $md5;
		}

		// 3- Thumbnails meta data.
		$thumbnails_data = array();

		// 4- Common meta data.
		require_once NGGALLERY_ABSPATH . '/lib/meta.php';
		$meta_obj    = new nggMeta( $image_data );
		$common_data = $meta_obj->get_common_meta();

		if ( $common_data ) {
			unset( $common_data['width'], $common_data['height'] );
		} else {
			$common_data = array(
				'aperture'          => 0,
				'credit'            => '',
				'camera'            => '',
				'caption'           => '',
				'created_timestamp' => 0,
				'copyright'         => '',
				'focal_length'      => 0,
				'iso'               => 0,
				'shutter_speed'     => 0,
				'flash'             => 0,
				'title'             => '',
				'keywords'          => '',
			);

			if ( ! empty( $image_data->meta_data ) && is_array( $image_data->meta_data ) ) {
				$image_data->meta_data = array_merge( $common_data, $image_data->meta_data );
				$common_data           = array_intersect_key( $image_data->meta_data, $common_data );
			}
		}

		$common_data['saved'] = true;

		/**
		 * Re-create non-fullsize image sizes and add related data.
		 */
		$failed = array();

		foreach ( $this->get_image_sizes() as $named_size ) {
			if ( 'full' === $named_size ) {
				continue;
			}

			$params    = $this->storage->get_image_size_params( $image_data, $named_size );
			$thumbnail = $this->storage->generate_image_clone(
				$backup_abspath,
				$this->storage->get_image_abspath( $image_data, $named_size ),
				$params
			);

			if ( ! $thumbnail ) {
				// Failed.
				$failed[] = $named_size;
				continue;
			}

			$size_meta = array(
				'width'     => 0,
				'height'    => 0,
				'filename'  => M_I18n::mb_basename( $thumbnail->fileName ),
				'generated' => microtime(),
			);

			$dimensions = $this->filesystem->get_image_size( $thumbnail->fileName );

			if ( $dimensions ) {
				$size_meta['width']  = $dimensions['width'];
				$size_meta['height'] = $dimensions['height'];
			}

			if ( isset( $params['crop_frame'] ) ) {
				$size_meta['crop_frame'] = $params['crop_frame'];
			}

			$thumbnails_data[ $named_size ] = $size_meta;
		} // End foreach().

		do_action( 'ngg_recovered_image', $image_data );

		/**
		 * Save the meta data.
		 */
		$image_data->meta_data = array_merge( $backup_data, $full_data, $thumbnails_data, $common_data );

		// Keep our property up to date.
		$this->image->_ngiw->_cache['meta_data'] = $image_data->meta_data;
		$this->image->_ngiw->_orig_image         = $image_data;

		$post_id = $this->storage->_image_mapper->save( $image_data );

		if ( ! $post_id ) {
			return new WP_Error( 'meta_data_not_saved', __( 'Related data could not be saved.', 'imagify' ) );
		}

		if ( $failed ) {
			return new WP_Error(
				'thumbnail_restore_failed',
				sprintf( _n( '%n thumbnail could not be restored.', '%n thumbnails could not be restored.', count( $failed ), 'imagify' ), count( $failed ) ),
				array( 'failed_thumbnails' => $failed )
			);
		}

		/**
		 * Fires after restoring an attachment.
		 *
		 * @since  1.5
		 * @author Jonathan Buttigieg
		 *
		 * @param int $id The attachment ID.
		 */
		do_action( 'after_imagify_ngg_restore_attachment', $this->id );

		return true;
	}

	/**
	 * Get the image sizes.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_image_sizes() {
		$sizes = array(
			'full',
		);

		// Remove common values (that have no value for us here, lol).
		$image_data = array_diff_key( $this->image->meta_data, array(
			'backup'            => 1,
			'width'             => 1,
			'height'            => 1,
			'md5'               => 1,
			'full'              => 1,
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
		) );

		if ( ! $image_data ) {
			return $sizes;
		}

		foreach ( $image_data as $size_name => $size_data ) {
			if ( isset( $size_data['width'], $size_data['height'], $size_data['filename'], $size_data['generated'] ) ) {
				$sizes[] = $size_name;
			}
		}

		return $sizes;
	}
}
