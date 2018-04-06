<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify NextGen Gallery attachment class.
 *
 * @since  1.5
 * @author Jonathan Buttigieg
 */
class Imagify_NGG_Attachment extends Imagify_Attachment {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.3.2';

	/**
	 * The attachment SQL DB class.
	 *
	 * @var    string
	 * @since  1.7
	 * @access protected
	 */
	protected $db_class_name = 'Imagify_NGG_DB';

	/**
	 * The image object.
	 *
	 * @var    object A nggImage object.
	 * @since  1.5
	 * @since  1.7 Not public anymore.
	 * @access public
	 */
	protected $image;

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
		if ( is_object( $id ) ) {
			$this->image = $id;
			$this->id    = (int) $id->pid;
		} else {
			$this->image = nggdb::find_image( absint( $id ) );
			$this->id    = ! empty( $this->image->pid ) ? (int) $this->image->pid : 0;
		}

		$this->get_row();

		$this->filesystem                   = Imagify_Filesystem::get_instance();
		$this->optimization_state_transient = 'imagify-ngg-async-in-progress-' . $this->id;

		// Load nggAdmin class.
		$ngg_admin_functions_path = WP_PLUGIN_DIR . '/' . NGGFOLDER . '/products/photocrati_nextgen/modules/ngglegacy/admin/functions.php';

