<?php
namespace Imagify\Optimization\Process;

use Imagify\Job\MediaOptimization;
use Imagify\Optimization\Data\DataInterface;
use Imagify\Optimization\File;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Abstract class used to optimize medias.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
abstract class AbstractProcess implements ProcessInterface {

	/**
	 * The suffix used in the thumbnail size name.
	 *
	 * @var    string
	 * @since  1.9
	 * @author Grégory Viguier
	 */
	const WEBP_SUFFIX = '@imagify-webp';

	/**
	 * The suffix used in file name to create a temporary copy of the full size.
	 *
	 * @var    string
	 * @since  1.9
	 * @author Grégory Viguier
	 */
	const TMP_SUFFIX = '@imagify-tmp';

	/**
	 * Used for the name of the transient telling if a media is locked.
	 * %1$s is the context, %2$s is the media ID.
	 *
	 * @var    string
	 * @since  1.9
	 * @author Grégory Viguier
	 */
	const LOCK_NAME = 'imagify_%1$s_%2$s_process_locked';

	/**
	 * The data optimization object.
	 *
	 * @var    DataInterface
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $data;

	/**
	 * The optimization data format.
	 *
	 * @var    array
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $data_format = [
		'level'          => null,
		'status'         => null,
		'success'        => null,
		'error'          => null,
		'original_size'  => null,
		'optimized_size' => null,
	];

	/**
	 * A File instance.
	 *
	 * @var    File
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $file;

	/**
	 * Filesystem object.
	 *
	 * @var    Imagify_Filesystem
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

	/**
	 * Used to cache the plugin’s options.
	 *
	 * @var    array
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $options = [];

	/**
	 * The constructor.
	 *
	 * @since  1.9
	 * @access public
	 * @see    self::constructor_accepts()
	 * @author Grégory Viguier
	 *
	 * @param mixed $id An ID, or whatever type the constructor accepts.
	 */
	public function __construct( $id ) {
		if ( $id instanceof DataInterface ) {
			$this->data = $id;
		} elseif ( static::constructor_accepts( $id ) ) {
			$data_class = str_replace( '\\Optimization\\Process\\', '\\Optimization\\Data\\', get_called_class() );
			$data_class = '\\' . ltrim( $data_class, '\\' );
			$this->data = new $data_class( $id );
		} else {
			$this->data = false;
		}

		$this->filesystem = \Imagify_Filesystem::get_instance();
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
		if ( $id instanceof DataInterface ) {
			return true;
		}

		$data_class = str_replace( '\\Optimization\\Process\\', '\\Optimization\\Data\\', get_called_class() );
		$data_class = '\\' . ltrim( $data_class, '\\' );

		return $data_class::constructor_accepts( $id );
	}

	/**
	 * Get the data instance.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return DataInterface|false
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Get the media instance.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return MediaInterface|false
	 */
	public function get_media() {
		if ( ! $this->get_data() ) {
			return false;
		}

		return $this->get_data()->get_media();
	}

	/**
	 * Get the File instance.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return File|false
	 */
	public function get_file() {
		if ( isset( $this->file ) ) {
			return $this->file;
		}

		$this->file = false;

		if ( $this->get_media() ) {
			$this->file = new File( $this->get_media()->get_raw_original_path() );
		}

		return $this->file;
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
		return $this->get_media() && $this->get_media()->is_valid();
	}


