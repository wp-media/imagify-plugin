<?php
declare(strict_types=1);

namespace Imagify\Media\Upload;

/**
 * Upload Media Class.
 */
class Upload {
	/**
	 * Adds a dropdown that allows filtering on the attachments Imagify status.
	 *
	 * @return void
	 */
	public function add_imagify_filter_to_attachments_dropdown() {
		$data = [];

		/**
		 * Tell if imagify stats query should run.
		 *
		 * @param bool  $boolean True if the query should be run. False otherwise.
		 */
		if ( apply_filters( 'imagify_display_library_stats', false ) ) {
			$data['optimized']   = imagify_count_optimized_attachments();
			$data['unoptimized'] = imagify_count_unoptimized_attachments();
			$data['errors']      = imagify_count_error_attachments();

		}

		$status      = isset( $_GET['imagify-status'] ) ? wp_unslash( $_GET['imagify-status'] ) : 0; // WPCS: CSRF ok.
		$options     = array(
			'optimized'   => _x( 'Optimized', 'Media Files', 'imagify' ),
			'unoptimized' => _x( 'Unoptimized', 'Media Files', 'imagify' ),
			'errors'      => _x( 'Errors', 'Media Files', 'imagify' ),
		);

		echo '<label class="screen-reader-text" for="filter-by-optimization-status">' . __( 'Filter by status', 'imagify' ) . '</label>';
		echo '<select id="filter-by-optimization-status" name="imagify-status">';
		echo '<option value="0" selected="selected">' . __( 'All Media Files', 'imagify' ) . '</option>';

		foreach ( $options as $value => $label ) {
			$filter_value = isset( $data[ $value ] ) ? ' (' . $data[ $value ] . ')' : '';
			echo '<option value="' . $value . '" ' . selected( $status, $value, false ) . '>' . $label . $filter_value . '</option>';
		}
		echo '</select>&nbsp;';
	}
}
