<?php
namespace Imagify\Optimization\Process;

use Imagify\Deprecated\Traits\Optimization\Process\AbstractProcessDeprecatedTrait;
use Imagify\Job\MediaOptimization;
use Imagify\Optimization\Data\DataInterface;
use Imagify\Optimization\File;
use WP_Error;

/**
 * Abstract class used to optimize medias.
 *
 * @since 1.9
 */
abstract class AbstractProcess implements ProcessInterface {
	use AbstractProcessDeprecatedTrait;

	/**
	 * The suffix used in the thumbnail size name.
	 *
	 * @var   string
	 * @since 1.9
	 */
	const WEBP_SUFFIX = '@imagify-webp';

	/**
	 * The suffix used in file name to create a temporary copy of the full size.
	 *
	 * @var   string
	 * @since 1.9
	 */
	const TMP_SUFFIX = '@imagify-tmp';

	/**
	 * Used for the name of the transient telling if a media is locked.
	 * %1$s is the context, %2$s is the media ID.
	 *
	 * @var   string
	 * @since 1.9
	 */
	const LOCK_NAME = 'imagify_%1$s_%2$s_process_locked';

	/**
	 * The data optimization object.
	 *
	 * @var   DataInterface
	 * @since 1.9
	 */
	protected $data;

	/**
	 * The optimization data format.
	 *
	 * @var   array
	 * @since 1.9
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
	 * @var   File
	 * @since 1.9
	 */
	protected $file;

	/**
	 * Filesystem object.
	 *
	 * @var   Imagify_Filesystem
	 * @since 1.9
	 */
	protected $filesystem;

	/**
	 * Used to cache the plugin’s options.
	 *
	 * @var   array
	 * @since 1.9
	 */
	protected $options = [];

