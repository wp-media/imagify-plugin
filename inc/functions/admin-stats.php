<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

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

	$count = get_imagify_option( 'attachments_count' ) - get_imagify_option( 'attachments_optimized_count' ) - get_imagify_option( 'attachments_error_count' );
	return $count;
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

	$total_attachments           = get_imagify_option( 'attachments_count' );
	$total_optimized_attachments = get_imagify_option( 'attachments_optimized_count' );

	if ( ! $total_attachments || ! $total_optimized_attachments ) {
		return 0;
	}

	return min( round( 100 * $total_optimized_attachments / $total_attachments ), 100 );
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
		$saving_data = get_imagify_option( 'saving_data_count' );

		// Global chart.
		$data['total_attachments']             += get_imagify_option( 'attachments_count' );
		$data['unoptimized_attachments']       += get_imagify_option( 'attachments_unoptimized_count' );
		$data['optimized_attachments']         += get_imagify_option( 'attachments_optimized_count' );
		$data['errors_attachments']            += get_imagify_option( 'attachments_error_count' );
		// Stats block.
		$data['already_optimized_attachments'] += $saving_data['count'];
		$data['original_human']                += $saving_data['original_size'];
		$data['optimized_human']               += $saving_data['optimized_size'];
	}

	if ( isset( $types['custom-folders|custom-folders'] ) ) {
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
