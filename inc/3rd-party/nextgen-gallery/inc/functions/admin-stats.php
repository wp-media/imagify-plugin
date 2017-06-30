<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Count number of attachments.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 *
 * @return int The number of attachments.
 */
function imagify_ngg_count_attachments() {
	global $wpdb;
	static $count;

	if ( isset( $count ) ) {
		return $count;
	}

	$table_name = $wpdb->prefix . 'ngg_pictures';
	$count      = (int) $wpdb->get_var( "SELECT COUNT($table_name.pid) FROM $table_name" ); // WPCS: unprepared SQL ok.

	return $count;
}

/**
 * Count number of optimized attachments with an error.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 *
 * @return int The number of attachments.
 */
function imagify_ngg_count_error_attachments() {
	static $count;

	if ( isset( $count ) ) {
		return $count;
	}

	$count = (int) imagify_ngg_db()->get_column_by( 'COUNT(*)', 'status', 'error' );

	return $count;
}

/**
 * Count number of optimized attachments (by Imagify or an other tool before).
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 *
 * @return int The number of attachments.
 */
function imagify_ngg_count_optimized_attachments() {
	static $count;

	if ( isset( $count ) ) {
		return $count;
	}

	$count = (int) imagify_ngg_db()->get_column_by( 'COUNT(*)', 'status', 'success' );

	return $count;
}

/**
 * Count number of unoptimized attachments.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @return int The number of attachments.
 */
function imagify_ngg_count_unoptimized_attachments() {
	return imagify_ngg_count_attachments() - imagify_ngg_count_optimized_attachments() - imagify_ngg_count_error_attachments();
}

/**
 * Count percent of optimized attachments.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @return int The percent of optimized attachments.
 */
function imagify_ngg_percent_optimized_attachments() {
	$total_attachments           = imagify_ngg_count_attachments();
	$total_optimized_attachments = imagify_ngg_count_optimized_attachments();

	return $total_attachments && $total_optimized_attachments ? round( 100 - ( ( $total_attachments - $total_optimized_attachments ) / $total_attachments ) * 100 ) : 0;
}

/**
 * Count percent, original & optimized size of all images optimized by Imagify.
 *
 * @since  1.5
 * @since  1.6.7 Revamped to handle huge libraries.
 * @author Jonathan Buttigieg
 *
 * @param  bool|array $attachments An array containing the keys 'count', 'original_size', and 'optimized_size', or an array of attachments (back compat', deprecated), or false.
 * @return array An array containing the keys 'count', 'original_size', and 'optimized_size'.
 */
function imagify_ngg_count_saving_data( $attachments ) {
	global $wpdb;

	if ( is_array( $attachments ) ) {
		return $attachments;
	}

	/**
	 * Filter the query to get all optimized NGG attachments.
	 * 3rd party will be able to override the result.
	 *
	 * @since 1.6.7
	 *
	 * @param bool|array $attachments An array containing the keys ('count', 'original_size', and 'optimized_size'), or false.
	 */
	$attachments = apply_filters( 'imagify_ngg_count_saving_data', false );

	if ( is_array( $attachments ) ) {
		return $attachments;
	}

	$table_name     = $wpdb->ngg_imagify_data;
	$original_size  = 0;
	$optimized_size = 0;
	$count          = 0;

	/** This filter is documented in /inc/functions/admin-stats.php */
	$limit  = apply_filters( 'imagify_count_saving_data_limit', 15000 );
	$limit  = absint( $limit );
	$offset = 0;

	$attachments = $wpdb->get_col( // WPCS: unprepared SQL ok.
		"SELECT $table_name.data
		 FROM {$wpdb->ngg_imagify_data}
		 WHERE status = 'success'
		 LIMIT $offset, $limit"
	);
	$wpdb->flush();

	while ( $attachments ) {
		$attachments = array_map( 'maybe_unserialize', $attachments );

		foreach ( $attachments as $attachment_data ) {
			if ( ! $attachment_data ) {
				continue;
			}

			++$count;
			$original_data = $attachment_data['sizes']['full'];

			// Increment the original sizes.
			$original_size  += $original_data['original_size']  ? $original_data['original_size']  : 0;
			$optimized_size += $original_data['optimized_size'] ? $original_data['optimized_size'] : 0;

			unset( $attachment_data['sizes']['full'], $original_data );

			// Increment the thumbnails sizes.
			foreach ( $attachment_data['sizes'] as $size_data ) {
				if ( ! empty( $size_data['success'] ) ) {
					$original_size  += $size_data['original_size']  ? $size_data['original_size']  : 0;
					$optimized_size += $size_data['optimized_size'] ? $size_data['optimized_size'] : 0;
				}
			}

			unset( $size_data );
		}

		unset( $attachment_data );

		if ( count( $attachments ) === $limit ) {
			// Unless we are really unlucky, we still have attachments to fetch.
			$offset += $limit;

			$attachments = $wpdb->get_col( // WPCS: unprepared SQL ok.
				"SELECT $table_name.data
				 FROM {$wpdb->ngg_imagify_data}
				 WHERE status = 'success'
				 LIMIT $offset, $limit"
			);
			$wpdb->flush();
		} else {
			// Save one request, don't go back to the beginning of the loop.
			$attachments = array();
		}
	} // End while().

	return array(
		'count'          => $count,
		'original_size'  => $original_size,
		'optimized_size' => $optimized_size,
	);
}
