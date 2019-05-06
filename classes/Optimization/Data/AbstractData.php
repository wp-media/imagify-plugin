<?php
namespace Imagify\Optimization\Data;

use Imagify\Media\MediaInterface;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Abstract class used to handle the optimization data of "media groups" (aka attachments).
 *
 * @since  1.9
 * @author Grégory Viguier
 */
abstract class AbstractData implements DataInterface {

	/**
	 * Optimization data structure.
	 * This is the format returned when we "get" optimization data from the DB.
	 *
	 * @var    array
	 * @since  1.9
	 * @access protected
	 * @see    $this->get_optimization_data()
	 * @author Grégory Viguier
	 */
	protected $default_optimization_data = [
		'status' => '',
		'level'  => false,
		'sizes'  => [],
		'stats'  => [
			'original_size'  => 0,
			'optimized_size' => 0,
			'percent'        => 0,
		],
	];

	/**
	 * The media object.
	 *
	 * @var    MediaInterface
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $media;

	/**
	 * Filesystem object.
	 *
	 * @var    object Imagify_Filesystem
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

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
		// Set the Media instance.
		if ( $id instanceof MediaInterface ) {
			$this->media = $id;
		} elseif ( static::constructor_accepts( $id ) ) {
			$media_class = str_replace( '\\Optimization\\Data\\', '\\Media\\', get_called_class() );
			$media_class = '\\' . ltrim( $media_class, '\\' );
			$this->media = new $media_class( $id );
		} else {
			$this->media = false;
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
		if ( $id instanceof MediaInterface ) {
			return true;
		}

		$media_class = str_replace( '\\Optimization\\Data\\', '\\Media\\', get_called_class() );
		$media_class = '\\' . ltrim( $media_class, '\\' );

		return $media_class::constructor_accepts( $id );
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
		return $this->media;
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
	/** OPTIMIZATION DATA ======================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Check if the main file is optimized (by Imagify).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool True if the media is optimized.
	 */
	public function is_optimized() {
		return 'success' === $this->get_optimization_status();
	}

	/**
	 * Check if the main file is optimized (NOT by Imagify).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool True if the media is optimized.
	 */
	public function is_already_optimized() {
		return 'already_optimized' === $this->get_optimization_status();
	}

	/**
	 * Check if the main file is optimized (by Imagify).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool True if the media is optimized.
	 */
	public function is_error() {
		return 'error' === $this->get_optimization_status();
	}

