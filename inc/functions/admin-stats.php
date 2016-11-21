<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/*
 * Count number of attachments.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @return int The number of attachments.
 */
function imagify_count_attachments() {
	global $wpdb;

	/**
	 * Filter the number of attachments
	 * 3rd party will be able to override the result
	 *
	 * @since 1.5
	*/
	$count = apply_filters( 'imagify_count_attachments', false );
	
	if ( false !== $count ) {
		return $count;
	}

	static $count;

	if ( ! $count ) {
		$count = $wpdb->get_var(
			"SELECT COUNT($wpdb->posts.ID)
			 FROM $wpdb->posts
			 WHERE post_type = 'attachment'
			 	AND post_status != 'trash'
			 	AND ($wpdb->posts.post_mime_type = 'image/jpeg' OR $wpdb->posts.post_mime_type = 'image/png' OR $wpdb->posts.post_mime_type = 'image/gif')"
		);

		if ( $count > apply_filters( 'imagify_unoptimized_attachment_limit', 10000 ) ) {
			set_transient(  'imagify_large_library', 1 );
		}
	}

	return (int) $count;
}

/*
 * Count number of optimized attachments with an error.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @return int The number of attachments.
 */
function imagify_count_error_attachments() {
	global $wpdb;

	/**
	 * Filter the number of optimized attachments with an error
	 * 3rd party will be able to override the result
	 *
	 * @since 1.5
	*/
	$count = apply_filters( 'imagify_count_error_attachments', false );
	
	if ( false !== $count ) {
		return $count;
	}
	
	static $count;
	
	if ( ! $count ) {
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
	}

	return (int) $count;
}

/*
 * Count number of optimized attachments (by Imagify or an other tool before).
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @return int The number of attachments.
 */
function imagify_count_optimized_attachments() {
	global $wpdb;

	/**
	 * Filter the number of optimized attachments
	 * 3rd party will be able to override the result
	 *
	 * @since 1.5
	*/
	$count = apply_filters( 'imagify_count_optimized_attachments', false );
	
	if ( false !== $count ) {
		return $count;
	}
	
	static $count;
	
	if ( ! $count ) {
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
	}

	return (int) $count;
}

/*
 * Count number of unoptimized attachments.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @return int The number of attachments.
 */
function imagify_count_unoptimized_attachments() {
	/**
	 * Filter the number of unoptimized attachments
	 * 3rd party will be able to override the result
	 *
	 * @since 1.5
	*/
	$count = apply_filters( 'imagify_count_unoptimized_attachments', false );
	
	if ( false !== $count ) {
		return $count;
	}
	
	static $count;
	
	if ( ! $count ) {
		$count = imagify_count_attachments() - imagify_count_optimized_attachments() - imagify_count_error_attachments();
	}

	return (int) $count;
}

/*
 * Count percent of optimized attachments.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @return int The percent of optimized attachments.
 */
function imagify_percent_optimized_attachments() {
	/**
	 * Filter the percent of optimized attachments
	 * 3rd party will be able to override the result
	 *
	 * @since 1.5
	*/
	$percent = apply_filters( 'imagify_percent_optimized_attachments', false );

	if ( false === $percent ) {
		$total_attachments			   = imagify_count_attachments();
		$total_optimized_attachments   = imagify_count_optimized_attachments();

		$percent = ( 0 !== $total_attachments ) ? round( ( 100 - ( ( $total_attachments - ( $total_optimized_attachments ) ) / $total_attachments ) * 100 ) ) : 0;
	}

	return $percent;
}

/*
 * Count percent, original & optimized size of all images optimized by Imagify.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @return array An array containing the optimization data.
 */
