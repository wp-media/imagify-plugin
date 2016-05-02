<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

class Imagify_Abstract_Attachment {
	/**
	 * The attachment ID
	 *
	 * @since 1.0
	 *
	 * @var    int
	 * @access public
	 */
	public $id;

	 /**
     * The constructor
     *
	 * @since 1.0
	 *
     * @return void
     **/
	function __construct( $id = 0 ) {}

	/**
	 * Get the attachment backup filepath.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return string|false
	 */
	public function get_backup_path() {}
	
	/**
	 * Get the attachment backup URL.
	 *
	 * @since 1.4
	 *
	 * @access public
	 * @return string|false
	 */
	public function get_backup_url() {
		$backup_path = $this->get_backup_path();
		$backup_url  = str_replace( ABSPATH, site_url( '/' ), $backup_path );
		
		return $backup_url;
	}

	/**
	 * Get the attachment optimization data.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return array
	 */
	public function get_data() {}
	
	/**
	 * Get the attachment extension.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return string
	 */
	public function get_extension() {
		$fullsize_path = $this->get_original_path();
		return pathinfo( $fullsize_path, PATHINFO_EXTENSION );
	}
	
	/**
	 * Get the attachment error if there is one.
	 *
	 * @since 1.1.5
	 *
	 * @access public
	 * @return string The message error
	 */
	public function get_optimized_error() {
		$error = $this->get_size_data( 'full', 'error' );
		
		if ( is_string( $error ) ) {
			return $error;
		}
		
		return false;
	}
	
	/**
	 * Get the attachment optimization level.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return int
	 */
	public function get_optimization_level() {}
	
	/**
	 * Get the attachment optimization level label.
	 *
	 * @since 1.2
	 *
	 * @access public
	 * @return string
	 */
	public function get_optimization_level_label() {
		$label = '';
		$level = $this->get_optimization_level();
		
		switch( $level ) {
			case 2:
				$label = __( 'Ultra', 'imagify' );
			break;
			case 1:
				$label = __( 'Aggressive', 'imagify' );
			break;
			case 0:
				$label = __( 'Normal', 'imagify' );
			break;
		}

		return $label;
	}

	/**
	 * Count number of optimized sizes.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return int
	 */
	public function get_optimized_sizes_count() {
		$data  = $this->get_data();
		$sizes = (array) $data['sizes'];
		$count = 0;

		if ( isset( $sizes['full'] ) ) {
			unset( $sizes['full'] );
		}

		foreach ( $sizes as $size ) {
			if ( $size['success'] ) {
				$count++;
			}
		}

		return (int) $count;
	}

	/**
	 * Get the attachment optimization status (success or error).
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return string
	 */
	public function get_status() {}

	/**
	 * Get the original attachment path.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return string
	 */
	public function get_original_path() {}

	/**
	 * Get the original attachment size.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @param  bool 	$human_format  True to display the image human format size (1Mb).
	 * @return string
	 */
	public function get_original_size( $human_format = true ) {
		$original_size = $this->get_size_data( 'full', 'original_size' );
		$original_size = ( empty( $original_size ) ) ? @filesize( $this->get_original_path() ) : (int) $original_size;
		
		if ( true === $human_format ) {
			$original_size = @size_format( $original_size, 2 );	
		}
		
		return $original_size;
	}

	/**
	 * Get the original attachment URL.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return string
	 */
	public function get_original_url() {}

	/*
	 * Get the statistics of a specific size.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @param  string  $size  The thumbnail slug.
	 * @param  string  $key   The specific data slug.
	 * @return array|string
	 */
	public function get_size_data( $size = 'full', $key = '' ) {
		$data  = $this->get_data();
		$stats = array();

		if ( isset( $data['sizes'][ $size ] ) ) {
			$stats = $data['sizes'][ $size ];
		}

		if ( isset( $stats[ $key ] ) ) {
			$stats = $stats[ $key ];
		}

		return $stats;
	}
	
	/**
	 * Get the global statistics data or a specific one.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @param  string $key  The specific data slug.
	 * @return array|string
	 */
	public function get_stats_data( $key = '' ) {
		$data  = $this->get_data();
		$stats = '';

		if ( isset( $data['stats'] ) ) {
			$stats = $data['stats'];
		}

		if ( isset( $stats[ $key ] ) ) {
			$stats = $stats[ $key ];
		}

		return $stats;
	}

	/**
	 * Check if the attachment is already optimized (before Imagify).
	 *
	 * @since 1.1.6
	 *
	 * @access public
	 * @return bool   True if the attachment is optimized.
	 */
	public function is_already_optimized() {
		return ( 'already_optimized' === $this->get_status() ) > 0;
	}
	
	/**
	 * Check if the attachment is optimized.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return bool   True if the attachment is optimized.
	 */
	public function is_optimized() {
		return ( 'success' === $this->get_status() ) > 0;
	}

	/**
	 * Check if the attachment exceeding the limit size (> 5mo).
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return bool   True if the attachment is skipped.
	 */
	public function is_exceeded() {
		$filepath = $this->get_original_path();
		$size     = 0;

		if ( file_exists( $filepath ) ) {
			$size = filesize( $filepath );
		}

		return ( $size > IMAGIFY_MAX_BYTES ) > 0;
	}

	/**
	 * Check if the attachment has a backup of the original size.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return bool   True if the attachment has a backup.
	 */
	public function has_backup() {
		return (bool) $this->get_backup_path();
	}

	/**
	 * Check if the attachment has an error.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return bool   True if the attachment has an error.
	 */
	public function has_error() {
		$has_error = $this->get_size_data( 'full', 'error' );
		return ( is_string( $has_error ) ) > 0;
	}
	
	/**
	 * Update the metadata size of the attachment
	 *
	 * @since 1.2
	 *
	 * @access public
	 * @return void
	 */
	public function update_metadata_size() {}

	/**
	 * Delete the backup file.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return void
	 */
	public function delete_backup() {
		$backup_path = $this->get_backup_path();

		if ( ! empty( $backup_path ) ) {
			@unlink( $backup_path );
		}
	}

	/**
	 * Fills statistics data with values from $data array
	 *
	 * @since 1.0
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
	static public function fill_data( $data, $response, $id, $url, $size = 'full' ) {}

	/**
	 * Optimize all sizes with Imagify.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @param  int 	  $optimization_level   The optimization level (2=ultra, 1=aggressive, 0=normal)
	 * @param  array  $metadata   	   		The attachment meta data
	 * @return array  $optimized_data  		The optimization data
	 */
	public function optimize( $optimization_level = null, $metadata = array() ) {}

	/**
	 * Process an attachment restoration from the backup file.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return void
	 */
	public function restore() {}
}