	/**
	 * Get the media's optimization level.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int|false The optimization level. False if not optimized.
	 */
	public function get_optimization_level() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		$data = $this->get_optimization_data();
		return $data['level'];
	}

	/**
	 * Get the media's optimization status (success or error).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string The optimization status. An empty string if there is none.
	 */
	public function get_optimization_status() {
		if ( ! $this->is_valid() ) {
			return '';
		}

		$data = $this->get_optimization_data();
		return $data['status'];
	}

	/**
	 * Count number of optimized sizes.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int Number of optimized sizes.
	 */
	public function get_optimized_sizes_count() {
		$data  = $this->get_optimization_data();
		$count = 0;

		if ( ! $data['sizes'] ) {
			return 0;
		}

		$context_sizes = $this->get_media()->get_media_files();
		$data['sizes'] = array_intersect_key( $data['sizes'], $context_sizes );

		if ( ! $data['sizes'] ) {
			return 0;
		}

		foreach ( $data['sizes'] as $size ) {
			if ( ! empty( $size['success'] ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Get the original media's size (weight).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $human_format True to display the image human format size (1Mb).
	 * @param  int  $decimals     Precision of number of decimal places.
	 * @return string|int
	 */
	public function get_original_size( $human_format = true, $decimals = 2 ) {
		if ( ! $this->is_valid() ) {
			return $human_format ? imagify_size_format( 0, $decimals ) : 0;
		}

		$size = $this->get_optimization_data();
		$size = ! empty( $size['sizes']['full']['original_size'] ) ? $size['sizes']['full']['original_size'] : 0;

		// If nothing in the database, try to get the info from the file.
		if ( ! $size ) {
			// Check for the backup file first.
			$filepath = $this->get_media()->get_backup_path();

			if ( ! $filepath ) {
				// Try the original file then.
				$filepath = $this->get_media()->get_original_path();
			}

			$size = $filepath ? $this->filesystem->size( $filepath ) : 0;
		}

		if ( $human_format ) {
			return imagify_size_format( (int) $size, $decimals );
		}

		return (int) $size;
	}

	/**
	 * Get the file size of the full size file.
	 * If the webp size is available, it is used.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $human_format True to display the image human format size (1Mb).
	 * @param  int  $decimals     Precision of number of decimal places.
	 * @param  bool $use_webp     Use the webp size if available.
	 * @return string|int
	 */
	public function get_optimized_size( $human_format = true, $decimals = 2, $use_webp = true ) {
		if ( ! $this->is_valid() ) {
			return $human_format ? imagify_size_format( 0, $decimals ) : 0;
		}

		$data  = $this->get_optimization_data();
		$media = $this->get_media();

		if ( $use_webp ) {
			$process_class_name = imagify_get_optimization_process_class_name( $media->get_context() );
			$webp_size_name     = 'full' . constant( $process_class_name . '::WEBP_SUFFIX' );
		}

		if ( $use_webp && ! empty( $data['sizes'][ $webp_size_name ]['optimized_size'] ) ) {
			$size = (int) $data['sizes'][ $webp_size_name ]['optimized_size'];
		} elseif ( ! empty( $data['sizes']['full']['optimized_size'] ) ) {
			$size = (int) $data['sizes']['full']['optimized_size'];
		} else {
			$size = 0;
		}

		if ( $size ) {
			return $human_format ? imagify_size_format( $size, $decimals ) : $size;
		}

		// If nothing in the database, try to get the info from the file.
		$filepath = false;

		if ( $use_webp && ! empty( $data['sizes'][ $webp_size_name ]['success'] ) ) {
			// Try with the webp file first.
			$filepath = $media->get_raw_original_path();
			$filepath = $filepath ? imagify_path_to_webp( $filepath ) : false;

			if ( ! $filepath || ! $this->filesystem->exists( $filepath ) ) {
				$filepath = false;
			}
		}

		if ( ! $filepath ) {
			// No webp? The full size then.
			$filepath = $media->get_original_path();
		}

		if ( ! $filepath ) {
			return $human_format ? imagify_size_format( 0, $decimals ) : 0;
		}

		$size = (int) $this->filesystem->size( $filepath );

		return $human_format ? imagify_size_format( $size, $decimals ) : $size;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** OPTIMIZATION STATS ====================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get one or all statistics of a specific size.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $size The thumbnail slug.
	 * @param  string $key  The specific data slug.
	 * @return array|string
	 */
	public function get_size_data( $size = 'full', $key = '' ) {
		$data = $this->get_optimization_data();

		if ( ! isset( $data['sizes'][ $size ] ) ) {
			return $key ? '' : [];
		}

		if ( ! $key ) {
			return $data['sizes'][ $size ];
		}

		if ( ! isset( $data['sizes'][ $size ][ $key ] ) ) {
			return '';
		}

		return $data['sizes'][ $size ][ $key ];
	}

	/**
	 * Get the overall statistics data or a specific one.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $key The specific data slug.
	 * @return array|string
	 */
	public function get_stats_data( $key = '' ) {
		$data  = $this->get_optimization_data();
		$stats = '';

		if ( empty( $data['stats'] ) ) {
			return $key ? '' : [];
		}

		if ( ! isset( $data['stats'][ $key ] ) ) {
			return '';
		}

		return $data['stats'][ $key ];
	}

	/**
	 * Get the optimized/original saving of the original image in percent.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return float A 2-decimals float.
	 */
	public function get_saving_percent() {
		if ( ! $this->is_valid() ) {
			return round( (float) 0, 2 );
		}

		$process_class_name = imagify_get_optimization_process_class_name( $this->get_media()->get_context() );
		$webp_size_name     = 'full' . constant( $process_class_name . '::WEBP_SUFFIX' );

		$percent = $this->get_size_data( $webp_size_name, 'percent' );

		if ( ! $percent ) {
			$percent = $this->get_size_data( 'full', 'percent' );
		}

		$percent = $percent ? $percent : 0;

		return round( (float) $percent, 2 );
	}

	/**
	 * Get the overall optimized/original saving (original image + all thumbnails) in percent.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return float A 2-decimals float.
	 */
	public function get_overall_saving_percent() {
		if ( ! $this->is_valid() ) {
			return round( (float) 0, 2 );
		}

		$percent = $this->get_stats_data( 'percent' );

		return round( (float) $percent, 2 );
	}
}
