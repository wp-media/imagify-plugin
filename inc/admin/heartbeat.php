<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

global $pagenow;

add_filter( 'heartbeat_received', '_imagify_heartbeat_received', 10, 2 );
/**
 * Prepare the data that goes back with the Heartbeat API.
 *
 * @since 1.4.5
 *
 * @param  array $response The Heartbeat response.
 * @param  array $data     The $_POST data sent.
 * @return array
 */
function _imagify_heartbeat_received( $response, $data ) {
	if ( empty( $data['imagify_heartbeat'] ) || 'update_bulk_data' !== $data['imagify_heartbeat'] ) {
		return $response;
	}

	$user     = new Imagify_User();
	$types    = ! empty( $data['imagify_types'] ) && is_array( $data['imagify_types'] ) ? array_flip( $data['imagify_types'] ) : array();
	$new_data = array(
		// User account.
		'unconsumed_quota'              => is_wp_error( $user ) ? 0 : $user->get_percent_unconsumed_quota(),
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

	if ( isset( $types['library'] ) ) {
		/**
		 * Library.
		 */
		$saving_data = imagify_count_saving_data();

		// Global chart.
		$new_data['total_attachments']             += imagify_count_attachments();
		$new_data['unoptimized_attachments']       += imagify_count_unoptimized_attachments();
		$new_data['optimized_attachments']         += imagify_count_optimized_attachments();
		$new_data['errors_attachments']            += imagify_count_error_attachments();
		// Stats block.
		$new_data['already_optimized_attachments'] += $saving_data['count'];
		$new_data['original_human']                += $saving_data['original_size'];
		$new_data['optimized_human']               += $saving_data['optimized_size'];
	}

	if ( isset( $types['custom-folders'] ) ) {
		/**
		 * Custom folders.
		 */
		// Global chart.
		$new_data['total_attachments']             += Imagify_Files_Stats::count_all_files();
		$new_data['unoptimized_attachments']       += Imagify_Files_Stats::count_no_status_files();
		$new_data['optimized_attachments']         += Imagify_Files_Stats::count_optimized_files();
		$new_data['errors_attachments']            += Imagify_Files_Stats::count_error_files();
		// Stats block.
		$new_data['already_optimized_attachments'] += Imagify_Files_Stats::count_success_files();
		$new_data['original_human']                += Imagify_Files_Stats::get_original_size();
		$new_data['optimized_human']               += Imagify_Files_Stats::get_optimized_size();
	}

	/**
	 * Percentages.
	 */
	if ( $new_data['total_attachments'] && $new_data['optimized_attachments'] ) {
		$new_data['optimized_attachments_percent'] = round( 100 * $new_data['optimized_attachments'] / $new_data['total_attachments'] );
	} else {
		$new_data['optimized_attachments_percent'] = 0;
	}

	if ( $new_data['original_human'] && $new_data['optimized_human'] ) {
		$new_data['optimized_percent'] = ceil( 100 - ( 100 * $new_data['optimized_human'] / $new_data['original_human'] ) );
	} else {
		$new_data['optimized_percent'] = 0;
	}

	/**
	 * Formating.
	 */
	$new_data['already_optimized_attachments'] = number_format_i18n( $new_data['already_optimized_attachments'] );
	$new_data['original_human']                = imagify_size_format( $new_data['original_human'], 1 );
	$new_data['optimized_human']               = imagify_size_format( $new_data['optimized_human'], 1 );

	$response['imagify_bulk_data'] = $new_data;

	return $response;
}


if ( 'upload.php' === $pagenow && ! empty( $_GET['page'] ) && Imagify_Views::get_instance()->get_bulk_page_slug() === $_GET['page'] ) { // WPCS: CSRF ok.
	add_filter( 'heartbeat_settings', '_imagify_heartbeat_settings', IMAGIFY_INT_MAX );
}
/**
 * Update the Heartbeat API settings.
 *
 * @since 1.4.5
 *
 * @param  array $settings Heartbeat API settings.
 * @return array
 */
function _imagify_heartbeat_settings( $settings ) {
	$settings['interval'] = 30;
	return $settings;
}