function imagify_count_saving_data( $key = '' ) {
	global $wpdb;

	/**
	 * Filter the query to get all optimized attachments
	 * 3rd party will be able to override the result
	 *
	 * @since 1.5
	*/
	$attachments = apply_filters( 'imagify_count_saving_data', false );

	if ( false === $attachments ) {
		$attachments = $wpdb->get_col(
			"SELECT pm1.meta_value
			 FROM $wpdb->postmeta as pm1
			 INNER JOIN $wpdb->postmeta as pm2
			 	ON pm1.post_id = pm2.post_id
			 WHERE pm1.meta_key= '_imagify_data'
			       AND ( pm2.meta_key= '_imagify_status' AND pm2.meta_value= 'success' )"
		);
	}

	$attachments = array_map( 'maybe_unserialize', (array) $attachments );

	$original_size  = 0;
	$optimized_size = 0;
	$count			= 0;

	foreach ( $attachments as $attachment_data ) {
		if ( ! $attachment_data ) {
			continue;
		}
		
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

/**
 * Returns the estimated total size of the images not optimized
 *
 * We estimate the total size of the images in the library by getting the latest 250 images and their thumbnails
 * add up their filesizes, and doing some maths to get the total size
 * 
 * @return (int) the current estimated total size of images not optimized
 *
 * @since  1.6
 * @author Remy Perona
 */
function imagify_calculate_total_size_images_library() {
    global $wpdb;

    $images_id = $wpdb->get_results( "
        SELECT ID FROM $wpdb->posts
        WHERE (post_mime_type LIKE 'image/%')
        AND post_type = 'attachment' AND (post_status = 'inherit')
        LIMIT 250
    ", ARRAY_A );

    $images_id = wp_list_pluck( $images_id, 'ID' );

    if ( ! (bool) $images_id ) {
        return 0;
    }

    $partial_total_images = count( $images_id );
    $total_images         = imagify_count_attachments();
    $total_size_images    = imagify_calculate_total_image_size( $images_id, $partial_total_images, $total_images );

    return (int) $total_size_images;
 }

/**
 * Returns the estimated average size of the images uploaded per month
 *
 * We estimate the average size of the images uploaded in the library per month by getting the latest 250 images and their thumbnails
 * for the 3 latest months, add up their filesizes, and doing some maths to get the total average size
 * 
 * @return (int) the current estimated average size of images uploaded per month
 *
 * @since  1.6
 * @author Remy Perona
 */
function imagify_calculate_average_size_images_per_month() {
    $imagify_mime_types = get_imagify_mime_type();

    $partial_images_uploaded_last_month = new WP_Query( array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'post_mime_type' => $imagify_mime_types,
        'posts_per_page' => 250,
        'date_query'     => array(
            array(
                'before' => 'now',
                'after'  => '1 month ago'
            )            
        ),
        'fields'         => 'ids'
    ) );

    $partial_images_uploaded_two_months_ago = new WP_Query( array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'post_mime_type' => $imagify_mime_types,
        'posts_per_page' => 250,
        'date_query'     => array(
            array(
                'before' => '1 month ago',
                'after'  => '2 months ago'
            )            
        ),
        'fields'         => 'ids'
    ) );

    $partial_images_uploaded_three_months_ago = new WP_Query( array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'post_mime_type' => $imagify_mime_types,
        'posts_per_page' => 250,
        'date_query'     => array(
            array(
                'before' => '2 months ago',
                'after'  => '3 months ago'
            )            
        ),
        'fields'         => 'ids'
    ) );

    $images_uploaded_id = new WP_Query( array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'post_mime_type' => $imagify_mime_types,
        'posts_per_page' => -1,
        'date_query'     => array(
            array(
                'before' => 'now',
                'after'  => '3 months ago'
            )            
        ),
        'fields'         => 'ids'
    ) );

    $partial_images_uploaded_id = array_merge( $partial_images_uploaded_last_month->posts, $partial_images_uploaded_two_months_ago->posts, $partial_images_uploaded_three_months_ago->posts );

    if ( ! (bool) $partial_images_uploaded_id ) {
        return 0;
    }

    if ( ! (bool) $images_uploaded_id->posts ) {
        return 0;
    }

    $partial_total_images_uploaded = count( $partial_images_uploaded_id );
    $total_images_uploaded         = $images_uploaded_id->post_count;
    $average_size_images_per_month = imagify_calculate_total_image_size( $partial_images_uploaded_id, $partial_total_images_uploaded, $total_images_uploaded ) / 3;
    
    return $average_size_images_per_month;
}

/**
 * Returns the estimated total size of images
 * 
 * @param array $images_id Array of images ID
 * @param int $partial_total_images The number of images we're doing the calculation with
 * @param int $total_images The total number of images
 * @return int The estimated total size of images
 *
 * @since  1.6
 * @author Remy Perona
 */
