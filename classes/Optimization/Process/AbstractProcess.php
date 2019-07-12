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

	/**
	 * Tell if the current user is allowed to operate Imagify in this context.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
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
				// Add webp convertion.
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

			if ( ! $media->get_context_instance()->can_backup() && ! $media->get_backup_path() && ! $this->get_data()->get_size_data( 'full', 'success' ) ) {
				/**
				 * Backup is NOT activated, and a backup file does NOT exist yet, and the full size is NOT optimized yet.
				 * Webp conversion needs a backup file, even a temporary one: we’ll create one.
				 */
				$webp = false;

				foreach ( $sizes as $size_name ) {
					if ( $this->is_size_webp( $size_name ) ) {
						$webp = true;
						break;
					}
				}

				if ( $webp ) {
					// We have at least one webp conversion to do: create a temporary backup.
					$backuped = $this->get_file()->backup( $media->get_raw_backup_path() );

					if ( $backuped ) {
						// See \Imagify\Job\MediaOptimization->delete_backup().
						$args['delete_backup'] = true;
					}
				}
			}
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
			'sizes'              => array_unique( $sizes ),
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
	 * @return \sdtClass|\WP_Error        Optimized image data. A \WP_Error object on error.
	 */
	public function optimize_size( $size, $optimization_level = null ) {
		if ( ! $this->is_valid() ) { // Bail out.
			return new \WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$media        = $this->get_media();
		$sizes        = $media->get_media_files();
		$thumb_size   = $size;
		$webp         = $this->is_size_webp( $size );
		$path_is_temp = false;

		if ( $webp ) {
			// We'll make sure the file is an image later.
			$thumb_size = $webp; // Contains the name of the non-webp size.
			$webp       = true;
		}

		if ( empty( $sizes[ $thumb_size ]['path'] ) ) { // Bail out.
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

		if ( $this->get_data()->get_size_data( $size, 'success' ) ) { // Bail out.
			// This size is already optimized with Imagify, and must not be optimized again.
			if ( $webp ) {
				return new \WP_Error(
					'size_is_successfully_optimized',
					sprintf(
						/* translators: %s is a size name. */
						__( 'The webp format for the size %s already exists.', 'imagify' ),
						'<code>' . esc_html( $thumb_size ) . '</code>'
					)
				);
			} else {
				return new \WP_Error(
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
			// We want a webp version but the source file is already optimized by Imagify.
			$result = $this->create_temporary_copy( $thumb_size, $sizes );

			if ( ! $result ) { // Bail out.
				// Could not create a copy of the non-webp version.
				$response = new \WP_Error(
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

			$response = new \WP_Error(
				'no_webp',
				__( 'This file is not an image and cannot be converted to webp format.', 'imagify' )
			);

			$this->update_size_optimization_data( $response, $size, $optimization_level );

			return $response;
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
			} elseif ( $webp && ! $this->can_create_webp_version( $file->get_path() ) ) {
				$response = new \WP_Error(
					'is_animated_gif',
					__( 'This file is an animated gif: since Imagify does not support animated webp, webp creation for animated gif is disabled.', 'imagify' )
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
					// Resizing succeeded: optimize the file.
					$response = $file->optimize( [
						'backup'             => ! $response['backuped'] && $this->can_backup( $size ),
						'backup_path'        => $media->get_raw_backup_path(),
						'optimization_level' => $optimization_level,
						'convert'            => $webp ? 'webp' : '',
						'keep_exif'          => $this->can_keep_exif( $size ),
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
	 * Compare the file size of a file and its webp version: if the webp version is heavier than the non-webp file, delete it.
	 *
	 * @since  1.9.4
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  array $args {
	 *     A list of mandatory arguments.
	 *
	 *     @type \sdtClass|\WP_Error $response            Optimized image data. A \WP_Error object on error.
	 *     @type File                $file                The File instance of the file currently being optimized.
	 *     @type bool                $is_webp             Tell if we're requesting a webp file.
	 *     @type string              $non_webp_thumb_size Name of the corresponding non-webp thumbnail size. If we're not creating a webp file, this corresponds to the current thumbnail size.
	 *     @type string              $non_webp_file_path  Path to the corresponding non-webp file. If we're not creating a webp file, this corresponds to the current file path.
	 *     @type string              $optimization_level  The optimization level.
	 * }
	 * @return \sdtClass|\WP_Error                        Optimized image data. A \WP_Error object on error.
	 */
	protected function compare_webp_file_size( $args ) {
		static $keep_large_webp;

		if ( ! isset( $keep_large_webp ) ) {
			/**
			 * Allow to not store webp images that are larger than their non-webp version.
			 *
			 * @since  1.9.4
			 * @author Grégory Viguier
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
			 * We just created a webp version:
			 * Check if it is lighter than the (maybe optimized) non-webp file.
			 */
			$data = $this->get_data()->get_size_data( $args['non_webp_thumb_size'] );

			if ( ! $data ) {
				// We haven’t tried to optimize the non-webp size yet.
				return $args['response'];
			}

			if ( ! empty( $data['optimized_size'] ) ) {
				// The non-webp size is optimized, we know the file size.
				$non_webp_file_size = $data['optimized_size'];
			} else {
				// The non-webp size is "already optimized" or "error": grab the file size directly from the file.
				$non_webp_file_size = $this->filesystem->size( $args['non_webp_file_path'] );
			}

			if ( ! $non_webp_file_size || $non_webp_file_size > $args['response']->new_size ) {
				// The new webp file is lighter.
				return $args['response'];
			}

			// The new webp file is heavier than the non-webp file: delete it and return an error.
			$this->filesystem->delete( $args['file']->get_path() );

			return new \WP_Error(
				'webp_heavy',
				sprintf(
					/* translators: %s is a size name. */
					__( 'The webp version of the size %s is heavier than its non-webp version.', 'imagify' ),
					'<code>' . esc_html( $args['non_webp_thumb_size'] ) . '</code>'
				)
			);
		}

		/**
		 * We just created a non-webp version:
		 * Check if its webp version file is lighter than this one.
		 */
		$webp_size      = $args['non_webp_thumb_size'] . static::WEBP_SUFFIX;
		$webp_file_size = $this->get_data()->get_size_data( $webp_size, 'optimized_size' );

		if ( ! $webp_file_size || $webp_file_size < $args['response']->new_size ) {
			// The webp file is lighter than this one.
			return $args['response'];
		}

		// The new optimized file is lighter than the webp file: delete the webp file and store an error.
		$webp_path = $args['file']->get_path_to_webp();

		if ( $webp_path && $this->filesystem->is_writable( $webp_path ) ) {
			$this->filesystem->delete( $webp_path );
		}

		$webp_response = new \WP_Error(
			'webp_heavy',
			sprintf(
				/* translators: %s is a size name. */
				__( 'The webp version of the size %s is heavier than its non-webp version.', 'imagify' ),
				'<code>' . esc_html( $args['non_webp_thumb_size'] ) . '</code>'
			)
		);

		$this->update_size_optimization_data( $webp_response, $webp_size, $args['optimization_level'] );

		return $args['response'];
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
	/** TEMPORARY COPY OF A SIZE FILE =========================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * If we need to create a webp version, we must create it from an unoptimized image.
	 * The full size is always optimized before the webp version creation, and in some cases it’s the same for the thumbnails.
	 * Then we use the backup file to create temporary files.
	 */

	/**
	 * Create a temporary copy of a size file.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
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
			 * We create a copy of the backup to be able to create a webp version from it.
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
			 * @since  1.9
			 * @author Grégory Viguier
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
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $size  The image size name.
	 * @param  array  $sizes A list of thumbnail sizes being optimized.
	 * @return string|bool   An image path. False on failure.
	 */
	protected function get_temporary_copy_path( $size, $sizes = null ) {
		if ( 'full' === $size ) {
			$path = $this->get_media()->get_raw_original_path();
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
		$dimensions = $media->get_dimensions();

		if ( ! $dimensions['width'] ) {
			// The dimensions don't seem to be in the database anymore: try to get them directly from the file.
			$dimensions = $file->get_dimensions();
		}

		if ( ! $dimensions['width'] ) {
			// Could not get the image dimensions.
			return new \WP_Error(
				'no_dimensions',
				sprintf(
					/* translators: %s is an error message. */
					__( 'Resizing failed: %s', 'imagify' ),
					__( 'Imagify could not get the image dimensions.', 'imagify' )
				)
			);
		}

		$resize_width = $this->get_option( 'resize_larger_w' );

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
			return new \WP_Error(
				'resize_failure',
				sprintf(
					/* translators: %s is an error message. */
					__( 'Resizing failed: %s', 'imagify' ),
					$resized_path->get_error_message()
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
	 * Tell if a size should be resized.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
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
			// We resize only the main file and its webp version.
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
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
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
	protected function can_keep_exif( $size ) {
		if ( ! $this->is_valid() ) {
			return false;
		}

		if ( 'full' !== $size && 'full' . static::WEBP_SUFFIX !== $size ) {
			// We keep exif only on the main file and its webp version.
			return false;
		}

		return $this->get_media()->get_context_instance()->can_keep_exif();
	}


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

		$data = $this->get_data();

		if ( ! $data->is_optimized() ) {
			return new \WP_Error( 'not_optimized', __( 'This media has not been optimized by Imagify yet.', 'imagify' ) );
		}

		if ( $this->has_webp() ) {
			return new \WP_Error( 'has_webp', __( 'This media already has webp versions.', 'imagify' ) );
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
			return new \WP_Error( 'no_sizes', __( 'This media does not have files that can be converted to webp format.', 'imagify' ) );
		}

		$optimization_level = $data->get_optimization_level();

		// Optimize.
		return $this->optimize_sizes( $sizes, $optimization_level, $args );
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

	/**
	 * Tell if the media has webp versions.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
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
	 * Tell if a webp version can be created for the given file.
	 * Make sure the file is an image before using this method.
	 *
	 * @since  1.9.5
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param string $file_path Path to the file.
	 * @return bool
	 */
	public function can_create_webp_version( $file_path ) {
		if ( ! $file_path ) {
			return false;
		}

		/**
		 * Tell if a webp version can be created for the given file.
		 * The file is an image.
		 *
		 * @since  1.9.5
		 * @author Grégory Viguier
		 *
		 * @param bool   $can       True to create a webp version, false otherwise. Null by default.
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
}
