<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify Attachment class.
 *
 * @since 1.0
 */
class Imagify_Attachment extends Imagify_Abstract_Attachment {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.2';

	/**
	 * Get the attachment backup filepath.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return string|bool
	 */
	public function get_backup_path() {
		$file_path   = $this->get_original_path();
		$backup_path = get_imagify_attachment_backup_path( $file_path );

		if ( file_exists( $backup_path ) ) {
			return $backup_path;
		}

		return false;
	}

	/**
	 * Get the attachment optimization data.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_data() {
		$data = get_post_meta( $this->id, '_imagify_data', true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Get the attachment optimization level.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return int
	 */
	public function get_optimization_level() {
		return (int) get_post_meta( $this->id, '_imagify_optimization_level', true );
	}

	/**
	 * Get the attachment optimization status (success or error).
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_status() {
		$status = get_post_meta( $this->id, '_imagify_status', true );
		return is_string( $status ) ? $status : '';
	}

	/**
	 * Get the original attachment path.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_original_path() {
		return get_attached_file( $this->id );
	}

	/**
	 * Get the original attachment URL.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_original_url() {
		return wp_get_attachment_url( $this->id );
	}

	/**
	 * Update the metadata size of the attachment.
	 *
	 * @since 1.2
	 * @access public
	 *
	 * @return bool
	 */
	public function update_metadata_size() {
		if ( ! wp_attachment_is_image( $this->id ) ) {
			return false;
		}

		$size = @getimagesize( $this->get_original_path() );

		if ( ! isset( $size[0], $size[1] ) ) {
			return false;
		}

		$metadata           = wp_get_attachment_metadata( $this->id );
		$metadata['width']  = $size[0];
		$metadata['height'] = $size[1];

		wp_update_attachment_metadata( $this->id, $metadata );
		return true;
	}

