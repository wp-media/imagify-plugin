<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/*
 * Count number of attachments.
 *
 * @since 1.0
 *
 * @return int The number of attachments.
 */
function imagify_count_attachments() {
	$count = wp_count_attachments( get_imagify_mime_type() );
	$count = get_object_vars( $count );
	$count = array_sum( $count );
	return (int) $count;
}

/*
 * Count number of execeed attachments (size > 5MB).
 *
 * @since 1.0
 *
 * @return int The number of exceeded attachments.
 */
function imagify_count_exceeding_attachments() {
	$count = 0;
	$query = new WP_Query(
		array(
			'post_type'              => 'attachment',
			'post_status'			 => 'inherit',
			'post_mime_type'         => get_imagify_mime_type(),
			'posts_per_page'         => -1,
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
			'fields'                 => 'ids'
		)
	);
	$attachments = (array) $query->posts;

	foreach( $attachments as $attachment_id ) {
		$attachment = new Imagify_Attachment( $attachment_id );

		// Check if the attachment extension is allowed
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			continue;
		}
		
		if ( $attachment->is_exceeded() ) {
			$count++;
		}
	}
	
	return (int) $count;
}

/*
 * Count number of optimized attachments with an error.
 *
 * @since 1.0
 *
 * @return int The number of attachments.
 */
function imagify_count_error_attachments() {
	$query = new WP_Query(
		array(
			'post_type'              => 'attachment',
			'post_status'			 => 'inherit',
			'post_mime_type'         => get_imagify_mime_type(),
			'meta_key'				 => '_imagify_status',
			'meta_value'			 => 'error',
			'posts_per_page'         => -1,
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
			'fields'                 => 'ids'
		)
	);

	return (int) $query->post_count;
}

/*
 * Count number of optimized attachments (by Imagify or an other tool before).
 *
 * @since 1.0
 *
 * @return int The number of attachments.
 */
function imagify_count_optimized_attachments() {
	$query = new WP_Query(
		array(
			'post_type'              => 'attachment',
			'post_status'			 => 'inherit',
			'post_mime_type'         => get_imagify_mime_type(),
			'meta_query'      => array(
			'relation'    => 'or',
				array(
					'key'     => '_imagify_status',
					'value'   => 'success',
					'compare' => '='
				),
				array(
					'key'     => '_imagify_status',
					'value'   => 'already_optimized',
					'compare' => '='
				)
			),
			'posts_per_page'         => -1,
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
			'fields'                 => 'ids'
		)
	);

	return (int) $query->post_count;
}

/*
 * Count number of unoptimized attachments.
 *
 * @since 1.0
 *
 * @return int The number of attachments.
 */
function imagify_count_unoptimized_attachments() {
	$count = imagify_count_attachments() - imagify_count_optimized_attachments() - imagify_count_error_attachments();
	return (int) $count;
}

/*
 * Count percent of optimized attachments.
 *
 * @since 1.0
 *
 * @return int The percent of optimized attachments.
 */
function imagify_percent_optimized_attachments() {
	$total_attachments			   = imagify_count_attachments();
	$total_optimized_attachments   = imagify_count_optimized_attachments();

	$percent = ( 0 !== $total_attachments ) ? round( ( 100 - ( ( $total_attachments - ( $total_optimized_attachments ) ) / $total_attachments ) * 100 ) ) : 0;

	return $percent;
}

/*
 * Count percent, original & optimized size of all images optimized by Imagify.
 *
 * @since 1.0
 *
 * @return array An array containing the optimization data.
 */
function imagify_count_saving_data( $key = '' ) {
	global $wpdb;

	$original_size  = 0;
	$optimized_size = 0;
	$query = new WP_Query(
		array(
			'post_type'              => 'attachment',
			'post_status'			 => 'inherit',
			'post_mime_type'         => get_imagify_mime_type(),
			'meta_key'				 => '_imagify_status',
			'meta_value'			 => 'success',
			'posts_per_page'         => -1,
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
			'fields'                 => 'ids'
		)
	);
	$attachments = (array) $query->posts;

	foreach( $attachments as $attachment_id ) {
		$attachment = new Imagify_Attachment( $attachment_id );

		// Check if the attachment extension is allowed
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			continue;
		}

		$stats_data    = $attachment->get_stats_data();
		$original_data = $attachment->get_size_data( 'full' );

		// Incremente the original sizes
		if ( $attachment->is_optimized() ) {
			$original_size  += ( $original_data['original_size'] ) ? $original_data['original_size'] : 0;
			$optimized_size += ( $original_data['optimized_size'] ) ? $original_data['optimized_size'] : 0;
		}
		
		$metadata = wp_get_attachment_metadata( $attachment_id );
		$sizes    = ( isset( $metadata['sizes'] ) ) ? (array) $metadata['sizes'] : array();

		// Incremente the thumbnails sizes
		foreach ( $sizes as $size_key => $size_data ) {
			$size_data = $attachment->get_size_data( $size_key );
			if ( ! empty( $size_data['success'] ) ) {
				$original_size  += ( $size_data['original_size'] ) ? $size_data['original_size'] : 0;
				$optimized_size += ( $size_data['optimized_size'] ) ? $size_data['optimized_size'] : 0;
			}
		}
	}

	$data = array(
		'count'			 => $query->post_count,
		'original_size'  => (int) $original_size,
		'optimized_size' => (int) $optimized_size,
		'percent'		 => ( 0 !== $optimized_size ) ? ceil( ( ( $original_size - $optimized_size ) / $original_size ) * 100 ) : 0
	);

	if ( ! empty( $key ) ) {
		return $data[ $key ];
	}

	return $data;
}