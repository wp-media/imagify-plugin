<?php
namespace Imagify\ThirdParty\NGG\Optimization\Data;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Optimization data class for the custom folders.
 * This class constructor accepts:
 * - A NGG image ID (int).
 * - A \nggImage object.
 * - A \nggdb object.
 * - An anonym object containing a pid property (and everything else).
 * - A \Imagify\Media\MediaInterface object.
 *
 * @since  1.9
 * @see    Imagify\ThirdParty\NGG\Media\NGG
 * @author Grégory Viguier
 */
class NGG extends \Imagify\Optimization\Data\AbstractData {
	use \Imagify\Traits\MediaRowTrait;

	/**
	 * The attachment SQL DB class.
	 *
	 * @var    string
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $db_class_name = '\\Imagify\\ThirdParty\\NGG\\DB';

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

		if ( ! empty( $row['data']['sizes'] ) && is_array( $row['data']['sizes'] ) ) {
			$data['sizes'] = $row['data']['sizes'];
			$data['sizes'] = array_filter( $data['sizes'], 'is_array' );
		}

		if ( empty( $data['sizes']['full'] ) ) {
			if ( 'success' === $row['status'] ) {
				$data['sizes']['full'] = [
					'success'        => true,
					'original_size'  => 0,
					'optimized_size' => 0,
					'percent'        => 0,
				];
			} elseif ( ! empty( $row['status'] ) ) {
				$data['sizes']['full'] = [
					'success' => false,
					'error'   => '',
				];
			}
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

		$old_data['data']['sizes'] = ! empty( $old_data['data']['sizes'] ) && is_array( $old_data['data']['sizes'] ) ? $old_data['data']['sizes'] : [];
		$old_data['data']['stats'] = ! empty( $old_data['data']['stats'] ) && is_array( $old_data['data']['stats'] ) ? $old_data['data']['stats'] : [];

		if ( 'full' === $size ) {
			/**
			 * Original file.
			 */
			$old_data['optimization_level'] = $data['level'];
			$old_data['status']             = $data['status'];
		}

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
				'percent'        => round( ( $data['original_size'] - $data['optimized_size'] ) / $data['original_size'] * 100, 2 ),
			];
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

		$this->delete_row();
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
	 *     @type string $optimization_level The optimization level.
	 *     @type string $status             The status: success, already_optimized, error.
	 *     @type array  $data               Data related to the thumbnails.
	 * }
	 */
	protected function get_reset_data() {
		$db_instance     = $this->get_row_db_instance();
		$primary_key     = $db_instance->get_primary_key();
		$column_defaults = $db_instance->get_column_defaults();

		return array_diff_key( $column_defaults, [
			'data_id'    => 0,
			$primary_key => 0,
		] );
	}

	/**
	 * Update the row.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $data The data to update.
	 */
	public function update_row( $data ) {
		if ( ! $this->db_class_name || $this->id <= 0 ) {
			return;
		}

		$primary_key = $this->get_row_db_instance()->get_primary_key();
		// This is needed in case the row doesn't exist yet.
		$data[ $primary_key ] = $this->id;

		$this->get_row_db_instance()->update( $this->id, $data );

		$this->reset_row_cache();
	}
}