	/**
	 * Fills statistics data with values from $data array.
	 *
	 * @since 1.0
	 * @since 1.6.5 Not static anymore.
	 * @since 1.6.6 Removed the attachment ID parameter.
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
				update_post_meta( $this->id, '_imagify_data', $data );

				if ( false !== strpos( $error, 'This image is already compressed' ) ) {
					$error_status = 'already_optimized';
				}

				update_post_meta( $this->id, '_imagify_status', $error_status );

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
		} // End if().

		return $data;
	}

	/**
	 * Optimize all sizes with Imagify.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param  int   $optimization_level  The optimization level (2=ultra, 1=aggressive, 0=normal).
	 * @param  array $metadata            The attachment meta data.
	 * @return array $optimized_data      The optimization data.
	 */
	public function optimize( $optimization_level = null, $metadata = array() ) {
		$optimization_level = is_null( $optimization_level ) ? (int) get_imagify_option( 'optimization_level', 1 ) : (int) $optimization_level;

		$metadata = $metadata ? $metadata : wp_get_attachment_metadata( $this->id );
		$sizes    = isset( $metadata['sizes'] ) ? (array) $metadata['sizes'] : array();

		// To avoid issue with "original_size" at 0 in "_imagify_data".
		if ( 0 === (int) $this->get_stats_data( 'original_size' ) ) {
			$this->delete_imagify_data();
		}

		// Get file path & URL for original image.
		$attachment_path          = $this->get_original_path();
		$attachment_url           = $this->get_original_url();
		$attachment_original_size = $this->get_original_size( false );

		// Check if the attachment extension is allowed.
		if ( ! $this->id || ! wp_attachment_is_image( $this->id ) ) {
			return;
		}

		// Check if the full size is already optimized.
		if ( $this->is_optimized() && ( $this->get_optimization_level() === $optimization_level ) ) {
			return;
		}

		/**
		 * Fires before optimizing an attachment.
		 *
		 * @since 1.0
		 *
		 * @param int $id The attachment ID.
		*/
		do_action( 'before_imagify_optimize_attachment', $this->id );

		set_transient( 'imagify-async-in-progress-' . $this->id, true, 10 * MINUTE_IN_SECONDS );

		// Get the resize values for the original size.
		$resized         = false;
		$do_resize       = get_imagify_option( 'resize_larger', false );
		$resize_width    = get_imagify_option( 'resize_larger_w' );
		$attachment_size = @getimagesize( $attachment_path );

		if ( $do_resize && isset( $attachment_size[0] ) && $resize_width < $attachment_size[0] ) {
			$resized_attachment_path = $this->resize( $attachment_path, $attachment_size, $resize_width );

			if ( ! is_wp_error( $resized_attachment_path ) ) {
				$filesystem = imagify_get_filesystem();

				if ( get_imagify_option( 'backup', false ) ) {
					$backup_path      = get_imagify_attachment_backup_path( $attachment_path );
					$backup_path_info = pathinfo( $backup_path );

					wp_mkdir_p( $backup_path_info['dirname'] );

					// TO DO - check and send a error message if the backup can't be created.
					$filesystem->copy( $attachment_path, $backup_path, true );
					imagify_chmod_file( $backup_path );
				}

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
			'context'            => 'wp',
			'resized'            => $resized,
			'original_size'      => $attachment_original_size,
		) );

		$data = $this->fill_data( null, $response, $attachment_url );

		if ( ! $data ) {
			delete_transient( 'imagify-async-in-progress-' . $this->id );
			return;
		}

		// Save the optimization level.
		update_post_meta( $this->id, '_imagify_optimization_level', $optimization_level );

		// If we resized the original with success, we have to update the attachment metadata.
		// If not, WordPress keeps the old attachment size.
		if ( $do_resize && $resized ) {
			$this->update_metadata_size();
		}

		// Optimize all thumbnails.
		if ( $sizes ) {
			foreach ( $sizes as $size_key => $size_data ) {
				// Check if this size has to be optimized.
				if ( array_key_exists( $size_key, get_imagify_option( 'disallowed-sizes', array() ) ) && ! imagify_is_active_for_network() ) {
					$data['sizes'][ $size_key ] = array(
						'success' => false,
						'error'   => __( 'This size isn\'t authorized to be optimized. Update your Imagify settings if you want to optimize it.', 'imagify' ),
					);
					continue;
				}

				$thumbnail_path = trailingslashit( dirname( $attachment_path ) ) . $size_data['file'];
				$thumbnail_url  = trailingslashit( dirname( $attachment_url ) ) . $size_data['file'];

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
				* @since 1.0
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
				$data = apply_filters( 'imagify_fill_thumbnail_data', $data, $response, $this->id, $thumbnail_path, $thumbnail_url, $size_key, $optimization_level );
			} // End foreach().
		} // End if().

		$data['stats']['percent'] = round( ( ( $data['stats']['original_size'] - $data['stats']['optimized_size'] ) / $data['stats']['original_size'] ) * 100, 2 );

		update_post_meta( $this->id, '_imagify_data', $data );
		update_post_meta( $this->id, '_imagify_status', 'success' );

		$optimized_data = $this->get_data();

		/**
		 * Fires after optimizing an attachment.
		 *
		 * @since 1.0
		 *
		 * @param int   $id              The attachment ID.
		 * @param array $optimized_data  The optimization data.
		*/
		do_action( 'after_imagify_optimize_attachment', $this->id, $optimized_data );

		delete_transient( 'imagify-async-in-progress-' . $this->id );

		return $optimized_data;
	}

	/**
	 * Process an attachment restoration from the backup file.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return void
	 */
	public function restore() {
		// Stop the process if there is no backup file to restore.
		if ( ! $this->has_backup() ) {
			return;
		}

		$backup_path     = $this->get_backup_path();
		$attachment_path = $this->get_original_path();
		$filesystem      = imagify_get_filesystem();

		/**
		 * Fires before restoring an attachment.
		 *
		 * @since 1.0
		 *
		 * @param int $id The attachment ID
		*/
		do_action( 'before_imagify_restore_attachment', $this->id );

		// Create the original image from the backup.
		$filesystem->copy( $backup_path, $attachment_path, true );
		imagify_chmod_file( $attachment_path );

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}

		remove_filter( 'wp_generate_attachment_metadata', '_imagify_optimize_attachment', PHP_INT_MAX );
		wp_generate_attachment_metadata( $this->id, $attachment_path );

		// Remove old optimization data.
		$this->delete_imagify_data();

		// Restore the original size in the metadata.
		$this->update_metadata_size();

		/**
		 * Fires after restoring an attachment.
		 *
		 * @since 1.0
		 *
		 * @param int $id The attachment ID
		*/
		do_action( 'after_imagify_restore_attachment', $this->id );
	}
}