	/** ----------------------------------------------------------------------------------------- */
	/** OPTIMIZATION ============================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Optimize a media files.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int   $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 * @param  array $args               An array of optionnal arguments.
	 * @return bool|WP_Error             True if successfully launched. A \WP_Error instance on failure.
	 */
	public function optimize( $optimization_level = null, $args = [] ) {
		if ( ! $this->is_valid() ) {
			return new \WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media = $this->get_media();

		if ( ! $media->is_supported() ) {
			return new \WP_Error( 'media_not_supported', __( 'This media is not supported.', 'imagify' ) );
		}

		if ( $this->get_data()->is_optimized() ) {
			return new \WP_Error( 'optimized', __( 'This media has already been optimized by Imagify.', 'imagify' ) );
		}

		$sizes = $media->get_media_files();
		$args  = is_array( $args ) ? $args : [];

		$args['hook_suffix'] = 'optimize_media';

		// Optimize.
		return $this->optimize_sizes( array_keys( $sizes ), $optimization_level, $args );
	}

	/**
	 * Re-optimize a media files with a different level.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int   $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 * @param  array $args               An array of optionnal arguments.
	 * @return bool|WP_Error             True if successfully launched. A \WP_Error instance on failure.
	 */
	public function reoptimize( $optimization_level = null, $args = [] ) {
		if ( ! $this->is_valid() ) {
			return new \WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media = $this->get_media();

		if ( ! $media->is_supported() ) {
			return new \WP_Error( 'media_not_supported', __( 'This media is not supported.', 'imagify' ) );
		}

		$data = $this->get_data();

		if ( ! $data->get_optimization_status() ) {
			return new \WP_Error( 'not_processed_yet', __( 'This media has not been processed yet.', 'imagify' ) );
		}

		$optimization_level = $this->sanitize_optimization_level( $optimization_level );

		if ( $data->get_optimization_level() === $optimization_level ) {
			return new \WP_Error( 'identical_optimization_level', __( 'This media is already optimized with this level.', 'imagify' ) );
		}

		$this->restore();

		$sizes = $media->get_media_files();
		$args  = is_array( $args ) ? $args : [];

		$args['hook_suffix'] = 'reoptimize_media';

		// Optimize.
		return $this->optimize_sizes( array_keys( $sizes ), $optimization_level, $args );
	}

	/**
	 * Optimize several file sizes by pushing tasks into the queue.
	 *
	 * @since  1.9
	 * @access public
	 * @see    MediaOptimization->task_before()
	 * @see    MediaOptimization->task_after()
	 * @author Grégory Viguier
	 *
	 * @param  array $sizes              An array of media sizes (strings). Use "full" for the size of the main file.
	 * @param  int   $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 * @param  array $args               {
	 *     An array of optionnal arguments.
	 *
	 *     @type string $hook_suffix Suffix used to trigger hooks before and after optimization.
	 * }
	 * @return bool|WP_Error             True if successfully launched. A \WP_Error instance on failure.
	 */
	public function optimize_sizes( $sizes, $optimization_level = null, $args = [] ) {
		if ( ! $this->is_valid() ) {
			return new \WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media = $this->get_media();

		if ( ! $media->is_supported() ) {
			return new \WP_Error( 'media_not_supported', __( 'This media is not supported.', 'imagify' ) );
		}

		if ( ! $sizes ) {
			return new \WP_Error( 'no_sizes', __( 'No sizes given to be optimized.', 'imagify' ) );
		}

		if ( empty( $args['locked'] ) ) {
			if ( $this->is_locked() ) {
				return new \WP_Error( 'media_locked', __( 'This media is already being processed.', 'imagify' ) );
			}

			$this->lock();
		}

		if ( $media->is_image() ) {
			if ( $this->get_option( 'convert_to_webp' ) ) {
				$files = $media->get_media_files();

				foreach ( $sizes as $size_name ) {
					if ( empty( $files[ $size_name ] ) ) {
						continue;
					}
					if ( 'image/webp' === $files[ $size_name ]['mime-type'] ) {
						continue;
					}

					array_unshift( $sizes, $size_name . static::WEBP_SUFFIX );
				}
			}

			/**
			 * If we need to create a webp version of the full size, we must create it from an unoptimized image (if possible).
			 * Since the full size is supposed to be optimized before the webp version creation, we must either:
			 * - Create a temporary copy of the backup image if it exists.
			 * - Or, create a temporary copy of the full size before its optimization.
			 */
			$this->maybe_create_temporary_full_copy( $sizes );
		}

		$optimization_level = $this->sanitize_optimization_level( $optimization_level );

		/**
		 * Filter the data sent to the optimization process.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param array            $new_args           Additional data to send to the optimization process.
		 * @param array            $args               Current data sent to the process.
		 * @param ProcessInterface $process            The current optimization process.
		 * @param array            $sizes              Sizes being processed.
		 * @param int              $optimization_level Optimization level.
		 */
		$new_args = apply_filters( 'imagify_optimize_sizes_args', [], $args, $this, $sizes, $optimization_level );

		if ( $new_args && is_array( $new_args ) ) {
			$args = array_merge( $new_args, $args );
		}

		/**
		 * Push the item to the queue, save the queue in the DB, empty the queue.
		 * A "batch" is then created in the DB with this unique item, it is then free to loop through its steps (files) without another item interfering (each media optimization has its own dedicated batch/queue).
		 */
		MediaOptimization::get_instance()->push_to_queue( [
			'id'                 => $media->get_id(),
			'sizes'              => $sizes,
			'optimization_level' => $optimization_level,
			'process_class'      => get_class( $this ),
			'data'               => $args,
		] )->save();

		return true;
	}

	/**
	 * Optimize one file with Imagify directly.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $size               The media size.
	 * @param  int    $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 * @return array|WP_Error             The optimization data. A \WP_Error instance on failure.
	 */
	public function optimize_size( $size, $optimization_level = null ) {
		if ( ! $this->is_valid() ) {
			return new \WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media        = $this->get_media();
		$sizes        = $media->get_media_files();
		$thumb_size   = $size;
		$webp         = $this->is_size_webp( $size );
		$use_tmp_file = false;

		if ( $webp ) {
			// We'll make sure the file is an image later.
			$thumb_size = $webp;
			$webp       = true;
		}

		if ( empty( $sizes[ $thumb_size ]['path'] ) ) {
			// This size is not in our list.
			return new \WP_Error(
				'unknown_size',
				sprintf(
					/* translators: %s is a size name. */
					__( 'The size %s is unknown.', 'imagify' ),
					'<code>' . esc_html( $thumb_size ) . '</code>'
				)
			);
		}

		if ( $this->size_has_optimization_data( $size ) ) {
			// This size already has optimization data, and must not be optimized again.
			if ( $webp ) {
				return new \WP_Error(
					'size_already_has_optimization_data',
					sprintf(
						/* translators: %s is a size name. */
						__( 'The webp format for the size %s already exists.', 'imagify' ),
						'<code>' . esc_html( $thumb_size ) . '</code>'
					)
				);
			} else {
				return new \WP_Error(
					'size_already_has_optimization_data',
					sprintf(
						/* translators: %s is a size name. */
						__( 'The size %s already has optimization data.', 'imagify' ),
						'<code>' . esc_html( $thumb_size ) . '</code>'
					)
				);
			}
		}

		if ( $webp && 'full' === $thumb_size ) {
			// Webp version of the full size: maybe a temporary copy of the full size has been created.
			$tmp_path = $this->get_temporary_full_copy_path();

			if ( $this->filesystem->exists( $tmp_path ) ) {
				$use_tmp_file = true;
				$path         = $tmp_path;
			} else {
				$path = $sizes[ $thumb_size ]['path'];
			}
		} else {
			$path = $sizes[ $thumb_size ]['path'];
		}

		$file = new File( $path );

		$optimization_level = $this->sanitize_optimization_level( $optimization_level );

		if ( ! $file->is_supported( $media->get_allowed_mime_types() ) ) {
			// This file type is not supported.
			$extension = $file->get_extension();

			if ( '' === $extension ) {
				$response = new \WP_Error(
					'no_extension',
					__( 'With no extension, this file cannot be optimized.', 'imagify' )
				);
			} else {
				$response = new \WP_Error(
					'extension_not_supported',
					sprintf(
						/* translators: %s is a file extension. */
						__( '%s cannot be optimized.', 'imagify' ),
						'<code>' . esc_html( strtolower( $extension ) ) . '</code>'
					)
				);
			}

			return $this->update_size_optimization_data( $response, $size, $optimization_level );
		}

		if ( $webp && ! $file->is_image() ) {
			return new \WP_Error(
				'no_webp',
				__( 'This file is not an image and cannot be converted to webp format.', 'imagify' )
			);
		}

		$is_disabled = ! empty( $sizes[ $thumb_size ]['disabled'] );

		/**
		 * Fires before optimizing a file.
		 * Return a \WP_Error object to prevent the optimization.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param null|\WP_Error   $response           Null by default. Return a \WP_Error object to prevent optimization.
		 * @param ProcessInterface $process            The optimization process instance.
		 * @param File             $file               The file instance. If $webp is true, $file references the non-webp file.
		 * @param string           $thumb_size         The media size.
		 * @param int              $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
		 * @param bool             $webp               The image will be converted to webp.
		 * @param bool             $is_disabled        Tell if this size is disabled from optimization.
		 */
		$response = apply_filters( 'imagify_before_optimize_size', null, $this, $file, $thumb_size, $optimization_level, $webp, $is_disabled );

		if ( ! is_wp_error( $response ) ) {
			if ( $is_disabled ) {
				// This size must not be optimized.
				$response = new \WP_Error(
					'unauthorized_size',
					sprintf(
						/* translators: %s is a size name. */
						__( 'The size %s is not authorized to be optimized. Update your Imagify settings if you want to optimize it.', 'imagify' ),
						'<code>' . esc_html( $thumb_size ) . '</code>'
					)
				);
			} elseif ( ! $this->filesystem->exists( $file->get_path() ) ) {
				$response = new \WP_Error(
					'file_not_exists',
					sprintf(
						/* translators: %s is a file path. */
						__( 'The file %s does not seem to exist.', 'imagify' ),
						'<code>' . esc_html( $this->filesystem->make_path_relative( $file->get_path() ) ) . '</code>'
					)
				);
			} elseif ( ! $this->filesystem->is_writable( $file->get_path() ) ) {
				$response = new \WP_Error(
					'file_not_writable',
					sprintf(
						/* translators: %s is a file path. */
						__( 'The file %s does not seem to be writable.', 'imagify' ),
						'<code>' . esc_html( $this->filesystem->make_path_relative( $file->get_path() ) ) . '</code>'
					)
				);
			} else {
				// Maybe resize the file.
				$response = $this->maybe_resize( $thumb_size, $file );

				if ( ! is_wp_error( $response ) ) {
					// Resizement succeeded: optimize the file.
					$response = $file->optimize( [
						'backup'             => ! $response['backuped'] && $this->can_backup( $size ),
						'backup_path'        => $media->get_raw_backup_path(),
						'optimization_level' => $optimization_level,
						'convert'            => $webp ? 'webp' : '',
						'keep_exif'          => $this->can_keep_exif( $size ),
						'context'            => $media->get_context(),
						'original_size'      => $response['file_size'],
					] );
				}
			}
		}

		$data = $this->update_size_optimization_data( $response, $size, $optimization_level );

		/**
		 * Fires after optimizing a file.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param ProcessInterface $process            The optimization process instance.
		 * @param File             $file               The file instance.
		 * @param string           $thumb_size         The media size.
		 * @param int              $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
		 * @param bool             $webp               The image was supposed to be converted to webp.
		 * @param bool             $is_disabled        Tell if this size is disabled from optimization.
		 */
		do_action( 'imagify_after_optimize_size', $this, $file, $thumb_size, $optimization_level, $webp, $is_disabled );

		if ( $use_tmp_file ) {
			// Delete the temporary copy of the full size.
			$destination_path = str_replace( static::TMP_SUFFIX . '.', '.', $file->get_path() );

			$this->filesystem->move( $file->get_path(), $destination_path, true );
			$this->filesystem->delete( $tmp_path );
		}

		return $data;
	}

	/**
	 * Restore the media files from the backup file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|WP_Error True on success. A \WP_Error instance on failure.
	 */
	public function restore() {
		if ( ! $this->is_valid() ) {
			return new \WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media = $this->get_media();

		if ( ! $media->is_supported() ) {
			return new \WP_Error( 'media_not_supported', __( 'This media is not supported.', 'imagify' ) );
		}

		if ( ! $media->has_backup() ) {
			return new \WP_Error( 'no_backup', __( 'This media has no backup file.', 'imagify' ) );
		}

		if ( $this->is_locked() ) {
			return new \WP_Error( 'media_locked', __( 'This media is already being processed.', 'imagify' ) );
		}

		$this->lock( 'restoring' );

		$backup_path = $media->get_backup_path();
		$media_path  = $media->get_raw_original_path();

		if ( $backup_path === $media_path ) {
			// Uh?!
			$this->unlock();
			return new \WP_Error( 'same_path', __( 'Image path and backup path are identical.', 'imagify' ) );
		}

		$dest_dir = $this->filesystem->dir_path( $media_path );

		if ( ! $this->filesystem->exists( $dest_dir ) ) {
			$this->filesystem->make_dir( $dest_dir );
		}

		$dest_file_is_writable = ! $this->filesystem->exists( $media_path ) || $this->filesystem->is_writable( $media_path );

		if ( ! $dest_file_is_writable || ! $this->filesystem->is_writable( $dest_dir ) ) {
			$this->unlock();
			return new \WP_Error( 'destination_not_writable', __( 'The image to replace is not writable.', 'imagify' ) );
		}

		// Get some data before doing anything.
		$data  = $this->get_data()->get_optimization_data();
		$files = $media->get_media_files();

		/**
		 * Fires before restoring a media.
		 * Return a \WP_Error object to prevent the restoration.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param null|\WP_Error   $response Null by default. Return a \WP_Error object to prevent optimization.
		 * @param ProcessInterface $process  Instance of this process.
		 */
		$response = apply_filters( 'imagify_before_restore_media', null, $this );

		if ( ! is_wp_error( $response ) ) {
			// Create the original image from the backup.
			$response = $this->filesystem->copy( $backup_path, $media_path, true );

			if ( ! $response ) {
				// Failure.
				$response = new \WP_Error( 'copy_failed', __( 'The backup file could not be copied over the optimized one.', 'imagify' ) );
			} else {
				// Backup successfully copied.
				$this->filesystem->chmod_file( $media_path );

				// Remove old optimization data.
				$this->get_data()->delete_optimization_data();

				if ( $media->is_image() ) {
					// Restore the original dimensions in the database.
					$media->update_dimensions();

					// Delete the webp version.
					$this->delete_webp_file( $media_path );

					// Restore the thumbnails.
					$response = $this->restore_thumbnails();
				}
			}
		}

		/**
		 * Fires after restoring a media.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param ProcessInterface $process  Instance of this process.
		 * @param bool|WP_Error    $response The result of the operation: true on success, a WP_Error object on failure.
		 * @param array            $files    The list of files, before restoring them.
		 * @param array            $data     The optimization data, before deleting it.
		 */
		do_action( 'imagify_after_restore_media', $this, $response, $files, $data );

		$this->unlock();

		return $response;
	}

	/**
	 * Restore the thumbnails.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return bool|WP_Error True on success. A \WP_Error instance on failure.
	 */
	protected function restore_thumbnails() {
		// Delete the webp versions.
		$this->delete_webp_files( true );
		// Generate new thumbnails.
		return $this->get_media()->generate_thumbnails();
	}


	/** ----------------------------------------------------------------------------------------- */
	/** BACKUP FILE ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Delete the backup file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function delete_backup() {
		if ( ! $this->is_valid() ) {
			return;
		}

		$backup_path = $this->get_media()->get_backup_path();

		if ( $backup_path ) {
			$this->filesystem->delete( $backup_path );
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TEMPORARY COPY OF THE FULL SIZE ========================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the path to a temporary copy of the full size file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool $file_path An image path. False on failure.
	 */
	public function get_temporary_full_copy_path() {
		$path = $this->get_media()->get_raw_original_path();

		if ( ! $path ) {
			return false;
		}

		$info = $this->filesystem->path_info( $path );

		if ( ! $info['file_base'] ) {
			return false;
		}

		return $info['dir_path'] . $info['file_base'] . static::TMP_SUFFIX . '.' . $info['extension'];
	}

	/**
	 * Create a temporary copy of:
	 * - The full size if it's not optimized.
	 * - The backup image if it exists.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param array $sizes A list of thumbnail sizes being optimized.
	 */
	protected function maybe_create_temporary_full_copy( $sizes ) {
		$full_webp_size = 'full' . static::WEBP_SUFFIX;

		if ( ! in_array( $full_webp_size, $sizes, true ) ) {
			return;
		}

		$tmp_path = $this->get_temporary_full_copy_path();
		$tmp_file = new File( $tmp_path );

		if ( ! $tmp_file->is_image() ) {
			return;
		}

		$media = $this->get_media();

		if ( ! $tmp_file->is_supported( $media->get_allowed_mime_types() ) ) {
			return;
		}

		/**
		 * Try first with the backup file.
		 */
		$backup_path = $media->get_backup_path();

		if ( $backup_path ) {
			$copied = $this->filesystem->copy( $backup_path, $tmp_path, true );

			if ( $copied ) {
				$this->filesystem->chmod_file( $tmp_path );
				return;
			}
		}

		/**
		 * Try then the full size if it's not optimized yet.
		 */
		$is_optimized = $this->get_data()->get_size_data( 'full', 'success' );
		$full_path    = $media->get_original_path();

		if ( $full_path && ! $is_optimized ) {
			// The full size exists and is not optimized yet.
			$copied = $this->filesystem->copy( $full_path, $tmp_path, true );

			if ( $copied ) {
				$this->filesystem->chmod_file( $tmp_path );
				return;
			}
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** RESIZE FILE ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Maybe resize an image.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $size   The size name.
	 * @param  File   $file   A File instance.
	 * @return array|WP_Error A \WP_Error instance on failure, an array on success as follow: {
	 *     @type bool $resized   True when the image has been resized.
	 *     @type bool $backuped  True when the image has been backuped.
	 *     @type int  $file_size The file size in bytes.
	 * }
	 */
	public function maybe_resize( $size, $file ) {
		if ( ! $this->can_resize( $size, $file ) ) {
			// This file should not be resized.
			return [
				'resized'   => false,
				'backuped'  => false,
				'file_size' => 0,
			];
		}

		$media      = $this->get_media();
		$dimentions = $media->get_dimensions();

		if ( ! $dimentions['width'] ) {
			// The dimensions don't seem to be in the database anymore: try to get them directly from the file.
			$dimentions = $file->get_dimensions();
		}

		if ( ! $dimentions['width'] ) {
			// Could not get the image dimensions.
			return new \WP_Error(
				'no_dimensions',
				sprintf(
					/* translators: %s is an error message. */
					__( 'Resizement failed: %s', 'imagify' ),
					__( 'Imagify could not get the image dimensions.', 'imagify' )
				)
			);
		}

		$resize_width = $this->get_option( 'resize_larger_w' );

		if ( $resize_width >= $dimentions['width'] ) {
			// No need to resize.
			return [
				'resized'   => false,
				'backuped'  => false,
				'file_size' => 0,
			];
		}

		$resized_path = $file->resize( $dimentions, $resize_width );

		if ( is_wp_error( $resized_path ) ) {
			// The resizement failed.
			return new \WP_Error(
				'resize_failure',
				sprintf(
					/* translators: %s is an error message. */
					__( 'Resizement failed: %s', 'imagify' ),
					$resized_path->get_message()
				)
			);
		}

		if ( $this->can_backup( $size ) ) {
			$backuped = $file->backup( $media->get_raw_backup_path() );

			if ( is_wp_error( $backuped ) ) {
				// The backup failed.
				return new \WP_Error(
					'backup_failure',
					sprintf(
						/* translators: %s is an error message. */
						__( 'Backup failed: %s', 'imagify' ),
						$backuped->get_message()
					)
				);
			}
		} else {
			$backuped = false;
		}

		$file_size = (int) $this->filesystem->size( $file->get_path() );
		$resized   = $this->filesystem->move( $resized_path, $file->get_path(), true );

		if ( ! $resized ) {
			// The resizement failed.
			return new \WP_Error(
				'resize_move_failure',
				__( 'The image could not be replaced by the resized one.', 'imagify' )
			);
		}

		// Store the new dimensions.
		$media->update_dimensions();

		return [
			'resized'   => true,
			'backuped'  => $backuped,
			'file_size' => $file_size,
		];
	}

	/**
	 * Tell if a size can be resized.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $size The size name.
	 * @param  File   $file A File instance.
	 * @return bool
	 */
	abstract protected function can_resize( $size, $file );

	/**
	 * Tell if a size can be backuped.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $size The size name.
	 * @return bool
	 */
	abstract protected function can_backup( $size );

	/**
	 * Tell if a size should keep exif.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $size The size name.
	 * @return bool
	 */
	abstract protected function can_keep_exif( $size );


	/** ----------------------------------------------------------------------------------------- */
	/** WEBP ==================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Generate webp images if they are missing.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|WP_Error True if successfully launched. A \WP_Error instance on failure.
	 */
	public function generate_webp_versions() {
		if ( ! $this->is_valid() ) {
			return new \WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media = $this->get_media();

		if ( ! $media->is_image() ) {
			return new \WP_Error( 'no_webp', __( 'This media is not an image and cannot be converted to webp format.', 'imagify' ) );
		}

		if ( ! $media->has_backup() ) {
			return new \WP_Error( 'no_backup', __( 'This media has no backup file.', 'imagify' ) );
		}

		if ( ! $this->get_data()->is_optimized() ) {
			return new \WP_Error( 'not_optimized', __( 'This media has not been optimized by Imagify yet.', 'imagify' ) );
		}

		$size = 'full' . static::WEBP_SUFFIX;

		if ( $this->size_has_optimization_data( $size ) ) {
			return new \WP_Error( 'has_webp', __( 'This media already has webp versions.', 'imagify' ) );
		}

		if ( $this->is_locked() ) {
			return new \WP_Error( 'media_locked', __( 'This media is already being processed.', 'imagify' ) );
		}

		$this->lock();

		// Since the main image and the thumbnails are already optimized, we can't use them to generate the webp versions, we must restore everything before (yay!).
		$files = $media->get_media_files();

		foreach ( $files as $size_name => $file ) {
			//
		}
	}

	/**
	 * Delete the webp images.
	 * This doesn't delete the related optimization data.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param bool $keep_full Set to true to keep the full size.
	 */
	public function delete_webp_files( $keep_full = false ) {
		if ( ! $this->is_valid() ) {
			return;
		}

		$media = $this->get_media();

		if ( ! $media->is_image() ) {
			return;
		}

		$files = $media->get_media_files();

		if ( $keep_full ) {
			unset( $files['full'] );
		}

		if ( ! $files ) {
			return;
		}

		foreach ( $files as $file ) {
			if ( 0 === strpos( $file['mime-type'], 'image/' ) ) {
				$this->delete_webp_file( $file['path'] );
			}
		}
	}

	/**
	 * Delete a webp image, given its non-webp version's path.
	 * This doesn't delete the related optimization data.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param string $file_path Path to the non-webp file.
	 */
	protected function delete_webp_file( $file_path ) {
		if ( ! $file_path ) {
			return;
		}

		$webp_file = new File( $file_path );
		$webp_path = $webp_file->get_path_to_webp();

		if ( $webp_path && $this->filesystem->is_writable( $webp_path ) && $this->filesystem->is_file( $webp_path ) ) {
			$this->filesystem->delete( $webp_path, false, 'f' );
		}
	}

	/**
	 * Tell if a thumbnail size is an "Imagify webp" size.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $size_name The size name.
	 * @return string|bool       The unsuffixed name of the size if webp. False if not webp.
	 */
	public function is_size_webp( $size_name ) {
		static $suffix;

		if ( ! isset( $suffix ) ) {
			$suffix = preg_quote( static::WEBP_SUFFIX, '/' );
		}

		if ( preg_match( '/^(?<size>.+)' . $suffix . '$/', $size_name, $matches ) ) {
			return $matches['size'];
		}

		return false;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** PROCESS STATUS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if a process is running for this media.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The action if locked ('optimizing' or 'restoring'). False if not locked.
	 */
	public function is_locked() {
		$name = $this->get_lock_name();

		if ( ! $name ) {
			return false;
		}

		$callback = $this->get_media()->get_context_instance()->is_network_wide() ? 'get_site_transient' : 'get_transient';
		$action   = call_user_func( $callback, $name );

		if ( ! $action ) {
			return false;
		}

		return $this->validate_lock_action( $action );
	}

	/**
	 * Set the running status to "running" for 10 minutes.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param string $action The action performed behind this lock: 'optimizing' or 'restoring'.
	 */
	public function lock( $action = 'optimizing' ) {
		$name = $this->get_lock_name();

		if ( ! $name ) {
			return;
		}

		$action   = $this->validate_lock_action( $action );
		$media    = $this->get_media();
		$callback = $media->get_context_instance()->is_network_wide() ? 'set_site_transient' : 'set_transient';

		call_user_func( $callback, $name, $action, 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Unset the running status.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function unlock() {
		$name = $this->get_lock_name();

		if ( ! $name ) {
			return false;
		}

		$callback = $this->get_media()->get_context_instance()->is_network_wide() ? 'delete_site_transient' : 'delete_transient';

		call_user_func( $callback, $name );
	}

	/**
	 * Get the name of the transient that stores the lock status.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return string|bool The name on success. False on failure.
	 */
	protected function get_lock_name() {
		$media = $this->get_media();

		if ( ! $media ) {
			return false;
		}

		/**
		 * Note that the site transient used by WP Background is named '*_process_lock'.
		 * That would give something like 'imagify_optimize_media_process_lock' for the optimization process, while here it would be 'imagify_wp_42_process_locked'.
		 */
		return sprintf( static::LOCK_NAME, $media->get_context(), $media->get_id() );
	}

	/**
	 * Validate the lock action.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $action The action performed behind this lock: 'optimizing' or 'restoring'.
	 * @return string         The valid action.
	 */
	protected function validate_lock_action( $action ) {
		switch ( $action ) {
			case 'restore':
			case 'restoring':
				$action = 'restoring';
				break;

			default:
				$action = 'optimizing';
		}

		return $action;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** DATA ==================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if a size already has optimization data.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $size The size name.
	 * @return bool
	 */
	public function size_has_optimization_data( $size ) {
		$data = $this->get_data()->get_optimization_data();

		return ! empty( $data['sizes'][ $size ] );
	}

	/**
	 * Update the optimization data for a size.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $response The API response.
	 * @param  string $size     The size name.
	 * @param  int    $level    The optimization level (0=normal, 1=aggressive, 2=ultra).
	 * @return array            {
	 *     The optimization data.
	 *
	 *     @type int    $level          The optimization level.
	 *     @type string $status         The status: 'success', 'already_optimized', 'error'.
	 *     @type bool   $success        True if successfully optimized. False on error or if already optimized.
	 *     @type string $error          An error message.
	 *     @type int    $original_size  The weight of the file, before optimization.
	 *     @type int    $optimized_size The weight of the file, once optimized.
	 * }
	 */
	public function update_size_optimization_data( $response, $size, $level ) {
		$disabled = false;
		$data     = $this->data_format;

		$data['level'] = is_numeric( $level ) ? (int) $level : $this->get_option( 'optimization_level' );

		if ( is_wp_error( $response ) ) {
			/**
			 * Error.
			 */
			$disabled = 'unauthorized_size' === $response->get_error_code();

			// Size data.
			$data['success'] = false;
			$data['error']   = $response->get_error_message();

			// Status.
			if ( false !== strpos( $data['error'], 'This image is already compressed' ) ) {
				$data['status'] = 'already_optimized';
			} else {
				$data['status'] = 'error';
			}
		} else {
			/**
			 * Success.
			 */
			$response = (object) array_merge( [
				'original_size' => 0,
				'new_size'      => 0,
				'percent'       => 0,
			], (array) $response );

			// Status.
			$data['status'] = 'success';

			// Size data.
			$data['success']        = true;
			$data['original_size']  = $response->original_size;
			$data['optimized_size'] = $response->new_size;
		}

		$_unauthorized = $disabled ? '_unauthorized' : '';

		/**
		 * Filter the optimization data.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param array  $data       {
		 *     The optimization data.
		 *
		 *     @type int    $level          The optimization level.
		 *     @type string $status         The status: 'success', 'already_optimized', 'error'.
		 *     @type bool   $success        True if successfully optimized. False on error or if already optimized.
		 *     @type string $error          An error message.
		 *     @type int    $original_size  The weight of the file, before optimization.
		 *     @type int    $optimized_size The weight of the file, once optimized.
		 * }
		 * @param object $response   The API response.
		 * @param string $size       The size name.
		 * @param int    $level      The optimization level.
		 * @param object $media_data The DataInterface instance of the media.
		 */
		$data = apply_filters( "imagify{$_unauthorized}_file_optimization_data", $data, $response, $size, $level, $this->get_data() );

		// Store.
		$this->get_data()->update_size_optimization_data( $size, $data );

		return $data;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** VARIOUS TOOLS =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get a plugin’s option.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $option_name The option nme.
	 * @return mixed
	 */
	protected function get_option( $option_name ) {
		if ( isset( $this->options[ $option_name ] ) ) {
			return $this->options[ $option_name ];
		}

		$this->options[ $option_name ] = get_imagify_option( $option_name );

		return $this->options[ $option_name ];
	}

	/**
	 * Sanitize and validate an optimization level.
	 * If not provided (false, null), fallback to the level set in the plugin's settings.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  mixed $optimization_level The optimization level.
	 * @return int
	 */
	protected function sanitize_optimization_level( $optimization_level ) {
		if ( ! is_numeric( $optimization_level ) ) {
			return $this->get_option( 'optimization_level' );
		}

		return \Imagify_Options::get_instance()->sanitize_and_validate( 'optimization_level', $optimization_level );
	}

	/**
	 * Normalize a user capacity describer.
	 *
	 * @since  1.9
	 * @access public
	 * @see    $this->current_user_can( $describer )
	 * @author Grégory Viguier
	 *
	 * @param  string $describer Capacity describer. Possible values are 'bulk-optimize', 'manual-optimize', 'auto-optimize', 'bulk-restore', and 'manual-restore'.
	 * @return string|bool       The normalized describer. False if not in the list.
	 */
	protected function normalize_capacity_describer( $describer ) {
		if ( ! $this->is_valid() ) {
			return false;
		}

		switch ( $describer ) {
			case 'bulk-optimize':
			case 'bulk-restore':
				return 'bulk-optimize';

			case 'optimize':
			case 'restore':
			case 'manual-optimize':
			case 'manual-restore':
				return 'manual-optimize';

			case 'auto-optimize':
				return 'auto-optimize';

			default:
				return false;
		}
	}
}
