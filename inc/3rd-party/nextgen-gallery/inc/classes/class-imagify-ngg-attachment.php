<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify NextGen Gallery attachment class.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
class Imagify_NGG_Attachment extends Imagify_Attachment {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.1.1';

	/**
	 * The image object.
	 *
	 * @since 1.5
	 *
	 * @var    object A nggImage object.
	 * @access public
	 */
	public $image;

	/**
	 * The attachment SQL data row.
	 *
	 * @since 1.5
	 *
	 * @var    array
	 * @access public
	 */
	public $row;

	/**
	 * Tell if the file mime type can be optimized by Imagify.
	 *
	 * @since 1.6.9
	 *
	 * @var bool
	 * @access protected
	 * see $this->is_mime_type_supported()
	 */
	protected $is_mime_type_supported;

	/**
	 * The constructor.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @param int|object $id An image attachment ID or a NGG object.
	 * @return void
	 */
	public function __construct( $id ) {
		if ( is_object( $id ) ) {
			$this->image = $id;
			$this->id    = $id->pid;
		} else {
			$this->image = nggdb::find_image( absint( $id ) );
			$this->id    = $this->image->pid;
		}

		$this->id  = absint( $this->id );
		$this->row = $this->get_row();

		// Load nggAdmin class.
		$ngg_admin_functions_path = WP_PLUGIN_DIR . '/' . NGGFOLDER . '/products/photocrati_nextgen/modules/ngglegacy/admin/functions.php';

		if ( ! class_exists( 'nggAdmin' ) && file_exists( $ngg_admin_functions_path ) ) {
			require_once( $ngg_admin_functions_path );
		}
	}

	/**
	 * Get the attachment backup file path.
	 *
	 * @since  1.5
	 * @author Jonathan Buttigieg
	 * @access public
	 *
	 * @return string|false The file path. False if it doesn't exist.
	 */
	public function get_backup_path() {
		$file_path   = $this->get_original_path();
		$backup_path = get_imagify_ngg_attachment_backup_path( $file_path );

		if ( $backup_path && file_exists( $backup_path ) ) {
			return $backup_path;
		}

		return false;
	}

	/**
	 * Get the attachment backup URL.
	 *
	 * @since 1.6.8
	 * @author Grégory Viguier
	 *
	 * @return string|false
	 */
	public function get_backup_url() {
		return site_url( '/' ) . imagify_make_file_path_replative( $this->get_backup_path() );
	}

	/**
	 * Get the attachment optimization data.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return array|bool
	 */
	public function get_data() {
		$row = $this->row ? $this->row : $this->get_row();
		return isset( $row['data'] ) ? maybe_unserialize( $row['data'] ) : false;
	}

	/**
	 * Get the attachment optimization level.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return int|bool
	 */
	public function get_optimization_level() {
		$row = $this->row ? $this->row : $this->get_row();
		return isset( $row['optimization_level'] ) ? (int) $row['optimization_level'] : false;
	}

	/**
	 * Get the attachment SQL data row.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return array
	 */
	public function get_row() {
		return imagify_ngg_db()->get( $this->id );
	}

	/**
	 * Get the attachment optimization status (success or error).
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return string|bool
	 */
	public function get_status() {
		$row = $this->row ? $this->row : $this->get_row();
		return isset( $row['status'] ) ? $row['status'] : false;
	}

	/**
	 * Get the original attachment path.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return string
	 */
	public function get_original_path() {
		return $this->image->imagePath;
	}

	/**
	 * Get the original attachment URL.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return string
	 */
	public function get_original_url() {
		return $this->image->imageURL;
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

		$mime_type = imagify_get_mime_type_from_file( $this->get_original_path() );

		if ( ! $mime_type ) {
			$this->is_mime_type_supported = false;
			return $this->is_mime_type_supported;
		}

		$mime_types = get_imagify_mime_type();
		$mime_types = array_flip( $mime_types );

		$this->is_mime_type_supported = isset( $mime_types[ $mime_type ] );
		return $this->is_mime_type_supported;
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
		$size = @getimagesize( $this->get_original_path() );

		if ( isset( $size[0], $size[1] ) ) {
			$metadata                   = $this->image->meta_data;
			$metadata['width']          = $size[0];
			$metadata['height']         = $size[1];
			$metadata['full']['width']  = $size[0];
			$metadata['full']['height'] = $size[1];

			nggdb::update_image_meta( $this->id , $metadata );
		}
	}

