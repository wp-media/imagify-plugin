<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles all the main tools for compatibility with WP Retina 2x plugin.
 *
 * @since  1.8
 * @author Grégory Viguier
 */
class Imagify_WP_Retina_2x_Core {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.8
	 * @author Grégory Viguier
	 */
	const VERSION = '1.1';

	/**
	 * Filesystem object.
	 *
	 * @var    object Imagify_Filesystem
	 * @since  1.8
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

	/**
	 * Used to store methods that should not run for a time being.
	 *
	 * @var    array
	 * @since  1.8
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected static $prevented = array();

	/**
	 * The constructor.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 */
	public function __construct() {
		$this->filesystem = Imagify_Filesystem::get_instance();
	}


	/** ----------------------------------------------------------------------------------------- */
	/** GENERATE RETINA IMAGES ================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Generate retina images (except full size), and optimize them if the non-retina images are.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @return bool|object        True on success, false if prevented, a WP_Error object on failure.
	 */
	public function generate_retina_images( $attachment ) {
		$tests = $this->validate( __FUNCTION__, $attachment );

		if ( true !== $tests ) {
			return $tests;
		}

		// Backup the optimized full-sized image and replace it by the original backup file, so it can be used to create new retina images.
		$this->backup_optimized_file( $attachment );

		if ( ! $this->filesystem->exists( $attachment->get_original_path() ) ) {
			return new WP_Error( 'file_missing', 'The main file does not exist.' );
		}

		// Create retina images.
		wr2x_generate_images( wp_get_attachment_metadata( $attachment->get_id() ) );

		// Put the optimized full-sized file back.
		$this->put_optimized_file_back( $attachment );

		/**
		 * If the non-retina images are optimized by Imagify (or at least the user wanted it to be optimized at some point, and now has a "already optimized" or "error" status), optimize newly created retina files.
		 * If the retina version of the full size exists and is not optimized yet, it will be processed as well.
		 */
		if ( $attachment->is_optimized() && $this->can_auto_optimize() ) {
			$this->optimize_retina_images( $attachment );
		}

		return true;
	}

	/**
	 * Delete previous retina images and recreate them (except full size), and optimize them if they previously were.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param object $attachment An Imagify attachment.
	 * @return bool|object        True on success, false if prevented, a WP_Error object on failure.
	 */
	public function regenerate_retina_images( $attachment ) {
		$tests = $this->validate( __FUNCTION__, $attachment );

		if ( true !== $tests ) {
			return $tests;
		}

		// Delete the retina files and remove retina sizes from Imagify data.
		$result = $this->delete_retina_images( $attachment );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Create new retina files (and optimize them if they previously were).
		return $this->generate_retina_images( $attachment );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** DELETE RETINA IMAGES ==================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Delete the retina images. Also removes the related Imagify data.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param object $attachment        An Imagify attachment.
	 * @param bool   $delete_full_image True to also delete the retina version of the full size.
	 * @return bool|object              True on success, false if prevented, a WP_Error object on failure.
	 */
	public function delete_retina_images( $attachment, $delete_full_image = false ) {
		$tests = $this->validate( __FUNCTION__, $attachment, array(
			'metadata_dimensions' => 'error',
		) );

		if ( true !== $tests ) {
			return $tests;
		}

		/**
		 * To be a bit faster we update the data at once at the end.
		 *
		 * @see Imagify_WP_Retina_2x::remove_retina_thumbnail_data_hook().
		 */
		$this->prevent( 'remove_retina_image_data_by_filename' );

		// Delete the retina thumbnails.
		wr2x_delete_attachment( $attachment->get_id(), $delete_full_image );

		$this->allow( 'remove_retina_image_data_by_filename' );

		// Remove retina sizes from Imagify data.
		$this->remove_retina_images_data( $attachment, $delete_full_image );

		return true;
	}

	/**
	 * Delete the retina version of the full size.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @return bool|object        True on success, false if prevented, a WP_Error object on failure.
	 */
	public function delete_full_retina_image( $attachment ) {
		$tests = $this->validate( __FUNCTION__, $attachment, array(
			'metadata_file'  => false,
			'metadata_sizes' => false,
		) );

		if ( true !== $tests ) {
			return $tests;
		}

		$retina_path = wr2x_get_retina( $attachment->get_original_path() );

		if ( $retina_path ) {
			// The file exists.
			$this->filesystem->delete( $retina_path );
		}

		// Delete related Imagify data.
		return $this->remove_size_from_imagify_data( $attachment, 'full@2x' );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** REPLACE IMAGES ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Replace an attachment (except the retina version of the full size).
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @param  string $file_path  Path to the new file.
	 * @return bool|object        True on success, false if prevented, a WP_Error object on failure.
	 */
	public function replace_attachment( $attachment, $file_path ) {
		$tests = $this->validate( __FUNCTION__, $attachment, array(
			'metadata_sizes' => false,
		) );

		if ( true !== $tests ) {
			return $tests;
		}

		$attachment_id = $attachment->get_id();
		$sizes         = $this->get_attachment_sizes( $attachment );
		$original_path = $attachment->get_original_path();
		$dir_path      = $this->filesystem->path_info( $original_path, 'dir_path' );

		// Insert the new file (and overwrite the full size).
		$moved = $this->filesystem->move( $file_path, $original_path, true );

		if ( ! $moved ) {
			return new WP_Error( 'not_writable', __( 'Replacement failed.', 'imagify' ) );
		}

		// Delete retina images.
		$this->delete_retina_images( $attachment );

		// Delete the non-retina images.
		if ( $sizes ) {
			foreach ( $sizes as $name => $attr ) {
				$size_path = $dir_path . $attr['file'];

				if ( $this->filesystem->exists( $size_path ) && $this->filesystem->is_file( $size_path ) ) {
					// If the deletion fails,  we're screwed anyway since the main file has been deleted, so no need to return an error here.
					$this->filesystem->delete( $size_path );
				}
			}
		}

		// Get some Imagify data before deleting everything.
		$optimization_level    = $this->get_optimization_level( $attachment );
		$full_retina_data      = $attachment->get_data();
		$full_retina_data      = ! empty( $full_retina_data['sizes']['full@2x'] ) ? $full_retina_data['sizes']['full@2x'] : false;
		$full_retina_optimized = $full_retina_data && ! empty( $full_retina_data['success'] );

		// Delete the Imagify data.
		$attachment->delete_imagify_data();

		// Delete the backup file.
		$attachment->delete_backup();

		// Prevent auto-optimization.
		Imagify_Auto_Optimization::prevent_optimization( $attachment_id );

		// Generate the non-retina images and the related WP metadata.
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $original_path ) );

		// Allow auto-optimization back.
		Imagify_Auto_Optimization::allow_optimization( $attachment_id );

		// Generate retina images (since the Imagify data has been deleted, the images won't be optimized here).
		$result = $this->generate_retina_images( $attachment );

		if ( is_wp_error( $result ) ) {
			if ( $full_retina_optimized ) {
				// The retina version of the full size is optimized: restore it overwise the user may optimize it again some day.
				$this->restore_full_retina_file( $attachment );
			}

			return $result;
		}

		if ( $this->can_auto_optimize() ) {
			if ( $full_retina_optimized ) {
				// Don't optimize the retina full size, it already is.
				remove_filter( 'imagify_fill_full_size_data', array( Imagify_WP_Retina_2x::get_instance(), 'optimize_full_retina_version_hook' ) );
			}

			/**
			 * Optimize everyone.
			 *
			 * @see Imagify_WP_Retina_2x::optimize_full_retina_version_hook()
			 * @see Imagify_WP_Retina_2x::optimize_retina_version_hook()
			 * @see Imagify_WP_Retina_2x::maybe_optimize_unauthorized_retina_version_hook().
			 */
			$attachment->optimize( $optimization_level );

			if ( $full_retina_optimized ) {
				add_filter( 'imagify_fill_full_size_data', array( Imagify_WP_Retina_2x::get_instance(), 'optimize_full_retina_version_hook' ), 10, 8 );

				if ( $attachment->is_optimized() ) {
					// Put data back.
					$data = $attachment->get_data();
					$data['sizes']['full@2x'] = $full_retina_data;
					update_post_meta( $attachment_id, '_imagify_data', $data );
				} else {
					$this->restore_full_retina_file( $attachment );
				}
			}
		} elseif ( $full_retina_optimized ) {
			// The retina version of the full size is optimized: restore it overwise the user may optimize it again some day.
			$this->restore_full_retina_file( $attachment );
		}

		return true;
	}

