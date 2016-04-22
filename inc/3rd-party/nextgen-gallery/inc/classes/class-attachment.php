<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

class Imagify_NGG_Attachment extends Imagify_Abstract_Attachment {	
	/**
	 * The image object
	 *
	 * @since 1.5
	 *
	 * @var    object
	 * @access public
	 */
	public $image;

	 /**
     * The constructor
     *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
     * @return void
     **/
	function __construct( $id ) {
		if ( is_object( $id ) ) {
			$this->image = $id;
			$this->id    = $id->pid;
		} else {
			$this->image = nggdb::find_image( (int) $id );
			$this->id    = $this->image->pid;
		}
		
		$this->row = $this->get_row();
		
		// Load nggAdmin classe
		$ngg_admin_functions_path = WP_PLUGIN_DIR . '/' . NGGFOLDER . '/products/photocrati_nextgen/modules/ngglegacy/admin/functions.php';
		
		if ( ! class_exists( 'nggAdmin' ) && file_exists( $ngg_admin_functions_path ) ) {
			require_once( $ngg_admin_functions_path );
		}
	}

	/**
	 * Get the attachment backup filepath.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return string|false
	 */
	public function get_backup_path() {
		$backup_path = $this->get_original_path() . '_backup';

		if( file_exists( $backup_path ) ) {
			return $backup_path;
		}

		return false;
	}
	
	/**
	 * Get the attachment optimization data.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return array
	 */
	public function get_data() {
		$row = ( (bool) $this->row ) ? $this->row : $this->get_row();
		return isset( $row['data'] ) ? unserialize( $row['data'] ) : false;
	}
	
	/**
	 * Get the attachment optimization level.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return int
	 */
	public function get_optimization_level() {
		$row = ( (bool) $this->row ) ? $this->row : $this->get_row();
		return isset( $row['optimization_level'] ) ? $row['optimization_level'] : false;
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
		$result = Imagify_NGG_DB()->get( $this->id );
		return $result;
	}

