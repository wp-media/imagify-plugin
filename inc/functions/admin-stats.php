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
	global $wpdb;
	
	$count = $wpdb->get_var( 
		"SELECT COUNT($wpdb->posts.ID) 
		 FROM $wpdb->posts 
		 WHERE post_type = 'attachment' 
		 	AND post_status != 'trash' 
		 	AND ($wpdb->posts.post_mime_type = 'image/jpeg' OR $wpdb->posts.post_mime_type = 'image/png' OR $wpdb->posts.post_mime_type = 'image/gif')" 
	);
		
	if ( $count > apply_filters( 'imagify_unoptimized_attachment_limit', 10000 ) ) {
		set_transient( IMAGIFY_SLUG . '_large_library', 1 );	
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
	global $wpdb;
	
	$count = $wpdb->get_var( 
		"SELECT COUNT($wpdb->posts.ID) 
		 FROM $wpdb->posts 
		 INNER JOIN $wpdb->postmeta 
		 	ON $wpdb->posts.ID = $wpdb->postmeta.post_id
		 WHERE ($wpdb->posts.post_mime_type = 'image/jpeg' OR $wpdb->posts.post_mime_type = 'image/png' OR $wpdb->posts.post_mime_type = 'image/gif') 
		 	AND ( ( $wpdb->postmeta.meta_key = '_imagify_status' AND CAST($wpdb->postmeta.meta_value AS CHAR) = 'error' )
) 
			AND $wpdb->posts.post_type = 'attachment' 
			AND $wpdb->posts.post_status = 'inherit'" 
	);
	
	return (int) $count;
}

/*
 * Count number of optimized attachments (by Imagify or an other tool before).
 *
 * @since 1.0
 *
 * @return int The number of attachments.
 */
function imagify_count_optimized_attachments() {	
	global $wpdb;
	
	$count = $wpdb->get_var( 
		"SELECT COUNT($wpdb->posts.ID) 
		 FROM $wpdb->posts 
		 INNER JOIN $wpdb->postmeta 
		 	ON $wpdb->posts.ID = $wpdb->postmeta.post_id
		 WHERE ($wpdb->posts.post_mime_type = 'image/jpeg' OR $wpdb->posts.post_mime_type = 'image/png' OR $wpdb->posts.post_mime_type = 'image/gif') 
		 	AND ( ( $wpdb->postmeta.meta_key = '_imagify_status' AND CAST($wpdb->postmeta.meta_value AS CHAR) = 'success' ) OR ( $wpdb->postmeta.meta_key = '_imagify_status' AND CAST($wpdb->postmeta.meta_value AS CHAR) = 'already_optimized' ) ) 
		 	AND $wpdb->posts.post_type = 'attachment' 
		 	AND $wpdb->posts.post_status = 'inherit'" 
	);
			
	return (int) $count;
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
	
	$attachments = $wpdb->get_col( 
		"SELECT pm1.meta_value
		 FROM $wpdb->postmeta as pm1
		 INNER JOIN $wpdb->postmeta as pm2
		 	ON pm1.post_id = pm2.post_id
		 WHERE pm1.meta_key= '_imagify_data'
		       AND ( pm2.meta_key= '_imagify_status' AND pm2.meta_value= 'success' )"
	); 
	
	$attachments 	= array_map( 'maybe_unserialize', (array) $attachments );
	$original_size  = 0;
	$optimized_size = 0;
	$count			= 0;
		
	foreach( $attachments as $k => $attachment_data ) {
		$stats_data    = $attachment_data['stats'];
		$original_data = $attachment_data['sizes']['full'];
		
		// Incremente the original sizes
		$original_size  += ( $original_data['original_size'] ) ? $original_data['original_size'] : 0;
		$optimized_size += ( $original_data['optimized_size'] ) ? $original_data['optimized_size'] : 0;
		
		unset( $attachment_data['sizes']['full'] );
		
		// Incremente the thumbnails sizes
		foreach ( $attachment_data['sizes'] as $size_key => $size_data ) {
			if ( ! empty( $size_data['success'] ) ) {
				$original_size  += ( $size_data['original_size'] ) ? $size_data['original_size'] : 0;
				$optimized_size += ( $size_data['optimized_size'] ) ? $size_data['optimized_size'] : 0;
			}
		}
	}
	
	$data = array(
		'count'			 => count( $attachments ),
		'original_size'  => (int) $original_size,
		'optimized_size' => (int) $optimized_size,
		'percent'		 => ( 0 !== $optimized_size ) ? ceil( ( ( $original_size - $optimized_size ) / $original_size ) * 100 ) : 0
	);
	
	if ( ! empty( $key ) ) {
		return $data[ $key ];
	}

	return $data;
}