	/**
	 * Fills statistics data with values from $data array.
	 *
	 * @since 1.5
	 * @since 1.6.5 Not static anymore.
	 * @since 1.6.6 Removed the attachment ID parameter.
	 * @author Jonathan Buttigieg
	 * @access public
	 *
	 * @param  array  $data      The statistics data.
	 * @param  object $response  The API response.
	 * @param  int    $url       The attachment URL.
	 * @param  string $size      The attachment size key.
	 * @return bool|array        False if the original size has an error or an array contains the data for other result.
	 */
	public function fill_data( $data, $response, $url, $size = 'full' ) {
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

				imagify_ngg_db()->update( $this->id, array(
					'pid'    => $this->id,
					'status' => $error_status,
					'data'   => serialize( $data ),
				) );

				return false;
			}
		} else {
			$response = (object) array_merge( array(
				'original_size' => 0,
				'new_size'      => 0,
				'percent'       => 0,
			), (array) $response );

			$data['sizes'][ $size ] = array(
				'success'        => true,
				'file_url'       => $url,
				'original_size'  => $response->original_size,
				'optimized_size' => $response->new_size,
				'percent'        => $response->percent,
			);

			$data['stats']['original_size']  += $response->original_size;
			$data['stats']['optimized_size'] += $response->new_size;
			$data['stats']['percent']         = round( ( ( $data['stats']['original_size'] - $data['stats']['optimized_size'] ) / $data['stats']['original_size'] ) * 100, 2 );
		} // End if().

		return $data;
	}

	/**
	 * Optimize all sizes with Imagify.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @param  int   $optimization_level  The optimization level (2=ultra, 1=aggressive, 0=normal).
	 * @param  array $metadata            The attachment meta data (not used here).
	 * @return array $data                The optimization data.
	 */
	public function optimize( $optimization_level = null, $metadata = array() ) {
		// Check if the attachment extension is allowed.
		if ( ! $this->is_mime_type_supported() ) {
			return;
		}

		$optimization_level = is_null( $optimization_level ) ? (int) get_imagify_option( 'optimization_level', 1 ) : (int) $optimization_level;

		// To avoid issue with "original_size" at 0 in "_imagify_data".
		if ( 0 === (int) $this->get_stats_data( 'original_size' ) ) {
			$this->delete_imagify_data();
		}

		// Check if the full size is already optimized.
		if ( $this->is_optimized() && $this->get_optimization_level() === $optimization_level ) {
			return;
		}

		// Get file path & URL for original image.
		$attachment_path          = $this->get_original_path();
		$attachment_url           = $this->get_original_url();
		$attachment_original_size = $this->get_original_size( false );

		/**
		 * Fires before optimizing an attachment.
		 *
		 * @since 1.5
		 *
		 * @param int $id The attachment ID
		*/
		do_action( 'before_imagify_ngg_optimize_attachment', $this->id );

		set_transient( 'imagify-ngg-async-in-progress-' . $this->id, true, 10 * MINUTE_IN_SECONDS );

		// Get the resize values for the original size.
		$resized         = false;
		$do_resize       = get_imagify_option( 'resize_larger', false );
		$resize_width    = get_imagify_option( 'resize_larger_w' );
		$attachment_size = @getimagesize( $attachment_path );

		if ( $do_resize && isset( $attachment_size[0] ) && $resize_width < $attachment_size[0] ) {
			$resized_attachment_path = $this->resize( $attachment_path, $attachment_size, $resize_width );

			if ( ! is_wp_error( $resized_attachment_path ) ) {
				// TODO (@Greg): Send an error message if the backup fails.
				imagify_backup_file( $attachment_path, $this->get_backup_path() );

				$filesystem = imagify_get_filesystem();

				$filesystem->move( $resized_attachment_path, $attachment_path, true );
				imagify_chmod_file( $attachment_path );

				// If resized temp file still exists, delete it.
				if ( $filesystem->exists( $resized_attachment_path ) ) {
					$filesystem->delete( $resized_attachment_path );
				}

				$resized = true;
			}
		}

		// Optimize the original size.
		$response = do_imagify( $attachment_path, array(
			'optimization_level' => $optimization_level,
			'context'            => 'NGG',
			'resized'            => $resized,
			'original_size'      => $attachment_original_size,
		) );

		$data = $this->fill_data( null, $response, $attachment_url );

		// Save the optimization level.
		imagify_ngg_db()->update( $this->id, array(
			'pid'                => $this->id,
			'optimization_level' => $optimization_level,
		) );

		if ( ! $data ) {
			delete_transient( 'imagify-ngg-async-in-progress-' . $this->id );
			return;
		}

		// If we resized the original with success, we have to update the attachment metadata.
		// If not, WordPress keeps the old attachment size.
		if ( $do_resize && $resized ) {
			$this->update_metadata_size();
		}

		// Optimize thumbnails.
		$data = $this->optimize_thumbnails( $optimization_level, $data );

		// Save the status to success.
		imagify_ngg_db()->update( $this->id, array(
			'pid'    => $this->id,
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
			$dimensions = getimagesize( $attachment_path );
			$md5        = md5_file( $attachment_path );

			if ( ( $dimensions || $md5 ) && ( empty( $image->meta_data['full'] ) || ! is_array( $image->meta_data['full'] ) ) ) {
				$image->meta_data['full'] = array(
					'width'  => 0,
					'height' => 0,
					'md5'    => '',
				);
			}

			if ( $dimensions ) {
				$image->meta_data['width']  = $dimensions[0];
				$image->meta_data['height'] = $dimensions[1];
				$image->meta_data['full']['width']  = $dimensions[0];
				$image->meta_data['full']['height'] = $dimensions[1];
			}

			if ( $md5 ) {
				$image->meta_data['md5'] = $md5;
				$image->meta_data['full']['md5'] = $md5;
			}

			$storage->_image_mapper->save( $image );
		}

		delete_transient( 'imagify-ngg-async-in-progress-' . $this->id );

		return $data;
	}

	/**
	 * Optimize all thumbnails of an image.
	 *
	 * @since 1.5
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

		$optimization_level = is_null( $optimization_level ) ? (int) get_imagify_option( 'optimization_level', 1 ) : (int) $optimization_level;

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
					'backup'             => false,
					'optimization_level' => $optimization_level,
					'context'            => 'wp',
				) );

				$data = $this->fill_data( $data, $response, $thumbnail_url, $size_key );

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

			imagify_ngg_db()->update( $this->id, array(
				'pid'  => $this->id,
				'data' => serialize( $data ),
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
		if ( ! $this->is_mime_type_supported() ) {
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
		$filesystem     = imagify_get_filesystem();
		$full_abspath   = $storage->get_image_abspath( $image );
		$backup_abspath = $storage->get_image_abspath( $image, 'backup' );

		if ( $backup_abspath === $full_abspath ) {
			return new WP_Error( 'same_path', __( 'Image path and backup path are identical.', 'imagify' ) );
		}

		if ( ! $filesystem->is_writable( $full_abspath ) || ! $filesystem->is_writable( dirname( $full_abspath ) ) ) {
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

		if ( ! $filesystem->copy( $backup_abspath, $full_abspath, true, FS_CHMOD_FILE ) ) {
			return new WP_Error( 'copy_failed', __( 'Restoration failed.', 'imagify' ) );
		}

		/**
		 * Remove Imagify data.
		 */
		imagify_ngg_db()->delete( $image->pid );

		/**
		 * Fill in the NGG meta data.
		 */
		// 1- Meta data for the backup file.
		$dimensions  = getimagesize( $backup_abspath );
		$backup_data = array(
			'backup' => array(
				'filename'  => basename( $full_abspath ), // Yes, $full_abspath.
				'width'     => 0,
				'height'    => 0,
				'generated' => microtime(),
			),
		);

		if ( $dimensions ) {
			$backup_data['backup']['width']  = $dimensions[0];
			$backup_data['backup']['height'] = $dimensions[1];
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

		$dimensions = getimagesize( $full_abspath );

		if ( $dimensions ) {
			$full_data['width']  = $dimensions[0];
			$full_data['height'] = $dimensions[1];
			$full_data['full']['width']  = $dimensions[0];
			$full_data['full']['height'] = $dimensions[1];
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

			$dimensions = getimagesize( $thumbnail->fileName );

			if ( $dimensions ) {
				$size_meta['width']  = $dimensions[0];
				$size_meta['height'] = $dimensions[1];
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
