<?php
namespace Imagify\Optimization\Data;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Optimization data class for the custom folders.
 * This class constructor accepts:
 * - A media ID (int).
 * - An array of data coming from the files DB table /!\
 * - An object of data coming from the files DB table /!\
 * - A \Imagify\Media\MediaInterface object.
 *
 * @since  1.9
 * @see    Imagify\Media\CustomFolders
 * @author Grégory Viguier
 */
class CustomFolders extends AbstractData {
	use \Imagify\Traits\MediaRowTrait;

	/**
	 * The attachment SQL DB class.
	 *
	 * @var    string
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $db_class_name = 'Imagify_Files_DB';

	/**
	 * The constructor.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param mixed $id An ID, or whatever type the "Media" class constructor accepts.
	 */
	public function __construct( $id ) {
		parent::__construct( $id );

		if ( ! $this->is_valid() ) {
			return;
		}

		// This is required by MediaRowTrait.
		$this->id = $this->get_media()->get_id();

		// In this context, the media data and the optimization data are stored in the same DB table, so, no need to request twice the DB.
		$this->row = $this->get_media()->get_row();
	}

	/**
	 * Get the whole media optimization data.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array The data. See parent method for details.
	 */
	public function get_optimization_data() {
		if ( ! $this->is_valid() ) {
			return $this->default_optimization_data;
		}

		$row  = array_merge( $this->get_row_db_instance()->get_column_defaults(), $this->get_row() );
		$data = $this->default_optimization_data;

		$data['status'] = $row['status'];
		$data['level']  = $row['optimization_level'];
		$data['level']  = is_numeric( $data['level'] ) ? (int) $data['level'] : false;

		if ( 'success' === $row['status'] ) {
			/**
			 * Success.
			 */
			$data['sizes']['full'] = [
				'success'        => true,
				'original_size'  => $row['original_size'],
				'optimized_size' => $row['optimized_size'],
				'percent'        => $row['percent'],
			];
		} elseif ( ! empty( $row['status'] ) ) {
			/**
			 * Error.
			 */
			$data['sizes']['full'] = [
				'success' => false,
				'error'   => $row['error'],
			];
		}

		if ( ! empty( $row['data']['sizes'] ) && is_array( $row['data']['sizes'] ) ) {
			unset( $row['data']['sizes']['full'] );
			$data['sizes'] = array_merge( $data['sizes'], $row['data']['sizes'] );
			$data['sizes'] = array_filter( $data['sizes'], 'is_array' );
		}

		if ( empty( $data['sizes'] ) ) {
			return $data;
		}

		foreach ( $data['sizes'] as $size_data ) {
			// Cast.
			if ( isset( $size_data['original_size'] ) ) {
				$size_data['original_size'] = (int) $size_data['original_size'];
			}
			if ( isset( $size_data['optimized_size'] ) ) {
				$size_data['optimized_size'] = (int) $size_data['optimized_size'];
			}
			if ( isset( $size_data['percent'] ) ) {
				$size_data['percent'] = round( $size_data['percent'], 2 );
			}
			// Stats.
			if ( ! empty( $size_data['original_size'] ) && ! empty( $size_data['optimized_size'] ) ) {
				$data['stats']['original_size']  += $size_data['original_size'];
				$data['stats']['optimized_size'] += $size_data['optimized_size'];
			}
		}

		if ( $data['stats']['original_size'] && $data['stats']['optimized_size'] ) {
			$data['stats']['percent'] = $data['stats']['original_size'] - $data['stats']['optimized_size'];
			$data['stats']['percent'] = round( $data['stats']['percent'] / $data['stats']['original_size'] * 100, 2 );
		}

		return $data;
	}

