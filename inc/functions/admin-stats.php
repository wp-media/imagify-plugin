<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Count number of attachments.
 *
 * @since  1.0
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

	$mime_types   = Imagify_DB::get_mime_types();
	$statuses     = Imagify_DB::get_post_statuses();
	$nodata_join  = Imagify_DB::get_required_wp_metadata_join_clause();
	$nodata_where = Imagify_DB::get_required_wp_metadata_where_clause();
	$count        = (int) $wpdb->get_var( // WPCS: unprepared SQL ok.
		"
		SELECT COUNT( p.ID )
		FROM $wpdb->posts AS p
			$nodata_join
		WHERE p.post_mime_type IN ( $mime_types )
			AND p.post_type = 'attachment'
			AND p.post_status IN ( $statuses )
			$nodata_where"
	);

	if ( $count > imagify_get_unoptimized_attachment_limit() ) {
		set_transient( 'imagify_large_library', 1 );
	} elseif ( get_transient( 'imagify_large_library' ) ) {
		// In case the number is decreasing under our limit.
		delete_transient( 'imagify_large_library' );
	}

	return $count;
}

/**
 * Count number of optimized attachments with an error.
 *
 * @since  1.0
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

	$mime_types   = Imagify_DB::get_mime_types();
	$statuses     = Imagify_DB::get_post_statuses();
	$nodata_join  = Imagify_DB::get_required_wp_metadata_join_clause();
	$nodata_where = Imagify_DB::get_required_wp_metadata_where_clause();
	$count        = (int) $wpdb->get_var( // WPCS: unprepared SQL ok.
		"
		SELECT COUNT( DISTINCT p.ID )
		FROM $wpdb->posts AS p
			$nodata_join
		INNER JOIN $wpdb->postmeta AS mt1
			ON ( p.ID = mt1.post_id AND mt1.meta_key = '_imagify_status' )
		WHERE p.post_mime_type IN ( $mime_types )
			AND p.post_type = 'attachment'
			AND p.post_status IN ( $statuses )
			AND mt1.meta_value = 'error'
			$nodata_where"
	);

	return $count;
}

/**
 * Count number of optimized attachments (by Imagify or an other tool before).
 *
 * @since  1.0
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

	$mime_types   = Imagify_DB::get_mime_types();
	$statuses     = Imagify_DB::get_post_statuses();
	$nodata_join  = Imagify_DB::get_required_wp_metadata_join_clause();
	$nodata_where = Imagify_DB::get_required_wp_metadata_where_clause();
	$count        = (int) $wpdb->get_var( // WPCS: unprepared SQL ok.
		"
		SELECT COUNT( DISTINCT p.ID )
		FROM $wpdb->posts AS p
			$nodata_join
		INNER JOIN $wpdb->postmeta AS mt1
			ON ( p.ID = mt1.post_id AND mt1.meta_key = '_imagify_status' )
		WHERE p.post_mime_type IN ( $mime_types )
			AND p.post_type = 'attachment'
			AND p.post_status IN ( $statuses )
			AND mt1.meta_value IN ( 'success', 'already_optimized' )
			$nodata_where"
	);

	return $count;
}

/**
 * Count number of unoptimized attachments.
 *
 * @since  1.0
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
 * @since  1.0
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

	if ( ! $total_attachments || ! $total_optimized_attachments ) {
		return 0;
	}

	return min( round( 100 * $total_optimized_attachments / $total_attachments ), 100 );
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
				if ( $attachment_data['sizes'] ) {
					foreach ( $attachment_data['sizes'] as $size_data ) {
						if ( ! empty( $size_data['success'] ) ) {
							$original_size  += $size_data['original_size']  ? $size_data['original_size']  : 0;
							$optimized_size += $size_data['optimized_size'] ? $size_data['optimized_size'] : 0;
						}
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

		$mime_types     = Imagify_DB::get_mime_types();
		$statuses       = Imagify_DB::get_post_statuses();
		$nodata_join    = Imagify_DB::get_required_wp_metadata_join_clause();
		$nodata_where   = Imagify_DB::get_required_wp_metadata_where_clause();
		$attachment_ids = $wpdb->get_col( // WPCS: unprepared SQL ok.
			"
			SELECT p.ID
			FROM $wpdb->posts AS p
				$nodata_join
			INNER JOIN $wpdb->postmeta AS mt1
				ON ( p.ID = mt1.post_id AND mt1.meta_key = '_imagify_status' )
			WHERE p.post_mime_type IN ( $mime_types )
				AND p.post_type = 'attachment'
				AND p.post_status IN ( $statuses )
				AND mt1.meta_value = 'success'
				$nodata_where
			ORDER BY CAST( p.ID AS UNSIGNED )"
		);
		$wpdb->flush();

		$attachment_ids = array_map( 'absint', array_unique( $attachment_ids ) );
		$attachment_ids = array_chunk( $attachment_ids, $limit );

		while ( $attachment_ids ) {
			$limit_ids = array_shift( $attachment_ids );
			$limit_ids = implode( ',', $limit_ids );

			$attachments = $wpdb->get_col( // WPCS: unprepared SQL ok.
				"
				SELECT meta_value
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

				$original_data = $attachment_data['sizes']['full'];

				if ( empty( $original_data['success'] ) ) {
					/**
					 * Case where this attachment has multiple '_imagify_status' metas, and is fetched (in the above query) as a "success" while the '_imagify_data' says otherwise.
					 * Don't ask how it's possible, I don't know.
					 */
					continue;
				}

				++$count;

				// Increment the original sizes.
				$original_size  += ! empty( $original_data['original_size'] )  ? $original_data['original_size']  : 0;
				$optimized_size += ! empty( $original_data['optimized_size'] ) ? $original_data['optimized_size'] : 0;

				unset( $attachment_data['sizes']['full'], $original_data );

				// Increment the thumbnails sizes.
				if ( $attachment_data['sizes'] ) {
					foreach ( $attachment_data['sizes'] as $size_data ) {
						if ( ! empty( $size_data['success'] ) ) {
							$original_size  += ! empty( $size_data['original_size'] )  ? $size_data['original_size']  : 0;
							$optimized_size += ! empty( $size_data['optimized_size'] ) ? $size_data['optimized_size'] : 0;
						}
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

	$mime_types   = Imagify_DB::get_mime_types();
	$statuses     = Imagify_DB::get_post_statuses();
	$nodata_join  = Imagify_DB::get_required_wp_metadata_join_clause();
	$nodata_where = Imagify_DB::get_required_wp_metadata_where_clause();
	$image_ids    = $wpdb->get_col( // WPCS: unprepared SQL ok.
		"
		SELECT p.ID
		FROM $wpdb->posts AS p
			$nodata_join
		WHERE p.post_mime_type IN ( $mime_types )
			AND p.post_type = 'attachment'
			AND p.post_status IN ( $statuses )
			$nodata_where
		LIMIT 250
	" );

	if ( ! $image_ids ) {
		return 0;
	}

	$count_latest_images = count( $image_ids );
	$count_total_images  = imagify_count_attachments();

	return imagify_calculate_total_image_size( $image_ids, $count_latest_images, $count_total_images );
}

/**
 * Returns the estimated average size of the images uploaded per month.
 *
 * We estimate the average size of the images uploaded in the library per month by getting the latest 250 images and their thumbnails
 * for the 3 latest months, add up their filesizes, and doing some maths to get the total average size.
 *
 * @since  1.6
 * @since  1.7 Use wpdb instead of WP_Query.
 * @author Remy Perona
 *
 * @return int The current estimated average size of images uploaded per month.
 */
function imagify_calculate_average_size_images_per_month() {
	global $wpdb;

	$mime_types   = Imagify_DB::get_mime_types();
	$statuses     = Imagify_DB::get_post_statuses();
	$nodata_join  = Imagify_DB::get_required_wp_metadata_join_clause( "$wpdb->posts.ID" );
	$nodata_where = Imagify_DB::get_required_wp_metadata_where_clause();
	$limit        = ' LIMIT 0, 250';
	$query        = "
		SELECT $wpdb->posts.ID
		FROM $wpdb->posts
			$nodata_join
		WHERE $wpdb->posts.post_mime_type IN ( $mime_types )
			AND $wpdb->posts.post_type = 'attachment'
			AND $wpdb->posts.post_status IN ( $statuses )
			$nodata_where
			%date_query%";

	// Queries per month.
	$date_query = new WP_Date_Query( array(
		array(
			'before' => 'now',
			'after'  => '1 month ago',
		),
	) );

	$partial_images_uploaded_last_month = $wpdb->get_col( str_replace( '%date_query%', $date_query->get_sql(), $query . $limit ) ); // WPCS: unprepared SQL ok.

	$date_query = new WP_Date_Query( array(
		array(
			'before' => '1 month ago',
			'after'  => '2 months ago',
		),
	) );

	$partial_images_uploaded_two_months_ago = $wpdb->get_col( str_replace( '%date_query%', $date_query->get_sql(), $query . $limit ) ); // WPCS: unprepared SQL ok.

	$date_query = new WP_Date_Query( array(
		array(
			'before' => '2 month ago',
			'after'  => '3 months ago',
		),
	) );

	$partial_images_uploaded_three_months_ago = $wpdb->get_col( str_replace( '%date_query%', $date_query->get_sql(), $query . $limit ) ); // WPCS: unprepared SQL ok.

	// Total for the 3 months.
	$partial_images_uploaded_id = array_merge( $partial_images_uploaded_last_month, $partial_images_uploaded_two_months_ago, $partial_images_uploaded_three_months_ago );

	if ( ! $partial_images_uploaded_id ) {
		return 0;
	}

	// Total for the 3 months, without the "250" limit.
	$date_query = new WP_Date_Query( array(
		array(
			'before' => 'now',
			'after'  => '3 month ago',
		),
	) );

	$images_uploaded_id = $wpdb->get_col( str_replace( '%date_query%', $date_query->get_sql(), $query ) ); // WPCS: unprepared SQL ok.

	if ( ! $images_uploaded_id ) {
		return 0;
	}

	// Number of image attachments uploaded for the 3 latest months, limited to 250 per month.
	$partial_total_images_uploaded = count( $partial_images_uploaded_id );
	// Total number of image attachments uploaded for the 3 latest months.
	$total_images_uploaded         = count( $images_uploaded_id );

	return imagify_calculate_total_image_size( $partial_images_uploaded_id, $partial_total_images_uploaded, $total_images_uploaded ) / 3;
}

/**
 * Returns the estimated total size of images.
 *
 * @since  1.6
 * @author Remy Perona
 *
 * @param  array $image_ids            Array of image IDs.
 * @param  int   $partial_total_images The number of image attachments we're doing the calculation with.
 * @param  int   $total_images         The total number of image attachments.
 * @return int                         The estimated total size of images.
 */
function imagify_calculate_total_image_size( $image_ids, $partial_total_images, $total_images ) {
	global $wpdb;

	$image_ids = array_filter( array_map( 'absint', $image_ids ) );

	if ( ! $image_ids ) {
		return 0;
	}

	$results = Imagify_DB::get_metas( array(
		// Get attachments filename.
		'filenames'    => '_wp_attached_file',
		// Get attachments data.
		'data'         => '_wp_attachment_metadata',
		// Get Imagify data.
		'imagify_data' => '_imagify_data',
		// Get attachments status.
		'statuses'     => '_imagify_status',
	), $image_ids );

	// Number of image attachments we're doing the calculation with. In case array_filter() removed results.
	$partial_total_images              = count( $image_ids );
	// Total size of unoptimized size.
	$partial_size_images               = 0;
	// Total number of thumbnails.
	$partial_total_intermediate_images = 0;

	$filesystem            = imagify_get_filesystem();
	$is_active_for_network = imagify_is_active_for_network();
	$disallowed_sizes      = get_imagify_option( 'disallowed-sizes' );

	foreach ( $image_ids as $i => $image_id ) {
		$attachment_status = isset( $results['statuses'][ $image_id ] ) ? $results['statuses'][ $image_id ] : false;

		if ( 'success' === $attachment_status ) {
			/**
			 * The image files have been optimized.
			 */
			// Original size.
			$partial_size_images               += isset( $results['imagify_data'][ $image_id ]['stats']['original_size'] ) ? $results['imagify_data'][ $image_id ]['stats']['original_size'] : 0;
			// Number of thumbnails.
			$partial_total_intermediate_images += count( $results['imagify_data'][ $image_id ]['sizes'] );
			unset(
				$image_ids[ $i ],
				$results['filenames'][ $image_id ],
				$results['data'][ $image_id ],
				$results['imagify_data'][ $image_id ],
				$results['statuses'][ $image_id ]
			);
			continue;
		}

		/**
		 * The image files are not optimized.
		 */
		// Create an array containing all this attachment files.
		$files = array(
			'full' => get_imagify_attached_file( $results['filenames'][ $image_id ] ),
		);

		/** This filter is documented in inc/functions/process.php. */
		$files['full'] = apply_filters( 'imagify_file_path', $files['full'] );

		$sizes = isset( $results['data'][ $image_id ]['sizes'] ) ? $results['data'][ $image_id ]['sizes'] : array();

		if ( $sizes && is_array( $sizes ) ) {
			if ( ! $is_active_for_network ) {
				$sizes = array_diff_key( $sizes, $disallowed_sizes );
			}

			if ( $sizes ) {
				$full_dirname = $filesystem->dir_path( $files['full'] );

				foreach ( $sizes as $size_key => $size_data ) {
					$files[ $size_key ] = $full_dirname . '/' . $size_data['file'];
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
				if ( $filesystem->exists( $file ) ) {
					$partial_size_images += $filesystem->size( $file );
				}
			}

			unset( $files['full'] );
			$partial_total_intermediate_images += count( $files );
		}

		unset(
			$image_ids[ $i ],
			$results['filenames'][ $image_id ],
			$results['data'][ $image_id ],
			$results['imagify_data'][ $image_id ],
			$results['statuses'][ $image_id ]
		);
	} // End foreach().

	// Number of thumbnails per attachment = Number of thumbnails / Number of attachments.
	$intermediate_images_per_image = $partial_total_intermediate_images / $partial_total_images;
	/**
	 * Note: Number of attachments ($partial_total_images) === Number of full sizes.
	 * Average image size = Size of the images / ( Number of full sizes + Number of thumbnails ).
	 * Average image size = Size of the images / Number of images.
	 */
	$average_size_images           = $partial_size_images / ( $partial_total_images + $partial_total_intermediate_images );
	/**
	 * Note: Total number of attachments ($total_images) === Total number of full sizes.
	 * Total images size = Average image size * ( Total number of full sizes + ( Number of thumbnails per attachment * Total number of attachments ) ).
	 * Total images size = Average image size * ( Total number of full sizes + Total number of thumbnails ).
	 */
	$total_size_images             = $average_size_images * ( $total_images + ( $intermediate_images_per_image * $total_images ) );

	return $total_size_images;
}

/**
 * Get all generic stats to be used in the bulk optimization page.
 *
 * @since  1.7.1
 * @author Grégory Viguier
 *
 * @param  array $types The folder types. If a folder type is "library", the context should be suffixed after a pipe character. They are passed as array keys.
 * @param  array $args  {
 *     Optional. An array of arguments.
 *
 *     @type bool $fullset True to return the full set of data. False to return only the main data.
 *     @type bool $formatting Some of the data is returned formatted.
 * }
 * @return array
 */
function imagify_get_bulk_stats( $types, $args = array() ) {
	$types = $types && is_array( $types ) ? $types : array();
	$args  = array_merge( array(
		'fullset'    => false,
		'formatting' => true,
	), (array) $args );

	$data = array(
		// Global chart.
		'total_attachments'             => 0,
		'unoptimized_attachments'       => 0,
		'optimized_attachments'         => 0,
		'errors_attachments'            => 0,
		// Stats block.
		'already_optimized_attachments' => 0,
		'original_human'                => 0,
		'optimized_human'               => 0,
	);

	if ( isset( $types['library|wp'] ) ) {
		/**
		 * Library.
		 */
		$saving_data = imagify_count_saving_data();

		// Global chart.
		$data['total_attachments']             += imagify_count_attachments();
		$data['unoptimized_attachments']       += imagify_count_unoptimized_attachments();
		$data['optimized_attachments']         += imagify_count_optimized_attachments();
		$data['errors_attachments']            += imagify_count_error_attachments();
		// Stats block.
		$data['already_optimized_attachments'] += $saving_data['count'];
		$data['original_human']                += $saving_data['original_size'];
		$data['optimized_human']               += $saving_data['optimized_size'];
	}

	if ( isset( $types['custom-folders'] ) ) {
		/**
		 * Custom folders.
		 */
		// Global chart.
		$data['total_attachments']             += Imagify_Files_Stats::count_all_files();
		$data['unoptimized_attachments']       += Imagify_Files_Stats::count_no_status_files();
		$data['optimized_attachments']         += Imagify_Files_Stats::count_optimized_files();
		$data['errors_attachments']            += Imagify_Files_Stats::count_error_files();
		// Stats block.
		$data['already_optimized_attachments'] += Imagify_Files_Stats::count_success_files();
		$data['original_human']                += Imagify_Files_Stats::get_original_size();
		$data['optimized_human']               += Imagify_Files_Stats::get_optimized_size();
	}

	/**
	 * Full set of data.
	 */
	if ( $args['fullset'] ) {
		// User account.
		$views = Imagify_Views::get_instance();

		$data['unconsumed_quota'] = $views->get_quota_percent();
		$data['quota_class']      = $views->get_quota_class();
		$data['quota_icon']       = $views->get_quota_icon();
	}

	/**
	 * Filter the generic stats used in the bulk optimization page.
	 *
	 * @since  1.7.1
	 * @author Grégory Viguier
	 *
	 * @param array $data  The data.
	 * @param array $types The folder types. They are passed as array keys.
	 * @param array $args  {
	 *     Optional. An array of arguments.
	 *
	 *     @type bool $fullset True to return the full set of data. False to return only the main data.
	 *     @type bool $formatting Some of the data is returned formatted.
	 * }
	 */
	$data = apply_filters( 'imagify_bulk_stats', $data, $types, $args );

	/**
	 * Percentages.
	 */
	if ( $data['total_attachments'] && $data['optimized_attachments'] ) {
		$data['optimized_attachments_percent'] = round( 100 * $data['optimized_attachments'] / $data['total_attachments'] );
	} else {
		$data['optimized_attachments_percent'] = 0;
	}

	if ( $data['original_human'] && $data['optimized_human'] ) {
		$data['optimized_percent'] = ceil( 100 - ( 100 * $data['optimized_human'] / $data['original_human'] ) );
	} else {
		$data['optimized_percent'] = 0;
	}

	/**
	 * Formating.
	 */
	if ( $args['formatting'] ) {
		$data['already_optimized_attachments'] = number_format_i18n( $data['already_optimized_attachments'] );
		$data['original_human']                = imagify_size_format( $data['original_human'], 1 );
		$data['optimized_human']               = imagify_size_format( $data['optimized_human'], 1 );
	}

	return $data;
}
