<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Count number of attachments.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @return int The number of attachments.
 */
function imagify_count_attachments() {
	global $wpdb;
	static $count;

	/**
	 * Filter the number of attachments.
	 * 3rd party will be able to override the result.
	 *
	 * @since 1.5
	 *
	 * @param int|bool $pre_count Default is false. Provide an integer.
	 */
	$pre_count = apply_filters( 'imagify_count_attachments', false );

	if ( false !== $pre_count ) {
		return (int) $pre_count;
	}

	if ( isset( $count ) ) {
		return $count;
	}

	$count = (int) $wpdb->get_var( "
		SELECT COUNT( $wpdb->posts.ID )
		FROM $wpdb->posts
		WHERE post_type = 'attachment'
			AND post_status != 'trash'
			AND ( $wpdb->posts.post_mime_type = 'image/jpeg' OR $wpdb->posts.post_mime_type = 'image/png' OR $wpdb->posts.post_mime_type = 'image/gif' )"
	);

	if ( $count > apply_filters( 'imagify_unoptimized_attachment_limit', 10000 ) ) {
		set_transient( 'imagify_large_library', 1 );
	}

	return $count;
}

/**
 * Count number of optimized attachments with an error.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @return int The number of attachments.
 */
function imagify_count_error_attachments() {
	global $wpdb;
	static $count;

	/**
	 * Filter the number of optimized attachments with an error.
	 * 3rd party will be able to override the result.
	 *
	 * @since 1.5
	 *
	 * @param int|bool $pre_count Default is false. Provide an integer.
	 */
	$pre_count = apply_filters( 'imagify_count_error_attachments', false );

	if ( false !== $pre_count ) {
		return (int) $pre_count;
	}

	if ( isset( $count ) ) {
		return $count;
	}

	$count = (int) $wpdb->get_var( "
		SELECT COUNT( $wpdb->posts.ID )
		FROM $wpdb->posts
		INNER JOIN $wpdb->postmeta
			ON $wpdb->posts.ID = $wpdb->postmeta.post_id
		WHERE ( $wpdb->posts.post_mime_type = 'image/jpeg' OR $wpdb->posts.post_mime_type = 'image/png' OR $wpdb->posts.post_mime_type = 'image/gif' )
			AND $wpdb->postmeta.meta_key = '_imagify_status' AND CAST( $wpdb->postmeta.meta_value AS CHAR ) = 'error'
			AND $wpdb->posts.post_type = 'attachment'
			AND $wpdb->posts.post_status = 'inherit'"
	);

	return $count;
}

/**
 * Count number of optimized attachments (by Imagify or an other tool before).
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @return int The number of attachments.
 */