	/**
	 * Get the attachment optimization status (success or error).
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return string
	 */
	public function get_status() {
		$row = ( (bool) $this->row ) ? $this->row : $this->get_row();
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
	 * Update the metadata size of the attachment
	 *
	 * @since 1.5
	 *
	 * @access public
	 * @return void
	 */
	public function update_metadata_size() {		
		$size = @getimagesize( $this->get_original_path() );
		
		if ( isset( $size[0], $size[1] ) ) {
			$metadata           = $this->image->meta_data;
			$metadata['width']  = $metadata['full']['width']  = $size[0];
			$metadata['height'] = $metadata['full']['height'] = $size[1];
			
			nggdb::update_image_meta( $this->id , $metadata );
		}
	}
	
	/**
	 * Fills statistics data with values from $data array
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @static
	 * @param array   $data		 The statistics data
	 * @param object  $response  The API response
	 * @param int  	  $id   	 The attachment ID
	 * @param int  	  $url  	 The attachment URL
	 * @param string  $size 	 The attachment size key
	 * @return bool|array  False if the original size has an error or an array contains the data for other result
	 */
	static public function fill_data( $data, $response, $id, $url, $size = 'full' ) {
		if ( is_wp_error( $response ) ) {
			$error        = $response->get_error_message();
			$error_status = 'error';
			
			$data['sizes'][ $size ] = array(
				'success' => false,
				'error'   => $error
			);
			
			// Update the error status for the original size
			if ( 'full' === $size ) {				
				if ( false !== strpos( $error, 'This image is already compressed' ) ) {
					$error_status = 'already_optimized';	
				}
				
				IMAGIFY_NGG_DB()->update( 
					$id, 
					array(
						'pid'    => $id,
						'status' => $error_status,
						'data'   => serialize( $data )
					) 
				);
				
				return false;
			}
		} else {			
			$data['sizes'][ $size ] = array(
				'success' 		 => true,
				'file_url'		 => $url,
				'original_size'  => $response->original_size,
				'optimized_size' => $response->new_size,
				'percent'        => $response->percent
			);

			$data['stats']['original_size']  += ( isset( $response->original_size ) ) ? $response->original_size : 0;
			$data['stats']['optimized_size'] += ( isset( $response->new_size ) ) ? $response->new_size : 0;
			$data['stats']['percent'] = round( ( ( $data['stats']['original_size'] - $data['stats']['optimized_size'] ) / $data['stats']['original_size'] ) * 100, 2 );
		}

		return $data;
	}

	/**
	 * Optimize all sizes with Imagify.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @param  int 	  $optimization_level  The optimization level (2=ultra, 1=aggressive, 0=normal)
	 * @param  array  $metadata   	   	   The attachment meta data (not used here)
	 * @return array  $data  			   The optimization data
	 */
	public function optimize( $optimization_level = null, $metadata = array() ) {		
		$optimization_level = ( is_null( $optimization_level ) ) ? (int) get_imagify_option( 'optimization_level', 1 ) : (int) $optimization_level;

		$id 		   = $this->id;
		$data          = array(
			'stats' => array(
				'original_size'      => 0,
				'optimized_size'     => 0,
				'percent'            => 0,
			)
		);

		// Get file path & URL for original image
		$attachment_path = $this->get_original_path();
		$attachment_url  = $this->get_original_url();
		
		// Check if the full size is already optimized
		if ( $this->is_optimized() && ( $this->get_optimization_level() == $optimization_level ) ) {
			return;
		}
		
		/**
		 * Fires before optimizing an attachment.
		 *
		 * @since 1.5
		 *
		 * @param int $id The attachment ID
		*/
		do_action( 'before_imagify_ngg_optimize_attachment', $id );
		
		set_transient( 'imagify-ngg-async-in-progress-' . $id, true, 10 * MINUTE_IN_SECONDS );
		
		// Get the resize values for the original size
		$resize           = array();
		$do_resize        = get_imagify_option( 'resize_larger', false );
		$resize_width     = get_imagify_option( 'resize_larger_w' );
		$attachment_size  = @getimagesize( $attachment_path );

		if ( $do_resize && isset( $attachment_size[0] ) && $resize_width < $attachment_size[0] ) {
			$resize['width'] = $resize_width;
		}
		
		// Optimize the original size 
		$response = do_imagify( $attachment_path, array(
			'optimization_level' => $optimization_level,
			'resize'             => $resize,
			'context'            => 'ngg',
			'original_size'		 => $this->get_original_size( false )
		) );
		$data 	  = $this->fill_data( $data, $response, $id, $attachment_url );
		
		// Save the optimization level
		IMAGIFY_NGG_DB()->update( 
			$id, 
			array( 
				'pid'                => $id,
				'optimization_level' => $optimization_level 
			) 
		);
		
		if ( (bool) ! $data ) {
			delete_transient( 'imagify-ngg-async-in-progress-' . $id );
			return;
		}
		
		// If we resized the original with success, we have to update the attachment metadata
		// If not, WordPress keeps the old attachment size.		
		if ( $do_resize && isset( $resize['width'] ) ) {
			$this->update_metadata_size();
		}
				
		// Optimize thumbnails
		$data = $this->optimize_thumbnails( $optimization_level, $data );
		
		// Save the status to success
		IMAGIFY_NGG_DB()->update( 
			$id, 
			array(
				'pid'    => $id,
				'status' => 'success',
			)
		);

		/**
		 * Fires after optimizing an attachment.
		 *
		 * @since 1.5
		 *
		 * @param int    $id   	The attachment ID
		 * @param array  $data  The optimization data
		*/
		do_action( 'after_imagify_ngg_optimize_attachment', $id, $data );
		
		delete_transient( 'imagify-ngg-async-in-progress-' . $id );

		return $data;
	}
	
	/**
	 * Optimize all thumbnails of an image
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @param  int 	  $optimization_level   The optimization level (2=ultra, 1=aggressive, 0=normal)
	 * @return array  $data  				The optimization data
	 */
	public function optimize_thumbnails( $optimization_level = null, $data = array() ) {
		$id 	 = $this->id;
		$storage = C_Gallery_Storage::get_instance();
		$sizes   = $storage->get_image_sizes();
		$data    = ( (bool) $data ) ? $data : $this->get_data();
		
		// Stop if the original image has an error
		if ( $this->has_error() ) {
			return $data;
		}
		
		$optimization_level = ( is_null( $optimization_level ) ) ? (int) get_imagify_option( 'optimization_level', 1 ) : (int) $optimization_level;

		/**
		 * Fires before optimizing all thumbnails.
		 *
		 * @since 1.5
		 *
		 * @param int $id The image ID
		*/
		do_action( 'before_imagify_ngg_optimize_thumbnails', $id );
		
		if ( (bool) $sizes ) {
			foreach ( $sizes as $size_key )  {
				if ( 'full' == $size_key || isset( $data['sizes'][ $size_key ]['success'] ) ) {
					continue;
				}
								
				$thumbnail_path = $storage->get_image_abspath( $this->image, $size_key );
				$thumbnail_url  = $storage->get_image_url( $this->image, $size_key );
	
				// Optimize the thumbnail size
				$response = do_imagify( $thumbnail_path, array(
					'backup'             => false,
					'optimization_level' => $optimization_level,
					'context'            => 'wp'
				) );
				$data     = $this->fill_data( $data, $response, $id, $thumbnail_url, $size_key );
				
				/** This filter is documented in /inc/classes/class-attachment.php */
				$data = apply_filters( 'imagify_fill_ngg_thumbnail_data', $data, $response, $id, $thumbnail_path, $thumbnail_url, $size_key, $optimization_level );
			}
			
			IMAGIFY_NGG_DB()->update( 
				$id, 
				array(
					'pid'    => $id,
					'data'   => serialize( $data )
				)
			);
		}
		
		/**
		 * Fires after optimizing all thumbnails.
		 *
		 * @since 1.5
		 *
		 * @param int    $id       The image ID
		 * @param array  $data     The optimization data
		*/
		do_action( 'after_imagify_ngg_optimize_thumbnails', $id, $data );
		
		return $data;
	}

	/**
	 * Process an attachment restoration from the backup file.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @access public
	 * @return void
	 */
	public function restore() {		
		// Stop the process if there is no backup file to restore
		if ( ! $this->has_backup() ) {
			return;
		}
		
		$id              = $this->id;
		$backup_path     = $this->get_backup_path();
		$attachment_path = $this->get_original_path();

		/**
		 * Fires before restoring an attachment.
		 *
		 * @since 1.5
		 *
		 * @param int $id The attachment ID
		*/
		do_action( 'before_imagify_ngg_restore_attachment', $id );
				
		// Create the original image from the backup
		C_Gallery_Storage::get_instance()->recover_image( $id );
		
		// Remove old optimization data
		Imagify_NGG_DB()->delete( $id );	
				
		/**
		 * Fires after restoring an attachment.
		 *
		 * @since 1.5
		 *
		 * @param int $id The attachment ID
		*/
		do_action( 'after_imagify_ngg_restore_attachment', $id );
	}
}