		if ( ! class_exists( 'nggAdmin' ) && $this->filesystem->exists( $ngg_admin_functions_path ) ) {
			require_once( $ngg_admin_functions_path );
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
	 * Tell if the current file mime type is supported.
	 *
	 * @since  1.6.9
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_mime_type_supported() {
		if ( isset( $this->is_mime_type_supported ) ) {
			return $this->is_mime_type_supported;
		}

		$mime_type = $this->filesystem->get_mime_type( $this->get_original_path() );

		if ( ! $mime_type ) {
			$this->is_mime_type_supported = false;
			return $this->is_mime_type_supported;
		}

		$mime_types = imagify_get_mime_types();
		$mime_types = array_flip( $mime_types );

		$this->is_mime_type_supported = isset( $mime_types[ $mime_type ] );
		return $this->is_mime_type_supported;
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
			$sizes = C_Gallery_Storage::get_instance()->get_image_sizes();
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

		if ( $size ) {
			$this->image->meta_data['width']          = $size['width'];
			$this->image->meta_data['height']         = $size['height'];
			$this->image->meta_data['full']['width']  = $size['width'];
			$this->image->meta_data['full']['height'] = $size['height'];

			nggdb::update_image_meta( $this->id, $this->image->meta_data );
		}
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

		/**
		 * Fires before optimizing an attachment.
		 *
		 * @since 1.5
		 *
		 * @param int $id The attachment ID
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

		// Save the optimization level.
		$this->update_row( array(
			// The pid column is needed in case the row doesn't exist yet.
			'pid'                => $this->id,
			'optimization_level' => $optimization_level,
		) );

		if ( ! $data ) {
			// Already optimized.
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
		 * Fires after optimizing an attachment.
		 *
		 * @since 1.5
		 *
		 * @param int    $id    The attachment ID.
		 * @param array  $data  The optimization data.
		*/
		do_action( 'after_imagify_ngg_optimize_attachment', $this->id, $data );

		/**
		 * Update NGG meta data.
		 */
		$storage = C_Gallery_Storage::get_instance()->object;
		$image   = $storage->_image_mapper->find( $this->id );

		if ( $image ) {
			$dimensions = $this->filesystem->get_image_size( $attachment_path );
			$md5        = md5_file( $attachment_path );

			if ( ( $dimensions || $md5 ) && ( empty( $image->meta_data['full'] ) || ! is_array( $image->meta_data['full'] ) ) ) {
				$image->meta_data['full'] = array(
					'width'  => 0,
					'height' => 0,
					'md5'    => '',
				);
			}

			if ( $dimensions ) {
				$image->meta_data['width']  = $dimensions['width'];
				$image->meta_data['height'] = $dimensions['height'];
				$image->meta_data['full']['width']  = $dimensions['width'];
				$image->meta_data['full']['height'] = $dimensions['height'];
			}

			if ( $md5 ) {
				$image->meta_data['md5'] = $md5;
				$image->meta_data['full']['md5'] = $md5;
			}

			$storage->_image_mapper->save( $image );
		}

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
		$storage = C_Gallery_Storage::get_instance();
		$sizes   = $storage->get_image_sizes();
		$data    = $data ? $data : $this->get_data();

		// Stop if the original image has an error.
		if ( $this->has_error() ) {
			return $data;
		}

		$optimization_level = isset( $optimization_level ) ? (int) $optimization_level : get_imagify_option( 'optimization_level' );

		/**
		 * Fires before optimizing all thumbnails.
		 *
		 * @since 1.5
		 *
		 * @param int $id The image ID.
		*/
		do_action( 'before_imagify_ngg_optimize_thumbnails', $this->id );

		if ( $sizes ) {
			foreach ( $sizes as $size_key ) {
				if ( 'full' === $size_key || isset( $data['sizes'][ $size_key ]['success'] ) ) {
					continue;
				}

				$thumbnail_path = $storage->get_image_abspath( $this->image, $size_key );
				$thumbnail_url  = $storage->get_image_url( $this->image, $size_key );

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
				* @since 1.5
				*
				* @param  array  $data            The statistics data.
				* @param  object $response        The API response.
				* @param  int    $id              The attachment ID.
				* @param  string $thumbnail_path  The attachment path.
				* @param  string $thumbnail_url   The attachment URL.
				* @param  string $size_key        The attachment size key.
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
		 * @since 1.5
		 *
		 * @param int   $id    The image ID.
		 * @param array $data  The optimization data.
		*/
		do_action( 'after_imagify_ngg_optimize_thumbnails', $this->id, $data );

		return $data;
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

		$storage = C_Gallery_Storage::get_instance()->object;
		$image   = $storage->_image_mapper->find( $this->id );

		if ( ! $image ) {
			return new WP_Error( 'no_image', __( 'Image not found in NextGen Gallery data.', 'imagify' ) );
		}

		/**
		 * Make some more tests before restoring the backup.
		 */
		$full_abspath   = $storage->get_image_abspath( $image );
		$backup_abspath = $storage->get_image_abspath( $image, 'backup' );

		if ( $backup_abspath === $full_abspath ) {
			return new WP_Error( 'same_path', __( 'Image path and backup path are identical.', 'imagify' ) );
		}

		if ( ! $this->filesystem->is_writable( $full_abspath ) || ! $this->filesystem->is_writable( $this->filesystem->dir_path( $full_abspath ) ) ) {
			return new WP_Error( 'destination_not_writable', __( 'The image to replace is not writable.', 'imagify' ) );
		}

		/**
		 * Fires before restoring an attachment.
		 *
		 * @since 1.5
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
		require_once( NGGALLERY_ABSPATH . '/lib/meta.php' );
		$meta_obj    = new nggMeta( $image );
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

			if ( ! empty( $image->meta_data ) && is_array( $image->meta_data ) ) {
				$image->meta_data = array_merge( $common_data, $image->meta_data );
				$common_data      = array_intersect_key( $image->meta_data, $common_data );
			}
		}

		$common_data['saved'] = true;

		/**
		 * Re-create non-fullsize image sizes and add related data.
		 */
		$failed = array();

		foreach ( $storage->get_image_sizes( $image ) as $named_size ) {
			if ( 'full' === $named_size ) {
				continue;
			}

			$params    = $storage->get_image_size_params( $image, $named_size );
			$thumbnail = $storage->generate_image_clone(
				$backup_abspath,
				$storage->get_image_abspath( $image, $named_size ),
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

		do_action( 'ngg_recovered_image', $image );

		/**
		 * Save the meta data.
		 */
		$image->meta_data = array_merge( $backup_data, $full_data, $thumbnails_data, $common_data );

		$post_id = $storage->_image_mapper->save( $image );

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
		 * @since 1.5
		 *
		 * @param int $id The attachment ID.
		*/
		do_action( 'after_imagify_ngg_restore_attachment', $this->id );

		return true;
	}
}