function imagify_count_optimized_attachments() {
	global $wpdb;
	static $count;

	/**
	 * Filter the number of optimized attachments.
	 * 3rd party will be able to override the result.
	 *
	 * @since 1.5
	 *
	 * @param int|bool $pre_count Default is false. Provide an integer.
	 */
	$pre_count = apply_filters( 'imagify_count_optimized_attachments', false );

	if ( false !== $pre_count ) {
		return (int) $pre_count;
	}

	if ( isset( $count ) ) {
		return $count;
	}

	$count = (int) $wpdb->get_var( "
		SELECT COUNT( $wpdb->posts.ID )
		FROM $wpdb->posts
		INNER JOIN $wpdb->postmeta
			ON $wpdb->posts.ID = $wpdb->postmeta.post_id
		WHERE ( $wpdb->posts.post_mime_type = 'image/jpeg' OR $wpdb->posts.post_mime_type = 'image/png' OR $wpdb->posts.post_mime_type = 'image/gif' )
			AND ( ( $wpdb->postmeta.meta_key = '_imagify_status' AND CAST( $wpdb->postmeta.meta_value AS CHAR ) = 'success' ) OR ( $wpdb->postmeta.meta_key = '_imagify_status' AND CAST( $wpdb->postmeta.meta_value AS CHAR ) = 'already_optimized' ) )
			AND $wpdb->posts.post_type = 'attachment'
			AND $wpdb->posts.post_status = 'inherit'"
	);

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
function imagify_count_unoptimized_attachments() {
	/**
	 * Filter the number of unoptimized attachments.
	 * 3rd party will be able to override the result.
	 *
	 * @since 1.5
	 *
	 * @param int|bool $pre_count Default is false. Provide an integer.
	 */
	$pre_count = apply_filters( 'imagify_count_unoptimized_attachments', false );

	if ( false !== $pre_count ) {
		return (int) $pre_count;
	}

	return imagify_count_attachments() - imagify_count_optimized_attachments() - imagify_count_error_attachments();
}

/**
 * Count percent of optimized attachments.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @return int The percent of optimized attachments.
 */
function imagify_percent_optimized_attachments() {
	/**
	 * Filter the percent of optimized attachments.
	 * 3rd party will be able to override the result.
	 *
	 * @since 1.5
	 *
	 * @param int|bool $percent Default is false. Provide an integer.
	 */
	$percent = apply_filters( 'imagify_percent_optimized_attachments', false );

	if ( false !== $percent ) {
		return (int) $percent;
	}

	$total_attachments           = imagify_count_attachments();
	$total_optimized_attachments = imagify_count_optimized_attachments();

	return $total_attachments && $total_optimized_attachments ? round( 100 - ( ( $total_attachments - $total_optimized_attachments ) / $total_attachments ) * 100 ) : 0;
}

/**
 * Count percent, original & optimized size of all images optimized by Imagify.
 *
 * @since  1.0
 * @since  1.6.7 Revamped to handle huge libraries.
 * @author Jonathan Buttigieg
 *
 * @param  string $key What data to return. Choices are between 'count', 'original_size', 'optimized_size', and 'percent'. If left empty, the whole array is returned.
 * @return array|int   An array containing the optimization data. A single data if $key is provided.
 */
function imagify_count_saving_data( $key = '' ) {
	global $wpdb;

	/**
	 * Filter the query to get all optimized attachments.
	 * 3rd party will be able to override the result.
	 *
	 * @since 1.5
	 * @since 1.6.7 This filter should return an array containing the following keys: 'count', 'original_size', and 'optimized_size'.
	 *
	 * @param bool|array $attachments An array containing the keys ('count', 'original_size', and 'optimized_size'), or an array of attachments (back compat', deprecated), or false.
	 */
	$attachments = apply_filters( 'imagify_count_saving_data', false );

	$original_size  = 0;
	$optimized_size = 0;
	$count          = 0;

	if ( is_array( $attachments ) ) {
		/**
		 * Bypass.
		 */
		if ( isset( $attachments['count'], $attachments['original_size'], $attachments['optimized_size'] ) ) {
			/**
			 * We have the results we need.
			 */
			$attachments['percent'] = $attachments['optimized_size'] && $attachments['original_size'] ? ceil( ( ( $attachments['original_size'] - $attachments['optimized_size'] ) / $attachments['original_size'] ) * 100 ) : 0;

			return $attachments;
		}

		/**
		 * Back compat'.
		 * The following shouldn't be used. Sites with a huge library won't like it.
		 */
		$attachments = array_map( 'maybe_unserialize', (array) $attachments );

		if ( $attachments ) {
			foreach ( $attachments as $attachment_data ) {
				if ( ! $attachment_data ) {
					continue;
				}

				++$count;
				$original_data = $attachment_data['sizes']['full'];

				// Increment the original sizes.
				$original_size  += $original_data['original_size']  ? $original_data['original_size']  : 0;
				$optimized_size += $original_data['optimized_size'] ? $original_data['optimized_size'] : 0;

				unset( $attachment_data['sizes']['full'] );

				// Increment the thumbnails sizes.
				foreach ( $attachment_data['sizes'] as $size_data ) {
					if ( ! empty( $size_data['success'] ) ) {
						$original_size  += $size_data['original_size']  ? $size_data['original_size']  : 0;
						$optimized_size += $size_data['optimized_size'] ? $size_data['optimized_size'] : 0;
					}
				}
			}
		}
	} else {

		/**
		 * Filter the chunk size of the requests fetching the data.
		 * 15,000 seems to be a good balance between memory used, speed, and number of DB hits.
		 *
		 * @param int $limit The maximum number of elements per chunk.
		 */
		$limit = apply_filters( 'imagify_count_saving_data_limit', 15000 );
		$limit = absint( $limit );

		$attachment_ids = $wpdb->get_col(
			"SELECT post_id
			 FROM $wpdb->postmeta
			 WHERE meta_key = '_imagify_status'
			     AND meta_value = 'success'
			 ORDER BY CAST( post_id AS UNSIGNED )"
		);
		$wpdb->flush();

		$attachment_ids = array_map( 'absint', $attachment_ids );
		$attachment_ids = array_chunk( $attachment_ids, $limit );

		while ( $attachment_ids ) {
			$limit_ids = array_shift( $attachment_ids );
			$limit_ids = implode( ',', $limit_ids );

			$attachments = $wpdb->get_col( // WPCS: unprepared SQL ok.
				"SELECT meta_value
				 FROM $wpdb->postmeta
				 WHERE post_id IN ( $limit_ids )
				    AND meta_key = '_imagify_data'"
			);
			$wpdb->flush();

			unset( $limit_ids );

			if ( ! $attachments ) {
				// Uh?!
				continue;
			}

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

			unset( $attachments, $attachment_data );
		} // End while().
	} // End if().

	$data = array(
		'count'          => $count,
		'original_size'  => $original_size,
		'optimized_size' => $optimized_size,
		'percent'        => $original_size && $optimized_size ? ceil( ( ( $original_size - $optimized_size ) / $original_size ) * 100 ) : 0,
	);

	if ( ! empty( $key ) ) {
		return isset( $data[ $key ] ) ? $data[ $key ] : 0;
	}

	return $data;
}

/**
 * Returns the estimated total size of the images not optimized.
 *
 * We estimate the total size of the images in the library by getting the latest 250 images and their thumbnails
 * add up their filesizes, and doing some maths to get the total size.
 *
 * @since  1.6
 * @author Remy Perona
 *
 * @return int The current estimated total size of images not optimized.
 */
function imagify_calculate_total_size_images_library() {
	global $wpdb;

	$image_ids = $wpdb->get_results( "
		SELECT ID FROM $wpdb->posts
		WHERE ( post_mime_type LIKE 'image/%' )
		AND post_type = 'attachment' AND ( post_status = 'inherit' )
		LIMIT 250
	", ARRAY_A );

	$image_ids = wp_list_pluck( $image_ids, 'ID' );

	if ( ! $image_ids ) {
		return 0;
	}

	$partial_total_images = count( $image_ids );
	$total_images         = imagify_count_attachments();
	$total_size_images    = imagify_calculate_total_image_size( $image_ids, $partial_total_images, $total_images );

	return $total_size_images;
}

/**
 * Returns the estimated average size of the images uploaded per month.
 *
 * We estimate the average size of the images uploaded in the library per month by getting the latest 250 images and their thumbnails
 * for the 3 latest months, add up their filesizes, and doing some maths to get the total average size.
 *
 * @since  1.6
 * @author Remy Perona
 *
 * @return int The current estimated average size of images uploaded per month.
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
				'after'  => '1 month ago',
			),
		),
		'fields'         => 'ids',
	) );

	$partial_images_uploaded_two_months_ago = new WP_Query( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_mime_type' => $imagify_mime_types,
		'posts_per_page' => 250,
		'date_query'     => array(
			array(
				'before' => '1 month ago',
				'after'  => '2 months ago',
			),
		),
		'fields'         => 'ids',
	) );

	$partial_images_uploaded_three_months_ago = new WP_Query( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_mime_type' => $imagify_mime_types,
		'posts_per_page' => 250,
		'date_query'     => array(
			array(
				'before' => '2 months ago',
				'after'  => '3 months ago',
			),
		),
		'fields'         => 'ids',
	) );

	$partial_images_uploaded_id = array_merge( $partial_images_uploaded_last_month->posts, $partial_images_uploaded_two_months_ago->posts, $partial_images_uploaded_three_months_ago->posts );

	if ( ! $partial_images_uploaded_id ) {
		return 0;
	}

	$images_uploaded_id = new WP_Query( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_mime_type' => $imagify_mime_types,
		'posts_per_page' => -1,
		'date_query'     => array(
			array(
				'before' => 'now',
				'after'  => '3 months ago',
			),
		),
		'fields'         => 'ids',
	) );

	if ( ! $images_uploaded_id->posts ) {
		return 0;
	}

	$partial_total_images_uploaded = count( $partial_images_uploaded_id );
	$total_images_uploaded         = $images_uploaded_id->post_count;
	$average_size_images_per_month = imagify_calculate_total_image_size( $partial_images_uploaded_id, $partial_total_images_uploaded, $total_images_uploaded ) / 3;

	return $average_size_images_per_month;
}

/**
 * Returns the estimated total size of images.
 *
 * @since  1.6
 * @author Remy Perona
 *
 * @param  array $image_ids            Array of image IDs.
 * @param  int   $partial_total_images The number of images we're doing the calculation with.
 * @param  int   $total_images         The total number of images.
 * @return int                         The estimated total size of images.
 */
function imagify_calculate_total_image_size( $image_ids, $partial_total_images, $total_images ) {
	global $wpdb;

	$image_ids = array_filter( array_map( 'absint', $image_ids ) );

	if ( ! $image_ids ) {
		return 0;
	}

	$results = imagify_get_wpdb_metas( array(
		// Get attachments filename.
		'filenames'    => '_wp_attached_file',
		// Get attachments data.
		'data'         => '_wp_attachment_metadata',
		// Get Imagify data.
		'imagify_data' => '_imagify_data',
		// Get attachments status.
		'statuses'     => '_imagify_status',
	), $image_ids );

	// Total unoptimized size.
	$partial_size_images               = 0;
	// Total number of thumbnails.
	$partial_total_intermediate_images = 0;

	$is_active_for_network = imagify_is_active_for_network();
	$disallowed_sizes      = array_filter( (array) get_imagify_option( 'disallowed-sizes', array() ) );

	foreach ( $image_ids as $i => $image_id ) {
		$attachment_status = isset( $results['statuses'][ $image_id ] ) ? $results['statuses'][ $image_id ] : false;

		if ( 'success' === $attachment_status ) {
			$partial_size_images               += isset( $results['imagify_data'][ $image_id ]['stats']['original_size'] ) ? $results['imagify_data'][ $image_id ]['stats']['original_size'] : 0;
			$partial_total_intermediate_images += count( $results['imagify_data'][ $image_id ]['sizes'] );
			unset( $image_ids[ $i ], $results['filenames'][ $image_id ], $results['data'][ $image_id ], $results['imagify_data'][ $image_id ], $results['statuses'][ $image_id ] );
			continue;
		}

		// Create an array containing all this attachment files.
		$files = array(
			'full' => get_imagify_attached_file( $results['filenames'][ $image_id ] ),
		);

		/** This filter is documented in inc/functions/process.php. */
		$files['full'] = apply_filters( 'imagify_file_path', $files['full'] );

		$sizes = isset( $results['data'][ $image_id ]['sizes'] ) ? $results['data'][ $image_id ]['sizes'] : array();

		if ( $sizes && is_array( $sizes ) ) {
			$full_dirname = trailingslashit( dirname( $files['full'] ) );

			if ( ! $is_active_for_network ) {
				$sizes = array_intersect_key( $sizes, $disallowed_sizes );
			}

			if ( $sizes ) {
				foreach ( $sizes as $size_key => $size_data ) {
					$files[ $size_key ] = $full_dirname . $size_data['file'];
				}
			}
		}

		/**
		 * Allow to provide all files size and the number of thumbnails.
		 *
		 * @since  1.6.7
		 * @author Grégory Viguier
		 *
		 * @param  bool  $size_and_count False by default.
		 * @param  int   $image_id       The attachment ID.
		 * @param  array $files          An array of file paths with thumbnail sizes as keys.
		 * @param  array $image_ids      An array of all attachment IDs.
		 * @return bool|array            False by default. Provide an array with the keys 'filesize' (containing the total filesize) and 'thumbnails' (containing the number of thumbnails).
		 */
		$size_and_count = apply_filters( 'imagify_total_attachment_filesize', false, $image_id, $files, $image_ids );

		if ( is_array( $size_and_count ) ) {
			$partial_size_images               += $size_and_count['filesize'];
			$partial_total_intermediate_images += $size_and_count['thumbnails'];
		} else {
			foreach ( $files as $file ) {
				if ( file_exists( $file ) ) {
					$partial_size_images += filesize( $file );
				}
			}

			unset( $files['full'] );
			$partial_total_intermediate_images += count( $files );
		}

		unset( $image_ids[ $i ], $results['filenames'][ $image_id ], $results['data'][ $image_id ], $results['imagify_data'][ $image_id ], $results['statuses'][ $image_id ] );
	} // End foreach().

	$intermediate_images_per_image = $partial_total_intermediate_images / $partial_total_images;
	$average_size_images           = $partial_size_images / ( $partial_total_images + $partial_total_intermediate_images );
	$total_size_images             = $average_size_images * ( $total_images + ( $intermediate_images_per_image * $total_images ) );

	return $total_size_images;
}