	/**
	 * The constructor.
	 *
	 * @since 1.9
	 * @see   self::constructor_accepts()
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
	 * @since 1.9
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
	 * @since 1.9
	 *
	 * @return DataInterface|false
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Get the media instance.
	 *
	 * @since 1.9
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
	 * Get the File instance of the original file.
	 *
	 * @since 1.9.8
	 *
	 * @return File|false
	 */
	public function get_original_file() {
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
	 * Get the File instance of the full size file.
	 *
	 * @since 1.9.8
	 *
	 * @return File|false
	 */
	public function get_fullsize_file() {
		if ( isset( $this->file ) ) {
			return $this->file;
		}

		$this->file = false;

		if ( $this->get_media() ) {
			$this->file = new File( $this->get_media()->get_raw_fullsize_path() );
		}

		return $this->file;
	}

	/**
	 * Tell if the current media is valid.
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function is_valid() {
		return $this->get_media() && $this->get_media()->is_valid();
	}

	/**
	 * Tell if the current user is allowed to operate Imagify in this context.
	 *
	 * @since 1.9
	 *
	 * @param  string $describer Capacity describer. See \Imagify\Context\ContextInterface->get_capacity() for possible values. Can also be a "real" user capacity.
	 * @return bool
	 */
	public function current_user_can( $describer ) {
		if ( ! $this->is_valid() ) {
			return false;
		}

		$media = $this->get_media();

		return $media->get_context_instance()->current_user_can( $describer, $media->get_id() );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** OPTIMIZATION ============================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Optimize a media files.
	 *
	 * @since 1.9
	 *
	 * @param  int   $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 * @param  array $args               An array of optionnal arguments.
	 * @return bool|WP_Error             True if successfully launched. A \WP_Error instance on failure.
	 */
	public function optimize( $optimization_level = null, $args = [] ) {
		if ( ! $this->is_valid() ) {
			return new WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media = $this->get_media();

		if ( ! $media->is_supported() ) {
			return new WP_Error( 'media_not_supported', __( 'This media is not supported.', 'imagify' ) );
		}

		$data = $this->get_data();

		if ( $data->is_optimized() ) {
			return new WP_Error( 'optimized', __( 'This media has already been optimized by Imagify.', 'imagify' ) );
		}

		if ( $data->is_already_optimized() && $this->has_webp() ) {
			// If already optimized but has WebP, delete WebP versions and optimization data.
			$data->delete_optimization_data();
			$deleted = $this->delete_webp_files();

			if ( is_wp_error( $deleted ) ) {
				return new WP_Error( 'webp_not_deleted', __( 'Previous WebP files could not be deleted.', 'imagify' ) );
			}
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
	 * @since 1.9
	 *
	 * @param  int   $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 * @param  array $args               An array of optionnal arguments.
	 * @return bool|WP_Error             True if successfully launched. A \WP_Error instance on failure.
	 */
	public function reoptimize( $optimization_level = null, $args = [] ) {
		if ( ! $this->is_valid() ) {
			return new WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media = $this->get_media();

		if ( ! $media->is_supported() ) {
			return new WP_Error( 'media_not_supported', __( 'This media is not supported.', 'imagify' ) );
		}

		$data = $this->get_data();

		if ( ! $data->get_optimization_status() ) {
			return new WP_Error( 'not_processed_yet', __( 'This media has not been processed yet.', 'imagify' ) );
		}

		$optimization_level = $this->sanitize_optimization_level( $optimization_level );

		if ( $data->get_optimization_level() === $optimization_level ) {
			return new WP_Error( 'identical_optimization_level', __( 'This media is already optimized with this level.', 'imagify' ) );
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
	 * @since 1.9
	 * @see   MediaOptimization->task_before()
	 * @see   MediaOptimization->task_after()
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
			return new WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media = $this->get_media();

		if ( ! $media->is_supported() ) {
			return new WP_Error( 'media_not_supported', __( 'This media is not supported.', 'imagify' ) );
		}

		if ( ! $sizes ) {
			return new WP_Error( 'no_sizes', __( 'No sizes given to be optimized.', 'imagify' ) );
		}

		if ( empty( $args['locked'] ) ) {
			if ( $this->is_locked() ) {
				return new WP_Error( 'media_locked', __( 'This media is already being processed.', 'imagify' ) );
			}

			$this->lock();
		}

		if ( $media->is_image() ) {
			if ( $this->get_option( 'convert_to_webp' ) ) {
				// Add WebP convertion.
				$files = $media->get_media_files();

				foreach ( $sizes as $size_name ) {
					if ( empty( $files[ $size_name ] ) ) {
						continue;
					}
					if ( 'image/webp' === $files[ $size_name ]['mime-type'] ) {
						continue;
					}
					if ( in_array( $size_name . static::WEBP_SUFFIX, $sizes, true ) ) {
						continue;
					}

					array_unshift( $sizes, $size_name . static::WEBP_SUFFIX );
				}
			}

			if ( ! $media->get_context_instance()->can_backup() && ! $media->get_backup_path() && ! $this->get_data()->get_size_data( 'full', 'success' ) ) {
				/**
				 * Backup is NOT activated, and a backup file does NOT exist yet, and the full size is NOT optimized yet.
				 * WebP conversion needs a backup file, even a temporary one: we’ll create one.
				 */
				$webp = false;

				foreach ( $sizes as $size_name ) {
					if ( $this->is_size_webp( $size_name ) ) {
						$webp = true;
						break;
					}
				}

				if ( $webp ) {
					// We have at least one WebP conversion to do: create a temporary backup.
					$backuped = $this->get_original_file()->backup( $media->get_raw_backup_path() );

					if ( $backuped ) {
						// See \Imagify\Job\MediaOptimization->delete_backup().
						$args['delete_backup'] = true;
					}
				}
			}
		}

		$sizes              = array_unique( $sizes );
		$optimization_level = $this->sanitize_optimization_level( $optimization_level );

		/**
		 * Filter the data sent to the optimization process.
		 *
		 * @since 1.9
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
	 * @since 1.9
	 *
	 * @param  string $size               The media size.
	 * @param  int    $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 * @return array|\WP_Error            Optimized image data. A \WP_Error object on error.
	 */
	public function optimize_size( $size, $optimization_level = null ) {
		if ( ! $this->is_valid() ) { // Bail out.
			return new WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media        = $this->get_media();
		$sizes        = $media->get_media_files();
		$thumb_size   = $size;
		$webp         = $this->is_size_webp( $size );
		$path_is_temp = false;

		if ( $webp ) {
			// We'll make sure the file is an image later.
			$thumb_size = $webp; // Contains the name of the non-WebP size.
			$webp       = true;
		}

		if ( empty( $sizes[ $thumb_size ]['path'] ) ) { // Bail out.
			// This size is not in our list.
			return new WP_Error(
				'unknown_size',
				sprintf(
					/* translators: %s is a size name. */
					__( 'The size %s is unknown.', 'imagify' ),
					'<code>' . esc_html( $thumb_size ) . '</code>'
				)
			);
		}

		if ( $this->get_data()->get_size_data( $size, 'success' ) ) { // Bail out.
			// This size is already optimized with Imagify, and must not be optimized again.
			if ( $webp ) {
				return new WP_Error(
					'size_is_successfully_optimized',
					sprintf(
						/* translators: %s is a size name. */
						__( 'The WebP format for the size %s already exists.', 'imagify' ),
						'<code>' . esc_html( $thumb_size ) . '</code>'
					)
				);
			} else {
				return new WP_Error(
					'size_is_successfully_optimized',
					sprintf(
						/* translators: %s is a size name. */
						__( 'The size %s is already optimized by Imagify.', 'imagify' ),
						'<code>' . esc_html( $thumb_size ) . '</code>'
					)
				);
			}
		}

		/**
		 * Starting from here, errors will be stored in the optimization data of the size.
		 */
		$path = $sizes[ $thumb_size ]['path'];

		$optimization_level = $this->sanitize_optimization_level( $optimization_level );

		if ( $webp && $this->get_data()->get_size_data( $thumb_size, 'success' ) ) {
			// We want a WebP version but the source file is already optimized by Imagify.
			$result = $this->create_temporary_copy( $thumb_size, $sizes );

			if ( ! $result ) { // Bail out.
				// Could not create a copy of the non-WebP version.
				$response = new WP_Error(
					'non_webp_copy_failed',
					sprintf(
						/* translators: %s is a size name. */
						__( 'Could not create an unoptimized copy of the size %s.', 'imagify' ),
						'<code>' . esc_html( $thumb_size ) . '</code>'
					)
				);

				$this->update_size_optimization_data( $response, $size, $optimization_level );

				return $response;
			}

			/**
			 * $path now targets a temporary file.
			 */
			$path         = $this->get_temporary_copy_path( $thumb_size, $sizes );
			$path_is_temp = true;
		}

		$file = new File( $path ); // Original file or temporary copy.

		if ( ! $file->is_supported( $media->get_allowed_mime_types() ) ) { // Bail out.
			// This file type is not supported.
			$extension = $file->get_extension();

			if ( '' === $extension ) {
				$response = new WP_Error(
					'no_extension',
					__( 'With no extension, this file cannot be optimized.', 'imagify' )
				);
			} else {
				$response = new WP_Error(
					'extension_not_supported',
					sprintf(
						/* translators: %s is a file extension. */
						__( '%s cannot be optimized.', 'imagify' ),
						'<code>' . esc_html( strtolower( $extension ) ) . '</code>'
					)
				);
			}

			if ( $path_is_temp ) {
				$this->filesystem->delete( $path );
			}

			$this->update_size_optimization_data( $response, $size, $optimization_level );

			return $response;
		}

		if ( $webp && ! $file->is_image() ) { // Bail out.
			if ( $path_is_temp ) {
				$this->filesystem->delete( $path );
			}

			$response = new WP_Error(
				'no_webp',
				__( 'This file is not an image and cannot be converted to WebP format.', 'imagify' )
			);

			$this->update_size_optimization_data( $response, $size, $optimization_level );

			return $response;
		}

		$is_disabled = ! empty( $sizes[ $thumb_size ]['disabled'] );

		/**
		 * Fires before optimizing a file.
		 * Return a \WP_Error object to prevent the optimization.
		 *
		 * @since 1.9
		 *
		 * @param null|WP_Error   $response           Null by default. Return a \WP_Error object to prevent optimization.
		 * @param ProcessInterface $process            The optimization process instance.
		 * @param File             $file               The file instance. If $webp is true, $file references the non-WebP file.
		 * @param string           $thumb_size         The media size.
		 * @param int              $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
		 * @param bool             $webp               The image will be converted to WebP.
		 * @param bool             $is_disabled        Tell if this size is disabled from optimization.
		 */
		$response = apply_filters( 'imagify_before_optimize_size', null, $this, $file, $thumb_size, $optimization_level, $webp, $is_disabled );

		if ( ! is_wp_error( $response ) ) {
			if ( $is_disabled ) {
				// This size must not be optimized.
				$response = new WP_Error(
					'unauthorized_size',
					sprintf(
						/* translators: %s is a size name. */
						__( 'The size %s is not authorized to be optimized. Update your Imagify settings if you want to optimize it.', 'imagify' ),
						'<code>' . esc_html( $thumb_size ) . '</code>'
					)
				);
			} elseif ( ! $this->filesystem->exists( $file->get_path() ) ) {
				$response = new WP_Error(
					'file_not_exists',
					sprintf(
						/* translators: %s is a file path. */
						__( 'The file %s does not seem to exist.', 'imagify' ),
						'<code>' . esc_html( $this->filesystem->make_path_relative( $file->get_path() ) ) . '</code>'
					)
				);
			} elseif ( $webp && ! $this->can_create_webp_version( $file->get_path() ) ) {
				$response = new WP_Error(
					'is_animated_gif',
					__( 'This file is an animated gif: since Imagify does not support animated WebP, WebP creation for animated gif is disabled.', 'imagify' )
				);
			} elseif ( ! $this->filesystem->is_writable( $file->get_path() ) ) {
				$response = new WP_Error(
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
					// Resizing succeeded: optimize the file.
					$response = $file->optimize( [
						'backup'             => ! $response['backuped'] && $this->can_backup( $size ),
						'backup_path'        => $media->get_raw_backup_path(),
						'backup_source'      => 'full' === $thumb_size ? $media->get_original_path() : null,
						'optimization_level' => $optimization_level,
						'convert'            => $webp ? 'webp' : '',
						'keep_exif'          => true,
						'context'            => $media->get_context(),
						'original_size'      => $response['file_size'],
					] );

					$response = $this->compare_webp_file_size( [
						'response'            => $response,
						'file'                => $file,
						'is_webp'             => $webp,
						'non_webp_thumb_size' => $thumb_size,
						'non_webp_file_path'  => $sizes[ $thumb_size ]['path'], // Don't use $path nor $file->get_path(), it may return the path to a temporary file.
						'optimization_level'  => $optimization_level,
					] );
				}
			}
		}

		$data = $this->update_size_optimization_data( $response, $size, $optimization_level );

		/**
		 * Fires after optimizing a file.
		 *
		 * @since 1.9
		 *
		 * @param ProcessInterface $process            The optimization process instance.
		 * @param File             $file               The file instance.
		 * @param string           $thumb_size         The media size.
		 * @param int              $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
		 * @param bool             $webp               The image was supposed to be converted to WebP.
		 * @param bool             $is_disabled        Tell if this size is disabled from optimization.
		 */
		do_action( 'imagify_after_optimize_size', $this, $file, $thumb_size, $optimization_level, $webp, $is_disabled );

		if ( ! $path_is_temp ) {
			return $data;
		}

		// Delete the temporary copy.
		$this->filesystem->delete( $path );

		if ( is_wp_error( $response ) ) {
			return $data;
		}

		// Rename the optimized file.
		$destination_path = str_replace( static::TMP_SUFFIX . '.', '.', $file->get_path() );

		$this->filesystem->move( $file->get_path(), $destination_path, true );

		return $data;
	}

	/**
	 * Compare the file size of a file and its WebP version: if the WebP version is heavier than the non-WebP file, delete it.
	 *
	 * @since 1.9.4
	 *
	 * @param  array $args {
	 *     A list of mandatory arguments.
	 *
	 *     @type \sdtClass|\WP_Error $response            Optimized image data. A \WP_Error object on error.
	 *     @type File                $file                The File instance of the file currently being optimized.
	 *     @type bool                $is_webp             Tell if we're requesting a WebP file.
	 *     @type string              $non_webp_thumb_size Name of the corresponding non-WebP thumbnail size. If we're not creating a WebP file, this corresponds to the current thumbnail size.
	 *     @type string              $non_webp_file_path  Path to the corresponding non-WebP file. If we're not creating a WebP file, this corresponds to the current file path.
	 *     @type string              $optimization_level  The optimization level.
	 * }
	 * @return \sdtClass|WP_Error                        Optimized image data. A WP_Error object on error.
	 */
	protected function compare_webp_file_size( $args ) {
		static $keep_large_webp;

		if ( ! isset( $keep_large_webp ) ) {
			/**
			 * Allow to not store WebP images that are larger than their non-WebP version.
			 *
			 * @since 1.9.4
			 *
			 * @param bool $keep_large_webp Set to false if you prefer your visitors over your Pagespeed score. Default value is true.
			 */
			$keep_large_webp = apply_filters( 'imagify_keep_large_webp', true );
		}

		if ( $keep_large_webp || is_wp_error( $args['response'] ) || ! $args['file']->is_image() ) {
			return $args['response'];
		}

		// Optimization succeeded.
		if ( $args['is_webp'] ) {
			/**
			 * We just created a WebP version:
			 * Check if it is lighter than the (maybe optimized) non-WebP file.
			 */
			$data = $this->get_data()->get_size_data( $args['non_webp_thumb_size'] );

			if ( ! $data ) {
				// We haven’t tried to optimize the non-WebP size yet.
				return $args['response'];
			}

			if ( ! empty( $data['optimized_size'] ) ) {
				// The non-WebP size is optimized, we know the file size.
				$non_webp_file_size = $data['optimized_size'];
			} else {
				// The non-WebP size is "already optimized" or "error": grab the file size directly from the file.
				$non_webp_file_size = $this->filesystem->size( $args['non_webp_file_path'] );
			}

			if ( ! $non_webp_file_size || $non_webp_file_size > $args['response']->new_size ) {
				// The new WebP file is lighter.
				return $args['response'];
			}

			// The new WebP file is heavier than the non-WebP file: delete it and return an error.
			$this->filesystem->delete( $args['file']->get_path() );

			return new WP_Error(
				'webp_heavy',
				sprintf(
					/* translators: %s is a size name. */
					__( 'The WebP version of the size %s is heavier than its non-WebP version.', 'imagify' ),
					'<code>' . esc_html( $args['non_webp_thumb_size'] ) . '</code>'
				)
			);
		}

		/**
		 * We just created a non-WebP version:
		 * Check if its WebP version file is lighter than this one.
		 */
		$webp_size      = $args['non_webp_thumb_size'] . static::WEBP_SUFFIX;
		$webp_file_size = $this->get_data()->get_size_data( $webp_size, 'optimized_size' );

		if ( ! $webp_file_size || $webp_file_size < $args['response']->new_size ) {
			// The WebP file is lighter than this one.
			return $args['response'];
		}

		// The new optimized file is lighter than the WebP file: delete the WebP file and store an error.
		$webp_path = $args['file']->get_path_to_webp();

		if ( $webp_path && $this->filesystem->is_writable( $webp_path ) ) {
			$this->filesystem->delete( $webp_path );
		}

		$webp_response = new WP_Error(
			'webp_heavy',
			sprintf(
				/* translators: %s is a size name. */
				__( 'The WebP version of the size %s is heavier than its non-WebP version.', 'imagify' ),
				'<code>' . esc_html( $args['non_webp_thumb_size'] ) . '</code>'
			)
		);

		$this->update_size_optimization_data( $webp_response, $webp_size, $args['optimization_level'] );

		return $args['response'];
	}

	/**
	 * Restore the media files from the backup file.
	 *
	 * @since 1.9
	 *
	 * @return bool|WP_Error True on success. A \WP_Error instance on failure.
	 */
	public function restore() {
		if ( ! $this->is_valid() ) {
			return new WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media = $this->get_media();

		if ( ! $media->is_supported() ) {
			return new WP_Error( 'media_not_supported', __( 'This media is not supported.', 'imagify' ) );
		}

		if ( ! $media->has_backup() ) {
			return new WP_Error( 'no_backup', __( 'This media has no backup file.', 'imagify' ) );
		}

		if ( $this->is_locked() ) {
			return new WP_Error( 'media_locked', __( 'This media is already being processed.', 'imagify' ) );
		}

		$this->lock( 'restoring' );

		$backup_path   = $media->get_backup_path();
		$original_path = $media->get_raw_original_path();

		if ( $backup_path === $original_path ) {
			// Uh?!
			$this->unlock();
			return new WP_Error( 'same_path', __( 'Image path and backup path are identical.', 'imagify' ) );
		}

		$dest_dir = $this->filesystem->dir_path( $original_path );

		if ( ! $this->filesystem->exists( $dest_dir ) ) {
			$this->filesystem->make_dir( $dest_dir );
		}

		$dest_file_is_writable = ! $this->filesystem->exists( $original_path ) || $this->filesystem->is_writable( $original_path );

		if ( ! $dest_file_is_writable || ! $this->filesystem->is_writable( $dest_dir ) ) {
			$this->unlock();
			return new WP_Error( 'destination_not_writable', __( 'The image to replace is not writable.', 'imagify' ) );
		}

		// Get some data before doing anything.
		$data  = $this->get_data()->get_optimization_data();
		$files = $media->get_media_files();

		/**
		 * Fires before restoring a media.
		 * Return a \WP_Error object to prevent the restoration.
		 *
		 * @since 1.9
		 *
		 * @param null|WP_Error   $response Null by default. Return a WP_Error object to prevent optimization.
		 * @param ProcessInterface $process  Instance of this process.
		 */
		$response = apply_filters( 'imagify_before_restore_media', null, $this );

		if ( ! is_wp_error( $response ) ) {
			// Create the original image from the backup.
			$response = $this->filesystem->copy( $backup_path, $original_path, true );

			if ( ! $response ) {
				// Failure.
				$response = new WP_Error( 'copy_failed', __( 'The backup file could not be copied over the optimized one.', 'imagify' ) );
			} else {
				// Backup successfully copied.
				$this->filesystem->chmod_file( $original_path );

				// Remove old optimization data.
				$this->get_data()->delete_optimization_data();

				if ( $media->is_image() ) {
					// Restore the original dimensions in the database.
					$media->update_dimensions();

					// Delete the WebP version.
					$this->delete_webp_file( $original_path );

					// Restore the thumbnails.
					$response = $this->restore_thumbnails();
				}
			}
		}

		/**
		 * Fires after restoring a media.
		 *
		 * @since 1.9
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
	 * @since 1.9
	 *
	 * @return bool|WP_Error True on success. A \WP_Error instance on failure.
	 */
	protected function restore_thumbnails() {
		$media = $this->get_media();

		/**
		 * Delete the WebP versions.
		 * If the full size file and the original file are not the same, the full size is considered like a thumbnail.
		 * In that case we must also delete the WebP file associated to the full size.
		 */
		$keep_full_webp = $media->get_raw_original_path() === $media->get_raw_fullsize_path();
		$this->delete_webp_files( $keep_full_webp );

		// Generate new thumbnails.
		return $media->generate_thumbnails();
	}


	/** ----------------------------------------------------------------------------------------- */
	/** BACKUP FILE ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Delete the backup file.
	 *
	 * @since 1.9
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
	/** TEMPORARY COPY OF A SIZE FILE =========================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * If we need to create a WebP version, we must create it from an unoptimized image.
	 * The full size is always optimized before the WebP version creation, and in some cases it’s the same for the thumbnails.
	 * Then we use the backup file to create temporary files.
	 */

	/**
	 * Create a temporary copy of a size file.
	 *
	 * @since 1.9
	 *
	 * @param  string $size  The image size name.
	 * @param  array  $sizes A list of thumbnail sizes being optimized.
	 * @return bool          True if the file exists/is created. False on failure.
	 */
	protected function create_temporary_copy( $size, $sizes = null ) {
		$media = $this->get_media();

		if ( ! isset( $sizes ) ) {
			$sizes = $media->get_media_files();
		}

		if ( empty( $sizes[ $size ] ) ) {
			// What?
			return false;
		}

		$tmp_path = $this->get_temporary_copy_path( $size, $sizes );

		if ( $tmp_path && $this->filesystem->exists( $tmp_path ) ) {
			// The temporary file already exists.
			return true;
		}

		$tmp_file = new File( $tmp_path );

		if ( ! $tmp_file->is_image() ) {
			// The file is not an image.
			return false;
		}

		if ( ! $tmp_file->is_supported( $media->get_allowed_mime_types() ) ) {
			// The file is not supported.
			return false;
		}

		/**
		 * Use the backup file as source.
		 */
		$backup_path = $media->get_backup_path();

		if ( ! $backup_path ) {
			// No backup, no hope for you.
			return false;
		}

		/**
		 * In all cases we must make a copy of the backup file, and not use the backup directly:
		 * sometimes the backup image does not have a valid file extension (yes I’m looking at you NextGEN Gallery).
		 */
		$copied = $this->filesystem->copy( $backup_path, $tmp_path, true );

		if ( ! $copied ) {
			return false;
		}

		if ( 'full' === $size ) {
			/**
			 * We create a copy of the backup to be able to create a WebP version from it.
			 * That means the optimization process will resize the file if needed, so there is nothing more to do here.
			 */
			return true;
		}

		// We need to create a thumbnail from it.
		$size_data     = $sizes[ $size ];
		$context_sizes = $media->get_context_instance()->get_thumbnail_sizes();

		if ( ! empty( $context_sizes[ $size ] ) ) {
			// Not a dynamic size, yay!
			$size_data = array_merge( $size_data, $context_sizes[ $size ] );
		}

		if ( empty( $size_data['path'] ) ) {
			// Should not happen.
			return false;
		}

		if ( ! isset( $size_data['crop'] ) ) {
			/**
			 * In case of a dynamic thumbnail we don’t know if the image must be croped or resized.
			 *
			 * @since 1.9
			 *
			 * @param bool           $crop      True to crop the thumbnail, false to resize. Null by default.
			 * @param string         $size      Name of the thumbnail size.
			 * @param array          $size_data Data of the thumbnail being processed. Contains at least 'width', 'height', and 'path'.
			 * @param MediaInterface $media     The MediaInterface instance corresponding to the image being processed.
			 */
			$crop = apply_filters( 'imagify_crop_thumbnail', null, $size, $size_data, $media );

			if ( null !== $crop ) {
				$size_data['crop'] = (bool) $crop;
			}
		}

		if ( ! isset( $size_data['crop'] ) ) {
			// We don't have the 'crop' data in that case: let’s try to guess it.
			if ( ! $size_data['height'] || ! $size_data['width'] ) {
				// One of the size dimensions is 0, that means crop is probably disabled.
				$size_data['crop'] = false;
			} else {
				if ( ! $this->filesystem->exists( $size_data['path'] ) ) {
					// Screwed.
					return false;
				}

				$thumb_dimensions = $this->filesystem->get_image_size( $size_data['path'] );

				if ( ! $thumb_dimensions || ! $thumb_dimensions['width'] || ! $thumb_dimensions['height'] ) {
					// ( ; Д ; )
					return false;
				}

				// Compare dimensions.
				$new_height = $thumb_dimensions['width'] * $size_data['height'] / $size_data['width'];
				// If the difference is > to 1px, let's assume that crop is enabled.
				$size_data['crop'] = abs( $thumb_dimensions['height'] - $new_height ) > 1;
			}
		}

		$resized = $tmp_file->create_thumbnail( [
			'path'            => $tmp_path,
			'width'           => $size_data['width'],
			'height'          => $size_data['height'],
			'crop'            => $size_data['crop'],
			'adjust_filename' => false,
		] );

		if ( is_wp_error( $resized ) ) {
			return false;
		}

		// Make sure the new file has the expected name.
		$new_tmp_path = $this->filesystem->dir_path( $tmp_path ) . $resized['file'];

		if ( $new_tmp_path === $tmp_path ) {
			return true;
		}

		return $this->filesystem->move( $new_tmp_path, $tmp_path, true );
	}

	/**
	 * Get the path to a temporary copy of a size file.
	 *
	 * @since 1.9
	 *
	 * @param  string $size  The image size name.
	 * @param  array  $sizes A list of thumbnail sizes being optimized.
	 * @return string|bool   An image path. False on failure.
	 */
	protected function get_temporary_copy_path( $size, $sizes = null ) {
		if ( 'full' === $size ) {
			$path = $this->get_media()->get_raw_fullsize_path();
		} else {
			if ( ! isset( $sizes ) ) {
				$sizes = $this->get_media()->get_media_files();
			}

			$path = ! empty( $sizes[ $size ]['path'] ) ? $sizes[ $size ]['path'] : false;
		}

		if ( ! $path ) {
			return false;
		}

		$info = $this->filesystem->path_info( $path );

		if ( ! $info['file_base'] ) {
			return false;
		}

		return $info['dir_path'] . $info['file_base'] . static::TMP_SUFFIX . '.' . $info['extension'];
	}


	/** ----------------------------------------------------------------------------------------- */
	/** RESIZE FILE ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Maybe resize an image.
	 *
	 * @since 1.9
	 *
	 * @param  string $size   The size name.
	 * @param  File   $file   A File instance.
	 * @return array|WP_Error A WP_Error instance on failure, an array on success as follow: {
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

		$dimensions = $file->get_dimensions();

		if ( ! $dimensions['width'] ) {
			// Could not get the image dimensions.
			return new WP_Error(
				'no_dimensions',
				sprintf(
					/* translators: %s is an error message. */
					__( 'Resizing failed: %s', 'imagify' ),
					__( 'Imagify could not get the image dimensions.', 'imagify' )
				)
			);
		}

		$media        = $this->get_media();
		$resize_width = $media->get_context_instance()->get_resizing_threshold();

		if ( $resize_width >= $dimensions['width'] ) {
			// No need to resize.
			return [
				'resized'   => false,
				'backuped'  => false,
				'file_size' => 0,
			];
		}

		$resized_path = $file->resize( $dimensions, $resize_width );

		if ( is_wp_error( $resized_path ) ) {
			// The resizement failed.
			return new WP_Error(
				'resize_failure',
				sprintf(
					/* translators: %s is an error message. */
					__( 'Resizing failed: %s', 'imagify' ),
					$resized_path->get_error_message()
				)
			);
		}

		if ( $this->can_backup( $size ) ) {
			$source   = 'full' === $size ? $media->get_original_path() : null;
			$backuped = $file->backup( $media->get_raw_backup_path(), $source );

			if ( is_wp_error( $backuped ) ) {
				// The backup failed.
				return new WP_Error(
					'backup_failure',
					sprintf(
						/* translators: %s is an error message. */
						__( 'Backup failed: %s', 'imagify' ),
						$backuped->get_error_message()
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
			return new WP_Error(
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
	 * Tell if a size should be resized.
	 *
	 * @since 1.9
	 *
	 * @param  string $size The size name.
	 * @param  File   $file A File instance.
	 * @return bool
	 */
	protected function can_resize( $size, $file ) {
		if ( ! $this->is_valid() ) {
			return false;
		}

		if ( 'full' !== $size && 'full' . static::WEBP_SUFFIX !== $size ) {
			// We resize only the main file and its WebP version.
			return false;
		}

		if ( ! $file->is_image() ) {
			return false;
		}

		return $this->get_media()->get_context_instance()->can_resize();
	}

	/**
	 * Tell if a size should be backuped.
	 *
	 * @since 1.9
	 *
	 * @param  string $size The size name.
	 * @return bool
	 */
	protected function can_backup( $size ) {
		if ( ! $this->is_valid() ) {
			return false;
		}

		if ( 'full' !== $size ) {
			// We backup only the main file.
			return false;
		}

		return $this->get_media()->get_context_instance()->can_backup();
	}


	/** ----------------------------------------------------------------------------------------- */
	/** WEBP ==================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Generate WebP images if they are missing.
	 *
	 * @since 1.9
	 *
	 * @return bool|WP_Error True if successfully launched. A \WP_Error instance on failure.
	 */
	public function generate_webp_versions() {
		if ( ! $this->is_valid() ) {
			return new WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media = $this->get_media();

		if ( ! $media->is_image() ) {
			return new WP_Error( 'no_webp', __( 'This media is not an image and cannot be converted to WebP format.', 'imagify' ) );
		}

		if ( ! $media->has_backup() ) {
			return new WP_Error( 'no_backup', __( 'This media has no backup file.', 'imagify' ) );
		}

		$data = $this->get_data();

		if ( ! $data->is_optimized() && ! $data->is_already_optimized() ) {
			return new WP_Error( 'not_optimized', __( 'This media has not been optimized by Imagify yet.', 'imagify' ) );
		}

		if ( $this->has_webp() ) {
			return new WP_Error( 'has_webp', __( 'This media already has WebP versions.', 'imagify' ) );
		}

		$files = $media->get_media_files();
		$sizes = [];
		$args  = [
			'hook_suffix' => 'generate_webp_versions',
		];

		foreach ( $files as $size_name => $file ) {
			if ( 'image/webp' !== $files[ $size_name ]['mime-type'] ) {
				array_unshift( $sizes, $size_name . static::WEBP_SUFFIX );
			}
		}

		if ( ! $sizes ) {
			return new \WP_Error( 'no_sizes', __( 'This media does not have files that can be converted to WebP format.', 'imagify' ) );
		}

		$optimization_level = $data->get_optimization_level();

		// Optimize.
		return $this->optimize_sizes( $sizes, $optimization_level, $args );
	}

	/**
	 * Delete the WebP images.
	 * This doesn't delete the related optimization data.
	 *
	 * @since 1.9
	 * @since 1.9.6 Return WP_Error or true.
	 *
	 * @param  bool $keep_full Set to true to keep the full size.
	 * @return bool|WP_Error  True on success. A \WP_Error object on failure.
	 */
	public function delete_webp_files( $keep_full = false ) {
		if ( ! $this->is_valid() ) {
			return new WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media = $this->get_media();

		if ( ! $media->is_image() ) {
			return new WP_Error( 'media_not_an_image', __( 'This media is not an image.', 'imagify' ) );
		}

		$files = $media->get_media_files();

		if ( $keep_full ) {
			unset( $files['full'] );
		}

		if ( ! $files ) {
			return true;
		}

		$error_count = 0;

		foreach ( $files as $file ) {
			if ( 0 === strpos( $file['mime-type'], 'image/' ) ) {
				$deleted = $this->delete_webp_file( $file['path'] );

				if ( is_wp_error( $deleted ) ) {
					++$error_count;
				}
			}
		}

		if ( $error_count ) {
			return new WP_Error(
				'files_not_deleted',
				sprintf(
					/* translators: %s is a formatted number, don’t use %d. */
					_n( '%s file could not be deleted.', '%s files could not be deleted.', $error_count, 'imagify' ),
					number_format_i18n( $error_count )
				)
			);
		}

		return true;
	}

	/**
	 * Delete a WebP image, given its non-WebP version's path.
	 * This doesn't delete the related optimization data.
	 *
	 * @since 1.9
	 * @since 1.9.6 Return WP_Error or true.
	 *
	 * @param  string $file_path Path to the non-WebP file.
	 * @return bool|WP_Error    True on success. A \WP_Error object on failure.
	 */
	protected function delete_webp_file( $file_path ) {
		if ( ! $file_path ) {
			return new WP_Error( 'no_path', __( 'Path to non-WebP file not provided.', 'imagify' ) );
		}

		$webp_file = new File( $file_path );
		$webp_path = $webp_file->get_path_to_webp();

		if ( ! $webp_path ) {
			return new WP_Error( 'no_webp_path', __( 'Could not get the path to the WebP file.', 'imagify' ) );
		}

		if ( ! $this->filesystem->exists( $webp_path ) ) {
			return true;
		}

		if ( ! $this->filesystem->is_writable( $webp_path ) ) {
			return new WP_Error(
				'file_not_writable',
				sprintf(
					/* translators: %s is a file path. */
					__( 'The file %s does not seem to be writable.', 'imagify' ),
					'<code>' . esc_html( $this->filesystem->make_path_relative( $webp_path ) ) . '</code>'
				)
			);
		}

		if ( ! $this->filesystem->is_file( $webp_path ) ) {
			return new WP_Error(
				'not_a_file',
				sprintf(
					/* translators: %s is a file path. */
					__( 'This does not seem to be a file: %s.', 'imagify' ),
					'<code>' . esc_html( $this->filesystem->make_path_relative( $webp_path ) ) . '</code>'
				)
			);
		}

		$deleted = $this->filesystem->delete( $webp_path, false, 'f' );

		if ( ! $deleted ) {
			return new WP_Error(
				'file_not_deleted',
				sprintf(
					/* translators: %s is a file path. */
					__( 'The file %s could not be deleted.', 'imagify' ),
					'<code>' . esc_html( $this->filesystem->make_path_relative( $webp_path ) ) . '</code>'
				)
			);
		}

		return true;
	}

	/**
	 * Tell if a thumbnail size is an "Imagify WebP" size.
	 *
	 * @since 1.9
	 *
	 * @param  string $size_name The size name.
	 * @return string|bool       The unsuffixed name of the size if WebP. False if not WebP.
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

	/**
	 * Tell if the media has WebP versions.
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function has_webp() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		if ( ! $this->get_media()->is_image() ) {
			return false;
		}

		$data = $this->get_data()->get_optimization_data();

		if ( empty( $data['sizes'] ) ) {
			return false;
		}

		$needle = static::WEBP_SUFFIX . '";a:4:{s:7:"success";b:1;';
		$data   = maybe_serialize( $data['sizes'] );

		return is_string( $data ) && strpos( $data, $needle );
	}

	/**
	 * Tell if a WebP version can be created for the given file.
	 * Make sure the file is an image before using this method.
	 *
	 * @since 1.9.5
	 *
	 * @param string $file_path Path to the file.
	 * @return bool
	 */
	public function can_create_webp_version( $file_path ) {
		if ( ! $file_path ) {
			return false;
		}

		/**
		 * Tell if a WebP version can be created for the given file.
		 * The file is an image.
		 *
		 * @since 1.9.5
		 *
		 * @param bool   $can       True to create a WebP version, false otherwise. Null by default.
		 * @param string $file_path Path to the file.
		 */
		$can = apply_filters( 'imagify_pre_can_create_webp_version', null, $file_path );

		if ( isset( $can ) ) {
			return (bool) $can;
		}

		$is_animated_gif = $this->filesystem->is_animated_gif( $file_path );

		if ( is_bool( $is_animated_gif ) ) {
			// Ok if it’s not an animated gif.
			return ! $is_animated_gif;
		}

		// At this point $is_animated_gif is null, which means the file cannot be read (yet).
		return true;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** PROCESS STATUS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if a process is running for this media.
	 *
	 * @since 1.9
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
	 * @since 1.9
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
	 * @since 1.9
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
	 * @since 1.9
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
	 * @since 1.9
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
	 * @since 1.9
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
	 * @since 1.9
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
			$data['error']  = null;

			// Size data.
			$data['success']        = true;
			$data['original_size']  = $response->original_size;
			$data['optimized_size'] = $response->new_size;
		}

		$_unauthorized = $disabled ? '_unauthorized' : '';

		/**
		 * Filter the optimization data.
		 *
		 * @since 1.9
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
		$data = (array) apply_filters( "imagify{$_unauthorized}_file_optimization_data", $data, $response, $size, $level, $this->get_data() );

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
	 * @since 1.9
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
	 * @since 1.9
	 *
	 * @param  mixed $optimization_level The optimization level.
	 * @return int
	 */
	protected function sanitize_optimization_level( $optimization_level ) {
		if ( ! is_numeric( $optimization_level ) ) {
			if ( $this->get_option( 'lossless' ) ) {
				return 0;
			}

			return $this->get_option( 'optimization_level' );
		}

		return \Imagify_Options::get_instance()->sanitize_and_validate( 'optimization_level', $optimization_level );
	}
}