	/**
	 * Replace an attachment (except the retina version of the full size).
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @param  string $file_path  Path to the new file.
	 * @return bool|object        True on success, false if prevented, a WP_Error object on failure.
	 */
	public function replace_full_retina_image( $attachment, $file_path ) {
		$tests = $this->validate( __FUNCTION__, $attachment, array(
			'metadata_file'  => false,
			'metadata_sizes' => false,
		) );

		if ( true !== $tests ) {
			return $tests;
		}

		// Replace the file.
		$retina_path = $this->get_retina_path( $attachment->get_original_path() );
		$moved       = $this->filesystem->move( $file_path, $retina_path, true );

		if ( ! $moved ) {
			return new WP_Error( 'not_writable', __( 'Replacement failed.', 'imagify' ) );
		}

		// Delete related Imagify data.
		$this->remove_size_from_imagify_data( $attachment, 'full@2x' );

		// Delete previous backup file.
		$result = $this->delete_file_backup( $retina_path );

		if ( is_wp_error( $result ) ) {
			$this->filesystem->delete( $file_path );
			return $result;
		}

		// Optimize.
		if ( $attachment->is_optimized() && $this->can_auto_optimize() ) {
			return $this->optimize_full_retina_image( $attachment );
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** OPTIMIZE RETINA IMAGES ================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Optimize retina images.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment         An Imagify attachment.
	 * @param  bool   $optimize_full_size False to not optimize the retina version of the full size.
	 * @return bool|object                True on success, false if prevented or not supported or no sizes, a WP_Error object on failure.
	 */
	public function optimize_retina_images( $attachment, $optimize_full_size = true ) {
		$tests = $this->validate( __FUNCTION__, $attachment, array(
			'supported'           => true,
			'can_optimize'        => 'error',
			'metadata_dimensions' => 'error',
		) );

		if ( true !== $tests ) {
			return $tests;
		}

		$metadata = wp_get_attachment_metadata( $attachment->get_id() );

		if ( $optimize_full_size ) {
			$metadata['sizes']['full'] = array(
				'file'      => $this->filesystem->file_name( $metadata['file'] ),
				'width'     => (int) $metadata['width'],
				'height'    => (int) $metadata['height'],
				'mime-type' => get_post_mime_type( $attachment->get_id() ),
			);
		}

		return $this->optimize_retina_sizes( $attachment, $metadata['sizes'] );
	}

	/**
	 * Optimize the full size retina image.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @return bool|object        True on success, false if prevented or not supported or no sizes, a WP_Error object on failure.
	 */
	public function optimize_full_retina_image( $attachment ) {
		$tests = $this->validate( __FUNCTION__, $attachment, array(
			'supported'           => true,
			'can_optimize'        => 'error',
			'metadata_dimensions' => 'error',
		) );

		if ( true !== $tests ) {
			return $tests;
		}

		$metadata = wp_get_attachment_metadata( $attachment->get_id() );

		$sizes = array(
			'full' => array(
				'file'      => $this->filesystem->file_name( $metadata['file'] ),
				'width'     => (int) $metadata['width'],
				'height'    => (int) $metadata['height'],
				'mime-type' => get_post_mime_type( $attachment->get_id() ),
			),
		);

		return $this->optimize_retina_sizes( $attachment, $sizes );
	}

	/**
	 * Optimize the given retina images.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @param  array  $sizes      A list of non-retina sizes, formatted like in wp_get_attachment_metadata().
	 * @return bool|object        True on success, false if prevented or not supported or no sizes, a WP_Error object on failure.
	 */
	public function optimize_retina_sizes( $attachment, $sizes ) {
		$tests = $this->validate( __FUNCTION__, $attachment, array(
			'supported'           => true,
			'can_optimize'        => 'error',
			'metadata_dimensions' => 'error',
		) );

		if ( true !== $tests ) {
			return $tests;
		}

		$attachment_id      = $attachment->get_id();
		$optimization_level = $this->get_optimization_level( $attachment );

		/**
		 * Filter the retina thumbnail sizes to optimize for a given attachment. This includes the sizes disabled in Imagify’ settings.
		 *
		 * @since  1.8
		 * @author Grégory Viguier
		 *
		 * @param array $sizes              An array of non-retina thumbnail sizes.
		 * @param int   $attachment_id      The attachment ID.
		 * @param int   $optimization_level The optimization level.
		 */
		$sizes = apply_filters( 'imagify_attachment_retina_sizes', $sizes, $attachment_id, $optimization_level );

		if ( ! $sizes || ! is_array( $sizes ) ) {
			return false;
		}

		$original_dirpath = $this->filesystem->dir_path( $attachment->get_original_path() );

		foreach ( $sizes as $size_key => $image_data ) {
			$retina_path = wr2x_get_retina( $original_dirpath . $image_data['file'] );

			if ( ! $retina_path ) {
				unset( $sizes[ $size_key ] );
				continue;
			}

			// The file exists.
			$sizes[ $size_key ]['retina-path'] = $retina_path;
		}

		if ( ! $sizes ) {
			return false;
		}

		$attachment->set_running_status();

		/**
		 * Fires before optimizing the retina images.
		 *
		 * @since  1.8
		 * @author Grégory Viguier
		 *
		 * @param int   $attachment_id      The attachment ID.
		 * @param array $sizes              An array of non-retina thumbnail sizes.
		 * @param int   $optimization_level The optimization level.
		 */
		do_action( 'before_imagify_optimize_retina_images', $attachment_id, $sizes, $optimization_level );

		$imagify_data = $attachment->get_data();

		foreach ( $sizes as $size_key => $image_data ) {
			$imagify_data = $this->optimize_retina_image( array(
				'data'               => $imagify_data,
				'attachment'         => $attachment,
				'retina_path'        => $image_data['retina-path'],
				'size_key'           => $size_key,
				'optimization_level' => $optimization_level,
			) );
		}

		$this->update_imagify_data( $attachment, $imagify_data );

		/**
		 * Fires after optimizing the retina images.
		 *
		 * @since  1.8
		 * @author Grégory Viguier
		 *
		 * @param int   $attachment_id      The attachment ID.
		 * @param array $sizes              An array of non-retina thumbnail sizes.
		 * @param int   $optimization_level The optimization level.
		 */
		do_action( 'after_imagify_optimize_retina_images', $attachment_id, $sizes, $optimization_level );

		$attachment->delete_running_status();

		return true;
	}

	/**
	 * Optimize the retina version of an image.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $args {
	 *     An array of required arguments.
	 *
	 *     @type array  $data               The statistics data.
	 *     @type object $attachment         An Imagify attachment.
	 *     @type string $retina_path        The path to the retina file.
	 *     @type string $size_key           The attachment size key (without '@2x').
	 *     @type int    $optimization_level The optimization level. Optionnal.
	 *     @type array  $metadata           WP metadata. If omitted, wp_get_attachment_metadata() will be used.
	 * }
	 * @return array The new optimization data.
	 */
	public function optimize_retina_image( $args ) {
		static $backup;

		$args = array_merge( array(
			'data'               => array(),
			'attachment'         => false,
			'retina_path'        => '',
			'size_key'           => '',
			'optimization_level' => false,
			'metadata'           => array(),
		), $args );

		if ( $this->is_prevented( __FUNCTION__ ) || ! $args['retina_path'] || $this->has_filesystem_error() ) {
			return $args['data'];
		}

		$retina_key = $args['size_key'] . '@2x';

		if ( isset( $args['data'][ $retina_key ] ) ) {
			// Don't optimize something that already is.
			return $args['data'];
		}

		$disallowed = $this->size_is_disallowed( $args['size_key'] );
		$do_retina  = ! $disallowed;
		/**
		 * Allow to optimize the retina version generated by WP Retina x2.
		 *
		 * @since 1.0
		 * @since 1.8 Added $args parameter.
		 *
		 * @param bool   $do_retina True will allow the optimization. False to prevent it.
		 * @param string $args      The arguments passed to the method.
		 */
		$do_retina = apply_filters( 'do_imagify_optimize_retina', $do_retina, $args );

		if ( ! $do_retina ) {
			if ( $disallowed ) {
				$message = __( 'This size is not authorized to be optimized. Update your Imagify settings if you want to optimize it.', 'imagify' );
			} else {
				$message = __( 'This size optimization has been prevented by a filter.', 'imagify' );
			}

			$args['data']['sizes'][ $retina_key ] = array(
				'success' => false,
				'error'   => $message,
			);
			return $args['data'];
		}

		if ( ! $args['metadata'] || ! is_array( $args['metadata'] ) ) {
			$args['metadata'] = wp_get_attachment_metadata( $args['attachment']->get_id() );
		}

		$is_a_copy = $this->size_is_a_full_copy( array(
			'size_name'    => $args['size_key'],
			'metadata'     => $args['metadata'],
			'imagify_data' => $args['data'],
			'retina_path'  => $args['retina_path'],
		) );

		if ( $is_a_copy ) {
			// This thumbnail is a copy of the full size image, which is already optimized.
			$args['data']['sizes'][ $retina_key ] = $args['data']['sizes']['full'];

			if ( isset( $args['data']['sizes']['full']['original_size'], $args['data']['sizes']['full']['optimized_size'] ) ) {
				// Concistancy only.
				$args['data']['stats']['original_size']  += $args['data']['sizes']['full']['original_size'];
				$args['data']['stats']['optimized_size'] += $args['data']['sizes']['full']['optimized_size'];
			}

			return $args['data'];
		}

		if ( ! is_int( $args['optimization_level'] ) ) {
			$args['optimization_level'] = get_imagify_option( 'optimization_level' );
		}

		// Hammer time.
		$response = do_imagify( $args['retina_path'], array(
			// Backup only if it's the full size.
			'backup'             => 'full' === $args['size_key'],
			'optimization_level' => $args['optimization_level'],
			'context'            => 'wp-retina',
		) );

		return $args['attachment']->fill_data( $args['data'], $response, $retina_key );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HANDLE BACKUPS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Backup a retina file.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Path to the file.
	 * @return bool|object       True on success, false if prevented or no need for backup, a WP_Error object on failure.
	 */
	public function backup_file( $file_path ) {
		static $backup;

		if ( $this->is_prevented( __FUNCTION__ ) ) {
			return false;
		}

		if ( ! isset( $backup ) ) {
			$backup = get_imagify_option( 'backup' );
		}

		if ( ! $backup ) {
			return false;
		}

		if ( $this->has_filesystem_error() ) {
			return new WP_Error( 'filesystem', __( 'Filesystem error.', 'imagify' ) );
		}

		$upload_basedir = get_imagify_upload_basedir();

		if ( ! $upload_basedir ) {
			$file_path = make_path_relative( $file_path );

			/* translators: %s is a file path. */
			return new WP_Error( 'upload_basedir', sprintf( __( 'The file %s could not be backed up. Image optimization aborted.', 'imagify' ), '<code>' . esc_html( $file_path ) . '</code>' ) );
		}

		$file_path   = wp_normalize_path( $file_path );
		$backup_dir  = get_imagify_backup_dir_path();
		$backup_path = str_replace( $upload_basedir, $backup_dir, $file_path );

		if ( $this->filesystem->exists( $backup_path ) ) {
			$this->filesystem->delete( $backup_path );
		}

		$backup_result = imagify_backup_file( $file_path, $backup_path );

		if ( is_wp_error( $backup_result ) ) {
			return $backup_result;
		}

		return true;
	}

	/**
	 * Delete a retina file backup.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Path to the file.
	 * @return bool|object       True on success, false if the file doesn't exist, a WP_Error object on failure.
	 */
	public function delete_file_backup( $file_path ) {
		$tests = $this->validate( __FUNCTION__ );

		if ( true !== $tests ) {
			return $tests;
		}

		$upload_basedir = get_imagify_upload_basedir();

		if ( ! $upload_basedir ) {
			$file_path = make_path_relative( $file_path );

			/* translators: %s is a file path. */
			return new WP_Error( 'upload_basedir', sprintf( __( 'Previous backup file for %s could not be deleted.', 'imagify' ), '<code>' . esc_html( $file_path ) . '</code>' ) );
		}

		$file_path   = wp_normalize_path( $file_path );
		$backup_dir  = get_imagify_backup_dir_path();
		$backup_path = str_replace( $upload_basedir, $backup_dir, $file_path );

		if ( ! $this->filesystem->exists( $backup_path ) ) {
			return false;
		}

		$result = $this->filesystem->delete( $backup_path );

		if ( ! $result ) {
			$file_path = make_path_relative( $file_path );

			/* translators: %s is a file path. */
			return new WP_Error( 'not_deleted', sprintf( __( 'Previous backup file for %s could not be deleted.', 'imagify' ), '<code>' . esc_html( $file_path ) . '</code>' ) );
		}

		return true;
	}

	/**
	 * Restore the retina version of the full size.
	 * This doesn't remove the Imagify data.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @return bool|object        True on success, false if prevented or backup doesn't exist, a WP_Error object on failure.
	 */
	public function restore_full_retina_file( $attachment ) {
		$tests = $this->validate( __FUNCTION__, $attachment, array(
			'metadata_file'  => false,
			'metadata_sizes' => false,
		) );

		if ( true !== $tests ) {
			return $tests;
		}

		$has_backup = $this->full_retina_has_backup( $attachment );

		if ( is_wp_error( $has_backup ) ) {
			return $has_backup;
		}

		if ( ! $has_backup ) {
			return new WP_Error( 'no_backup', __( 'The retina version of the full size of this image does not have backup.', 'imagify' ) );
		}

		$file_path   = $this->get_retina_path( $attachment->get_original_path() );
		$backup_path = $this->get_full_retina_backup_path( $attachment );

		/**
		 * Fires before restoring the retina version of the full size.
		 *
		 * @since  1.8
		 * @author Grégory Viguier
		 *
		 * @param string $backup_path Path to the backup file.
		 * @param string $file_path   Path to the source file.
		*/
		do_action( 'before_imagify_restore_full_retina_file', $backup_path, $file_path );

		// Save disc space by moving it instead of copying it.
		$moved = $this->filesystem->move( $backup_path, $file_path, true );

		/**
		 * Fires after restoring the retina version of the full size.
		 *
		 * @since  1.8
		 * @author Grégory Viguier
		 *
		 * @param string $backup_path Path to the backup file.
		 * @param string $file_path   Path to the source file.
		 * @param bool   $moved       Restore success.
		*/
		do_action( 'after_imagify_restore_full_retina_file', $backup_path, $file_path, $moved );

		if ( ! $moved ) {
			return new WP_Error( 'upload_basedir', __( 'Backup of the retina version of the full size image could not be restored.', 'imagify' ) );
		}

		return true;
	}

	/**
	 * Get the path to the retina version of the full size.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @return string|object      The path on success, a WP_Error object on failure.
	 */
	public function get_full_retina_backup_path( $attachment ) {
		$file_path   = $this->get_retina_path( $attachment->get_original_path() );
		$backup_path = get_imagify_attachment_backup_path( $file_path );

		if ( ! $backup_path ) {
			return new WP_Error( 'upload_basedir', __( 'Could not retrieve the path to the backup of the retina version of the full size image.', 'imagify' ) );
		}

		return $backup_path;
	}

	/**
	 * Tell if the retina version of the full size has a backup.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @return bool|object        A WP_Error object on failure.
	 */
	public function full_retina_has_backup( $attachment ) {
		$backup_path = $this->get_full_retina_backup_path( $attachment );

		if ( is_wp_error( $backup_path ) ) {
			return $backup_path;
		}

		return $this->filesystem->exists( $backup_path );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HANDLE IMAGIFY DATA ===================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Remove retina versions from Imagify data.
	 * It also rebuilds the attachment stats.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param object $attachment       An Imagify attachment.
	 * @param bool   $remove_full_size True to also remove the full size data.
	 */
	public function remove_retina_images_data( $attachment, $remove_full_size = false ) {
		if ( $this->is_prevented( __FUNCTION__ ) ) {
			return;
		}

		$imagify_data = $attachment->get_data();

		if ( empty( $imagify_data['sizes'] ) || ! is_array( $imagify_data['sizes'] ) ) {
			return;
		}

		$sizes = $this->get_attachment_sizes( $attachment );

		if ( ! $sizes ) {
			return;
		}

		$update = false;

		if ( $remove_full_size && isset( $imagify_data['sizes']['full@2x'] ) ) {
			unset( $imagify_data['sizes']['full@2x'] );
			$update = true;
		}

		foreach ( $sizes as $size => $attr ) {
			$size .= '@2x';

			if ( isset( $imagify_data['sizes'][ $size ] ) ) {
				unset( $imagify_data['sizes'][ $size ] );
				$update = true;
			}
		}

		if ( ! $update ) {
			return;
		}

		$this->update_imagify_data( $attachment, $imagify_data );
	}

	/**
	 * Remove a retina thumbnail from attachment's Imagify data, given the retina file name.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param object $attachment      An Imagify attachment.
	 * @param string $retina_filename Retina thumbnail file name.
	 */
	public function remove_retina_image_data_by_filename( $attachment, $retina_filename ) {
		if ( $this->is_prevented( __FUNCTION__ ) ) {
			return;
		}

		$imagify_data = $attachment->get_data();

		if ( empty( $imagify_data['sizes'] ) || ! is_array( $imagify_data['sizes'] ) ) {
			return;
		}

		$sizes = $this->get_attachment_sizes( $attachment );

		if ( ! $sizes ) {
			return;
		}

		$image_filename = str_replace( $this->get_suffix(), '.', $retina_filename );
		$size           = false;

		foreach ( $sizes as $name => $attr ) {
			if ( $image_filename === $attr['file'] ) {
				$size = $name;
				break;
			}
		}

		if ( ! $size || ! isset( $imagify_data['sizes'][ $size ] ) ) {
			return;
		}

		unset( $imagify_data['sizes'][ $size ] );

		$this->update_imagify_data( $attachment, $imagify_data );
	}

	/**
	 * Rebuild the attachment stats and store the data.
	 * Delete all Imagify data if the sizes are empty.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment   An Imagify attachment.
	 * @param  array  $imagify_data Imagify data.
	 * @return bool                 True on update, false on delete or prevented.
	 */
	public function update_imagify_data( $attachment, $imagify_data ) {
		if ( $this->is_prevented( __FUNCTION__ ) ) {
			return false;
		}

		if ( empty( $imagify_data['sizes'] ) || ! is_array( $imagify_data['sizes'] ) ) {
			// No new sizes.
			$attachment->delete_imagify_data();
			return false;
		}

		$imagify_data['stats'] = array(
			'original_size'  => 0,
			'optimized_size' => 0,
			'percent'        => 0,
		);

		foreach ( $imagify_data['sizes'] as $size_data ) {
			$imagify_data['stats']['original_size']  += ! empty( $size_data['original_size'] )  ? $size_data['original_size']  : 0;
			$imagify_data['stats']['optimized_size'] += ! empty( $size_data['optimized_size'] ) ? $size_data['optimized_size'] : 0;
		}

		if ( $imagify_data['stats']['original_size'] && $imagify_data['stats']['optimized_size'] ) {
			$imagify_data['stats']['percent'] = round( ( ( $imagify_data['stats']['original_size'] - $imagify_data['stats']['optimized_size'] ) / $imagify_data['stats']['original_size'] ) * 100, 2 );
		}

		update_post_meta( $attachment->get_id(), '_imagify_data', $imagify_data );

		return true;
	}

	/**
	 * Remove a size from Imagify data.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param object $attachment An Imagify attachment.
	 * @param string $size_name  Name of the size.
	 */
	public function remove_size_from_imagify_data( $attachment, $size_name ) {
		if ( $this->is_prevented( __FUNCTION__ ) ) {
			return;
		}

		$imagify_data = $attachment->get_data();

		if ( ! isset( $imagify_data['sizes'][ $size_name ] ) ) {
			return;
		}

		unset( $imagify_data['sizes'][ $size_name ] );

		$this->update_imagify_data( $attachment, $imagify_data );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** INTERNAL TOOLS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if a file extension is supported by WP Retina 2x.
	 * It uses $wr2x_core->is_supported_image() if available.
	 *
	 * @since  1.8
	 * @access public
	 * @see    $wr2x_core->is_supported_image()
	 * @author Grégory Viguier
	 *
	 * @param  string|int $file_path Path to the file or attachment ID.
	 * @return bool
	 */
	public function is_supported_format( $file_path ) {
		global $wr2x_core;
		static $method;
		static $results = array();

		if ( ! $file_path ) {
			return false;
		}

		if ( isset( $results[ $file_path ] ) ) {
			// $file_path can be a path or an attachment ID.
			return $results[ $file_path ];
		}

		if ( is_int( $file_path ) ) {
			$attachment_id = $file_path;
			$file_path     = get_attached_file( $attachment_id );

			if ( ! $file_path ) {
				$results[ $attachment_id ] = false;
				return false;
			}

			if ( isset( $results[ $file_path ] ) ) {
				// $file_path is now a path for sure.
				$results[ $attachment_id ] = $results[ $file_path ];
				return $results[ $file_path ];
			}
		}

		if ( ! isset( $method ) ) {
			if ( $wr2x_core && is_object( $wr2x_core ) && method_exists( $wr2x_core, 'is_supported_image' ) ) {
				$method = array( $wr2x_core, 'is_supported_image' );
			} else {
				$method = array( $this, 'is_supported_extension' );
			}
		}

		// $file_path is now a path for sure.
		$results[ $file_path ] = call_user_func( $method, $file_path );

		if ( ! empty( $attachment_id ) ) {
			$results[ $attachment_id ] = $results[ $file_path ];
		}

		return $results[ $file_path ];
	}

	/**
	 * Tell if a file extension is supported by WP Retina 2x.
	 * Internal version of $wr2x_core->is_supported_image().
	 *
	 * @since  1.8
	 * @access public
	 * @see    $this->is_supported_format()
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Path to a file.
	 * @return bool
	 */
	protected function is_supported_extension( $file_path ) {
		$extension  = strtolower( $this->filesystem->path_info( $file_path, 'extension' ) );
		$extensions = array(
			'jpg'  => 1,
			'jpeg' => 1,
			'png'  => 1,
			'gif'  => 1,
		);

		return isset( $extensions[ $extension ] );
	}

	/**
	 * Get the path to the retina version of an image.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Path to the non-retina image.
	 * @return string
	 */
	public function get_retina_path( $file_path ) {
		$path_info = $this->filesystem->path_info( $file_path );
		$suffix    = rtrim( $this->get_suffix(), '.' );
		$extension = isset( $path_info['extension'] ) ? '.' . $path_info['extension'] : '';

		return $path_info['dir_path'] . $path_info['file_base'] . $suffix . $extension;
	}

	/**
	 * Tell if the attchment has at least one retina image.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @return bool
	 */
	public function has_retina_images( $attachment ) {
		$dir_path      = $this->filesystem->path_info( $attachment->get_original_path(), 'dir_path' );
		$metadata      = wp_get_attachment_metadata( $attachment->get_id() );
		$sizes         = ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ? $metadata['sizes'] : array();
		$sizes['full'] = array(
			'file' => $this->filesystem->file_name( $metadata['file'] ),
		);

		foreach ( $sizes as $name => $attr ) {
			$size_path = $this->get_retina_path( $dir_path . $attr['file'] );

			if ( $this->filesystem->exists( $size_path ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Prevent a method to do its job.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param string $method_name Name of the method to prevent.
	 */
	public function prevent( $method_name ) {
		if ( empty( self::$prevented[ $method_name ] ) || self::$prevented[ $method_name ] < 1 ) {
			self::$prevented[ $method_name ] = 1;
		} else {
			++self::$prevented[ $method_name ];
		}
	}

	/**
	 * Allow a method to do its job.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param string $method_name Name of the method to allow.
	 */
	public function allow( $method_name ) {
		if ( empty( self::$prevented[ $method_name ] ) || self::$prevented[ $method_name ] <= 1 ) {
			unset( self::$prevented[ $method_name ] );
		} else {
			--self::$prevented[ $method_name ];
		}
	}

	/**
	 * Tell if a method is prevented to do its job.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $method_name Name of the method.
	 * @return bool
	 */
	public function is_prevented( $method_name ) {
		return ! empty( self::$prevented[ $method_name ] );
	}

	/**
	 * Tell if a thumbnail size is disallowed for optimization..
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $size_name The size name.
	 * @return bool
	 */
	public function size_is_disallowed( $size_name ) {
		static $disallowed_sizes;

		if ( imagify_is_active_for_network() ) {
			return false;
		}

		if ( ! isset( $disallowed_sizes ) ) {
			$disallowed_sizes = get_imagify_option( 'disallowed-sizes' );
		}

		return isset( $disallowed_sizes[ $size_name ] );
	}

	/**
	 * Tell if a thumbnail file is a copy of the full size image. Will return false if the full size is not optimized.
	 * Make sure both files exist before using this.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $args {
	 *     An array of arguments.
	 *
	 *     @type string $size_name    The size name. Required.
	 *     @type array  $metadata     WP metadata. Required.
	 *     @type array  $imagify_data Imagify data. Required.
	 *     @type string $retina_path  Path to the image we're testing. Required.
	 *     @type string $full_path    Path to the full size image. Optional but should be provided.
	 * }
	 * @return bool
	 */
	public function size_is_a_full_copy( $args ) {
		$size_name    = $args['size_name'];
		$metadata     = $args['metadata'];
		$imagify_data = $args['imagify_data'];

		if ( empty( $imagify_data['sizes']['full'] ) ) {
			// The full size is not optimized, so there is no point in checking if the given file is a copy.
			return false;
		}

		if ( ! isset( $metadata['width'], $metadata['height'], $metadata['file'] ) ) {
			return false;
		}

		if ( ! isset( $metadata['sizes'][ $size_name ]['width'], $metadata['sizes'][ $size_name ]['height'] ) ) {
			return false;
		}

		$size = $metadata['sizes'][ $size_name ];

		if ( $size['width'] * 2 !== $metadata['width'] || $size['height'] * 2 !== $metadata['height'] ) {
			// The full size image doesn't have the right dimensions.
			return false;
		}

		if ( empty( $args['full_path'] ) ) {
			$dir_path          = $this->filesystem->path_info( $args['retina_path'], 'dir_path' );
			$args['full_path'] = $dir_path . $metadata['file'];
		}

		$full_hash   = md5_file( $args['full_path'] );
		$retina_hash = md5_file( $args['retina_path'] );

		return hash_equals( $full_hash, $retina_hash );
	}

	/**
	 * Tell if there is a filesystem error.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function has_filesystem_error() {
		return ! empty( $this->filesystem->errors->errors );
	}

	/**
	 * Do few tests: method is not prevented, attachment is valid, filesystem has no error.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $method     The name of the method using this.
	 * @param  object $attachment An Imagify attachment.
	 * @param  array  $args       A list of additional tests.
	 * @return bool|object        True if ok, false if prevented, a WP_Error object on failure.
	 */
	public function validate( $method, $attachment = false, $args = array() ) {
		$args = array_merge( array(
			'supported'           => false,
			'can_optimize'        => false,
			'metadata_dimensions' => false,
			'metadata_file'       => $attachment ? 'error' : false,
			'metadata_sizes'      => $attachment ? 'error' : false,
		), $args );

		if ( $this->is_prevented( $method ) ) {
			return false;
		}

		if ( $attachment && ! $attachment->is_valid() ) {
			return new WP_Error( 'invalid_attachment', __( 'Invalid attachment.', 'imagify' ) );
		}

		if ( $args['supported'] && ! $attachment->is_extension_supported() ) {
			if ( 'error' !== $args['supported'] ) {
				return false;
			}

			return new WP_Error( 'mime_type_not_supported', __( 'This type of file is not supported.', 'imagify' ) );
		}

		if ( $args['can_optimize'] ) {
			if ( 'error' !== $args['can_optimize'] ) {
				if ( ! Imagify_Requirements::is_api_key_valid() || Imagify_Requirements::is_over_quota() ) {
					return false;
				}
			} else {
				if ( ! Imagify_Requirements::is_api_key_valid() ) {
					return new WP_Error( 'invalid_api_key', __( 'Your API key is not valid!', 'imagify' ) );
				}
				if ( Imagify_Requirements::is_over_quota() ) {
					return new WP_Error( 'over_quota', __( 'You have used all your credits!', 'imagify' ) );
				}
			}
		}

		if ( $this->has_filesystem_error() ) {
			return new WP_Error( 'filesystem', __( 'Filesystem error.', 'imagify' ) );
		}

		if ( $args['metadata_dimensions'] ) {
			$metadata = wp_get_attachment_metadata( $attachment->get_id() );

			if ( empty( $metadata['width'] ) || empty( $metadata['height'] ) ) {
				if ( 'error' !== $args['metadata_sizes'] ) {
					return false;
				}

				return new WP_Error( 'metadata_dimensions', __( 'This attachment lacks the required metadata.', 'imagify' ) );
			}
		}

		if ( $args['metadata_file'] ) {
			$metadata = isset( $metadata ) ? $metadata : wp_get_attachment_metadata( $attachment->get_id() );

			if ( empty( $metadata['file'] ) ) {
				if ( 'error' !== $args['metadata_file'] ) {
					return false;
				}

				return new WP_Error( 'metadata_file', __( 'This attachment lacks the required metadata.', 'imagify' ) );
			}
		}

		if ( $args['metadata_sizes'] ) {
			$metadata = isset( $metadata ) ? $metadata : wp_get_attachment_metadata( $attachment->get_id() );

			if ( empty( $metadata['sizes'] ) || ! is_array( $metadata['sizes'] ) ) {
				if ( 'error' !== $args['metadata_sizes'] ) {
					return false;
				}

				return new WP_Error( 'metadata_sizes', __( 'This attachment has no registered thumbnail sizes.', 'imagify' ) );
			}
		}

		return true;
	}

	/**
	 * Tell if Imagify can optimize the files.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function can_optimize() {
		return ! $this->has_filesystem_error() && Imagify_Requirements::is_api_key_valid() && ! Imagify_Requirements::is_over_quota();
	}

	/**
	 * Tell if Imagify can auto-optimize the files.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function can_auto_optimize() {
		return $this->can_optimize() && get_imagify_option( 'auto_optimize' );
	}

	/**
	 * Get thumbnail sizes from an attachment.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @return array
	 */
	public function get_attachment_sizes( $attachment ) {
		$metadata = wp_get_attachment_metadata( $attachment->get_id() );
		return ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ? $metadata['sizes'] : array();
	}

	/**
	 * Get the optimization level used to optimize the given attachment.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @return int                The attachment optimization level. The default level if not optimized.
	 */
	public function get_optimization_level( $attachment ) {
		static $default;

		if ( $attachment->get_status() ) {
			$level = $attachment->get_optimization_level();

			if ( is_int( $level ) ) {
				return $level;
			}
		}

		if ( ! isset( $default ) ) {
			$default = get_imagify_option( 'optimization_level' );
		}

		return $default;
	}

	/**
	 * Get the path to the temporary file.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path The optimized full-sized file path.
	 * @return string
	 */
	public function get_temporary_file_path( $file_path ) {
		return $file_path . '_backup';
	}

	/**
	 * Backup the optimized full-sized file and replace it by the original backup file.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param object $attachment An Imagify attachment.
	 */
	public function backup_optimized_file( $attachment ) {
		if ( $this->is_prevented( __FUNCTION__ ) ) {
			return;
		}

		$backup_path = $attachment->get_backup_path();

		if ( ! $backup_path || ! $attachment->is_optimized() ) {
			return;
		}

		/**
		 * Replace the optimized full-sized file by the backup, so any optimization will not use an optimized file, but the original one.
		 * The optimized full-sized file is kept and renamed, and will be put back in place at the end of the optimization process.
		 */
		$file_path     = $attachment->get_original_path();
		$tmp_file_path = $this->get_temporary_file_path( $file_path );

		if ( $this->filesystem->exists( $file_path ) ) {
			$this->filesystem->move( $file_path, $tmp_file_path, true );
		}

		$copied = $this->filesystem->copy( $backup_path, $file_path );

		if ( ! $copied ) {
			// Uh ho...
			$this->filesystem->move( $tmp_file_path, $file_path, true );
			return;
		}

		// Make sure the dimensions are in sync in post meta.
		$this->maybe_update_image_dimensions( $attachment, $file_path );
	}

	/**
	 * Put the optimized full-sized file back.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param object $attachment An Imagify attachment.
	 */
	public function put_optimized_file_back( $attachment ) {
		if ( $this->is_prevented( __FUNCTION__ ) ) {
			return;
		}

		$file_path     = $attachment->get_original_path();
		$tmp_file_path = $this->get_temporary_file_path( $file_path );

		if ( ! $this->filesystem->exists( $tmp_file_path ) ) {
			return;
		}

		$moved = $this->filesystem->move( $tmp_file_path, $file_path, true );

		if ( ! $moved ) {
			// Uh ho...
			return;
		}

		// Make sure the dimensions are in sync in post meta.
		$this->maybe_update_image_dimensions( $attachment, $file_path );
	}

	/**
	 * Make sure the dimensions are in sync in post meta.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @param  string $file_path  Path to the file.
	 * @return bool               True when updated.
	 */
	public function maybe_update_image_dimensions( $attachment, $file_path ) {
		if ( $this->is_prevented( __FUNCTION__ ) ) {
			return false;
		}

		$metadata   = wp_get_attachment_metadata( $attachment->get_id() );
		$width      = ! empty( $metadata['width'] )  ? (int) $metadata['width']  : 0;
		$height     = ! empty( $metadata['height'] ) ? (int) $metadata['height'] : 0;
		$dimensions = $this->filesystem->get_image_size( $file_path );

		if ( ! $dimensions ) {
			return false;
		}

		if ( $width === $dimensions['width'] && $height === $dimensions['height'] ) {
			return false;
		}

		$metadata['width']  = $dimensions['width'];
		$metadata['height'] = $dimensions['height'];

		// Prevent auto-optimization.
		Imagify_Auto_Optimization::prevent_optimization( $attachment_id );

		wp_update_attachment_metadata( $attachment->get_id(), $metadata );

		// Allow auto-optimization back.
		Imagify_Auto_Optimization::allow_optimization( $attachment_id );
		return true;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** WR2X COMPAT' TOOLS ====================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the suffix added to the file name, with a trailing dot.
	 * Don't use it for the size name.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_suffix() {
		global $wr2x_core;
		static $suffix;

		if ( ! isset( $suffix ) ) {
			$suffix = $wr2x_core && is_object( $wr2x_core ) && method_exists( $wr2x_core, 'retina_extension' ) ? $wr2x_core->retina_extension() : '@2x.';
		}

		return $suffix;
	}

	/**
	 * Get info about retina version.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @param  string $type       The type of info. Possible values are 'basic' and 'full' (for the full size).
	 * @return array              An array containing some HTML, indexed by the attachment ID.
	 */
	public function get_retina_info( $attachment, $type = 'basic' ) {
		global $wr2x_core;
		static $can_get_info;

		if ( ! isset( $can_get_info ) ) {
			$can_get_info = $wr2x_core && is_object( $wr2x_core ) && method_exists( $wr2x_core, 'retina_info' ) && method_exists( $wr2x_core, 'html_get_basic_retina_info_full' ) && method_exists( $wr2x_core, 'html_get_basic_retina_info' );
		}

		if ( ! $can_get_info ) {
			return '';
		}

		$attachment_id = $attachment->get_id();
		$info          = $wr2x_core->retina_info( $attachment_id );

		if ( 'full' === $type ) {
			return array(
				$attachment_id => $wr2x_core->html_get_basic_retina_info_full( $attachment_id, $info ),
			);
		}

		return array(
			$attachment_id => $wr2x_core->html_get_basic_retina_info( $attachment_id, $info ),
		);
	}

	/**
	 * Log.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param string $text Text to log.
	 */
	public function log( $text ) {
		global $wr2x_core;
		static $can_log;

		if ( ! isset( $can_log ) ) {
			$can_log = $wr2x_core && is_object( $wr2x_core ) && method_exists( $wr2x_core, 'log' );
		}

		if ( $can_log ) {
			$wr2x_core->log( $text );
		}
	}
}
