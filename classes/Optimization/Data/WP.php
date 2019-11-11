<?php
namespace Imagify\Optimization\Data;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Optimization data class for the medias in the WP library.
 * This class constructor accepts:
 * - A post ID (int).
 * - A \WP_Post object.
 * - A \Imagify\Media\MediaInterface object.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class WP extends AbstractData {

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

		$id = $this->get_media()->get_id();

		$data = get_post_meta( $id, '_imagify_data', true );
		$data = is_array( $data ) ? $data : [];

		if ( isset( $data['sizes'] ) && ! is_array( $data['sizes'] ) ) {
			$data['sizes'] = [];
		}

		if ( isset( $data['stats'] ) && ! is_array( $data['stats'] ) ) {
			$data['stats'] = [];
		}

		$data = array_merge( $this->default_optimization_data, $data );

		$data['status'] = get_post_meta( $id, '_imagify_status', true );
		$data['status'] = is_string( $data['status'] ) ? $data['status'] : '';

		$data['level'] = get_post_meta( $id, '_imagify_optimization_level', true );
		$data['level'] = is_numeric( $data['level'] ) ? (int) $data['level'] : false;

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

		$id = $this->get_media()->get_id();

		if ( 'full' === $size ) {
			// Optimization level.
			update_post_meta( $id, '_imagify_optimization_level', $data['level'] );
			// Optimization status.
			update_post_meta( $id, '_imagify_status', $data['status'] );
		}

		// Size data and stats.
		$old_data = get_post_meta( $id, '_imagify_data', true );
		$old_data = is_array( $old_data ) ? $old_data : [];

		if ( ! isset( $old_data['sizes'] ) || ! is_array( $old_data['sizes'] ) ) {
			$old_data['sizes'] = [];
		}

		if ( ! isset( $old_data['stats'] ) || ! is_array( $old_data['stats'] ) ) {
			$old_data['stats'] = [];
		}

		$old_data['stats'] = array_merge( [
			'original_size'  => 0,
			'optimized_size' => 0,
			'percent'        => 0,
		], $old_data['stats'] );

		if ( ! $data['success'] ) {
			/**
			 * Error.
			 */
			$old_data['sizes'][ $size ] = [
				'success' => false,
				'error'   => $data['error'],
			];
		} else {
			/**
			 * Success.
			 */
			$old_data['sizes'][ $size ] = [
				'success'        => true,
				'original_size'  => $data['original_size'],
				'optimized_size' => $data['optimized_size'],
				'percent'        => round( ( ( $data['original_size'] - $data['optimized_size'] ) / $data['original_size'] ) * 100, 2 ),
			];

			$old_data['stats']['original_size']  += $data['original_size'];
			$old_data['stats']['optimized_size'] += $data['optimized_size'];
			$old_data['stats']['percent']         = round( ( ( $old_data['stats']['original_size'] - $old_data['stats']['optimized_size'] ) / $old_data['stats']['original_size'] ) * 100, 2 );
		}

		update_post_meta( $id, '_imagify_data', $old_data );
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

		$id = $this->get_media()->get_id();

		delete_post_meta( $id, '_imagify_data' );
		delete_post_meta( $id, '_imagify_status' );
		delete_post_meta( $id, '_imagify_optimization_level' );
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

		$media_id = $this->get_media()->get_id();
		$data     = get_post_meta( $media_id, '_imagify_data', true );

		if ( empty( $data['sizes'] ) || ! is_array( $data['sizes'] ) ) {
			return;
		}

		$remaining_sizes_data = array_diff_key( $data['sizes'], array_flip( $sizes ) );

		if ( ! $remaining_sizes_data ) {
			// All sizes have been removed: delete everything.
			$this->delete_optimization_data();
			return;
		}

		if ( count( $remaining_sizes_data ) === count( $data['sizes'] ) ) {
			// Nothing has been removed.
			return;
		}

		$data['sizes'] = $remaining_sizes_data;

		// Update stats.
		$data['stats'] = [
			'original_size'  => 0,
			'optimized_size' => 0,
			'percent'        => 0,
		];

		foreach ( $data['sizes'] as $size_data ) {
			if ( empty( $size_data['success'] ) ) {
				continue;
			}

			$data['stats']['original_size']  += $size_data['original_size'];
			$data['stats']['optimized_size'] += $size_data['optimized_size'];
		}

		$data['stats']['percent'] = round( ( ( $data['stats']['original_size'] - $data['stats']['optimized_size'] ) / $data['stats']['original_size'] ) * 100, 2 );

		update_post_meta( $media_id, '_imagify_data', $data );
	}
}