	/**
	 * Update the optimization data, level, and status for a size.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param string $size The size name.
	 * @param array  $data The optimization data. See parent method for details.
	 */
	public function update_size_optimization_data( $size, array $data ) {
		if ( ! $this->is_valid() ) {
			return;
		}

		$old_data = array_merge( $this->get_reset_data(), $this->get_row() );

		if ( 'full' === $size ) {
			/**
			 * Original file.
			 */
			$old_data['optimization_level'] = $data['level'];
			$old_data['status']             = $data['status'];
			$old_data['modified']           = 0;

			$file_path = $this->get_media()->get_fullsize_path();

			if ( $file_path ) {
				$old_data['hash'] = md5_file( $file_path );
			}

			if ( ! $data['success'] ) {
				/**
				 * Error.
				 */
				$old_data['error'] = $data['error'];
			} else {
				/**
				 * Success.
				 */
				$old_data['original_size']  = $data['original_size'];
				$old_data['optimized_size'] = $data['optimized_size'];
				$old_data['percent']        = $data['original_size'] - $data['optimized_size'];
				$old_data['percent']        = round( ( $old_data['percent'] / $data['original_size'] ) * 100, 2 );
			}
		} else {
			/**
			 * WebP version or any other size.
			 */
			$old_data['data'] = ! empty( $old_data['data'] ) && is_array( $old_data['data'] ) ? $old_data['data'] : [];
			$old_data['data']['sizes'] = ! empty( $old_data['data']['sizes'] ) && is_array( $old_data['data']['sizes'] ) ? $old_data['data']['sizes'] : [];

			if ( ! $data['success'] ) {
				/**
				 * Error.
				 */
				$old_data['data']['sizes'][ $size ] = [
					'success' => false,
					'error'   => $data['error'],
				];
			} else {
				/**
				 * Success.
				 */
				$old_data['data']['sizes'][ $size ] = [
					'success'        => true,
					'original_size'  => $data['original_size'],
					'optimized_size' => $data['optimized_size'],
					'percent'        => round( ( ( $data['original_size'] - $data['optimized_size'] ) / $data['original_size'] ) * 100, 2 ),
				];
			}
		}

		if ( isset( $old_data['data']['sizes'] ) && ( ! $old_data['data']['sizes'] || ! is_array( $old_data['data']['sizes'] ) ) ) {
			unset( $old_data['data']['sizes'] );
		}

		$this->update_row( $old_data );
	}

	/**
	 * Delete the media optimization data, level, and status.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function delete_optimization_data() {
		if ( ! $this->is_valid() ) {
			return;
		}

		$this->update_row( $this->get_reset_data() );
	}

	/**
	 * Delete the optimization data for the given sizes.
	 * If all sizes are removed, all optimization data is deleted.
	 * Status and level are not modified nor removed if the "full" size is removed. This leaves the media in a Schrödinger state.
	 *
	 * @since  1.9.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $sizes A list of sizes to remove.
	 */
	public function delete_sizes_optimization_data( array $sizes ) {
		if ( ! $sizes || ! $this->is_valid() ) {
			return;
		}

		$data = array_merge( $this->get_reset_data(), $this->get_row() );

		$data['data']['sizes'] = ! empty( $data['data']['sizes'] ) && is_array( $data['data']['sizes'] ) ? $data['data']['sizes'] : [];

		if ( ! $data['data']['sizes'] ) {
			return;
		}

		$remaining_sizes_data = array_diff_key( $data['data']['sizes'], array_flip( $sizes ) );

		if ( ! $remaining_sizes_data ) {
			// All sizes have been removed: delete everything.
			$this->delete_optimization_data();
			return;
		}

		if ( count( $remaining_sizes_data ) === count( $data['data']['sizes'] ) ) {
			// Nothing has been removed.
			return;
		}

		$data['data']['sizes'] = $remaining_sizes_data;

		$this->update_row( $data );
	}

	/**
	 * Get default values used to reset optimization data.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return array {
	 *     The default values related to the optimization.
	 *
	 *     @type string $hash               The file hash.
	 *     @type int    $modified           0 to tell that the file has not been modified
	 *     @type int    $optimized_size     File size after optimization.
	 *     @type int    $percent            Saving optimized/original in percent.
	 *     @type int    $optimization_level The optimization level.
	 *     @type string $status             The status: success, already_optimized, error.
	 *     @type string $error              An error message.
	 * }
	 */
	protected function get_reset_data() {
		static $column_defaults;

		if ( ! isset( $column_defaults ) ) {
			$column_defaults = $this->get_row_db_instance()->get_column_defaults();

			// All DB columns that have `null` as default value, are Imagify data.
			foreach ( $column_defaults as $column_name => $value ) {
				if ( 'hash' === $column_name || 'modified' === $column_name || 'data' === $column_name ) {
					continue;
				}

				if ( isset( $value ) ) {
					unset( $column_defaults[ $column_name ] );
				}
			}
		}

		$imagify_columns = $column_defaults;

		// Also set the new file hash.
		$file_path = $this->get_media()->get_fullsize_path();

		if ( $file_path ) {
			$imagify_columns['hash'] = md5_file( $file_path );
		}

		return $imagify_columns;
	}
}