function imagify_calculate_total_image_size( $images_id, $partial_total_images, $total_images ) {
    global $wpdb;
    $partial_size_images               = '';
    $partial_total_intermediate_images = '';
    $sql_ids                           = implode( ',', $images_id );

    // Get attachments filename
	$attachments_filename = $wpdb->get_results( 
		"SELECT pm.post_id as id, pm.meta_value as value
		 FROM $wpdb->postmeta as pm
		 WHERE pm.meta_key= '_wp_attached_file'
		 	   AND pm.post_id IN ($sql_ids)
		 ORDER BY pm.post_id DESC"
		 , ARRAY_A	
	);

    $attachments_filename = imagify_query_results_combine( $images_id, $attachments_filename );

    // Get attachments data
	$attachments_data = $wpdb->get_results( 
		"SELECT pm.post_id as id, pm.meta_value as value
		 FROM $wpdb->postmeta as pm
		 WHERE pm.meta_key= '_wp_attachment_metadata'
		 	   AND pm.post_id IN ($sql_ids)
		 ORDER BY pm.post_id DESC"
		 , ARRAY_A	
	);
	
	$attachments_data = imagify_query_results_combine( $images_id, $attachments_data );	
	$attachments_data = array_map( 'maybe_unserialize', $attachments_data );

    // Get imagify data
	$imagify_data = $wpdb->get_results( 
		"SELECT pm.post_id as id, pm.meta_value as value
		 FROM $wpdb->postmeta as pm
		 WHERE pm.meta_key= '_imagify_data'
		 	   AND pm.post_id IN ($sql_ids)
		 ORDER BY pm.post_id DESC"
		 , ARRAY_A	
	);
	
	$imagify_data = imagify_query_results_combine( $images_id, $imagify_data );	
	$imagify_data = array_map( 'maybe_unserialize', $imagify_data );
	
	// Get attachments status
	$attachments_status = $wpdb->get_results( 
		"SELECT pm.post_id as id, pm.meta_value as value
		 FROM $wpdb->postmeta as pm
		 WHERE pm.meta_key= '_imagify_status'
		 	   AND pm.post_id IN ($sql_ids)
		 ORDER BY pm.post_id DESC"
		, ARRAY_A		
	);
	
	$attachments_status = imagify_query_results_combine( $images_id, $attachments_status );

    foreach( $images_id as $image_id ) {
        $attachment_status = ( isset( $attachments_status[ $image_id ] ) ) ? $attachments_status[ $image_id ] : false;

        if ( $attachments_status === 'success' ) {
            $imagify_data      = ( isset( $imagify_data[ $image_id ] ) ) ? $imagify_data[ $image_id ] : false;
            $partial_size_images += $imagify_data['stats']['original_size'];
            foreach ( $attachment_data['sizes'] as $size ) {
                $partial_total_intermediate_images++;
            }
            continue;
        }

        $attachment_metadata = ( isset( $attachments_data[ $image_id ] ) ) ? $attachments_data[ $image_id ] : false;
        $sizes               = ( isset( $attachment_metadata['sizes'] ) ) ? (array) $attachment_metadata['sizes'] : array();

        /** This filter is documented in inc/functions/process.php */
		$full_image = apply_filters( 'imagify_file_path', get_imagify_attached_file( $attachments_filename[ $image_id ] ) );
        $partial_size_images += filesize( $full_image );

        if ( (bool) $sizes ) {
            foreach ( $sizes as $size_key => $size_data ) {
                if (  array_key_exists( $size_key, get_imagify_option( 'disallowed-sizes', array() ) ) && ! imagify_is_active_for_network() ) {
                    continue;
                }
				
                $thumbnail_path = trailingslashit( dirname( $full_image ) ) . $size_data['file'];
				
				if ( file_exists( $thumbnail_path ) ) {
					$partial_size_images += filesize( $thumbnail_path );
					$partial_total_intermediate_images++;	
				}
            }
        }
    }

    $intermediate_images_per_image  = $partial_total_intermediate_images / $partial_total_images;
    $average_size_images            = $partial_size_images / ( $partial_total_images + $partial_total_intermediate_images );
    $total_size_images              = $average_size_images * ( $total_images + ( $intermediate_images_per_image * $total_images ) );

    return $total_size_images;
}