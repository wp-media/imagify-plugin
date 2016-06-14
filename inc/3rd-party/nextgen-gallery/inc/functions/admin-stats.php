<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/*
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
	
	if ( ! $count ) {
		$table_name = $wpdb->prefix . "ngg_pictures"; 
		$count 		= $wpdb->get_var( "SELECT COUNT($table_name.pid) FROM $table_name" );	
	}
	
	return (int) $count;
}

/*
 * Count number of optimized attachments with an error.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 *
 * @return int The number of attachments.
 */
function imagify_ngg_count_error_attachments() {
	static $count;
	
	if ( ! $count ) {
		$count = (int) Imagify_NGG_DB()->get_column_by( 'COUNT(*)', 'status', 'error' );
	}
	
	return $count;
}

/*
 * Count number of optimized attachments (by Imagify or an other tool before).
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 *
 * @return int The number of attachments.
 */
function imagify_ngg_count_optimized_attachments() {
	static $count;
	
	if ( ! $count ) {
		$count = (int) Imagify_NGG_DB()->get_column_by( 'COUNT(*)', 'status', 'success' );
	}
	
	return $count;
}

/*
 * Count number of unoptimized attachments.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @return int The number of attachments.
 */
function imagify_ngg_count_unoptimized_attachments() {
	static $count;
	
	if ( ! $count ) {
		$count = imagify_ngg_count_attachments() - imagify_ngg_count_optimized_attachments() - imagify_ngg_count_error_attachments();	
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
function imagify_ngg_percent_optimized_attachments() {
	$total_attachments			   = imagify_ngg_count_attachments();
	$total_optimized_attachments   = imagify_ngg_count_optimized_attachments();

	$percent = ( 0 !== $total_attachments ) ? round( ( 100 - ( ( $total_attachments - ( $total_optimized_attachments ) ) / $total_attachments ) * 100 ) ) : 0;
	
	return $percent;
}

/*
 * Count percent, original & optimized size of all images optimized by Imagify.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 *
 * @return array An array containing the optimization data.
 */
function imagify_ngg_count_saving_data() {
	global $wpdb;
	
	$table_name  = $wpdb->ngg_imagify_data;
	$attachments = $wpdb->get_col( "SELECT $table_name.data FROM $table_name WHERE status = 'success'" );
	
	return $attachments;
}