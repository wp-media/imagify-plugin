<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/*
 * Add "Imagify" column in upload.php
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 */
add_filter( 'manage_media_columns', '_imagify_manage_media_columns' );
function _imagify_manage_media_columns( $columns ) {
	$columns['imagify_optimized_file'] = __( 'Imagify', 'imagify' );
	return $columns;
}

add_filter( 'manage_media_custom_column', '_imagify_manage_media_custom_column', 10, 2 );
function _imagify_manage_media_custom_column( $column_name, $attachment_id ) {
	if ( 'imagify_optimized_file' == $column_name ) {
		$attachment = new Imagify_Attachment( $attachment_id );
		echo get_imagify_media_column_content( $attachment );
	}
}

/*
 * Adds a dropdown that allows filtering on the attachments Imagify status.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 */
add_action( 'restrict_manage_posts', '_imagify_attachments_filter_dropdown' );
function _imagify_attachments_filter_dropdown() {
	if ( 'upload.php' !== $GLOBALS['pagenow'] ) {
		return;
	}

	$optimized   = imagify_count_optimized_attachments();
	$unoptimized = imagify_count_unoptimized_attachments();
	$errors      = imagify_count_error_attachments();
	$status 	 = ( isset( $_GET['imagify-status'] ) ) ? $_GET['imagify-status'] : 0;
	$options 	 = array(
		'optimized'   => __( 'Optimized','imagify' ),
		'unoptimized' => __( 'Unoptimized','imagify' ),
		'errors'      => __( 'Errors','imagify' ),
	);

	$output = '<label class="screen-reader-text" for="filter-by-optimization-status">' . __( 'Filter by status','imagify' ) . '</label>';

	$output .= '<select id="filter-by-optimization-status" name="imagify-status">';
		$output .= '<option value="0" selected="selected">' . __( 'All images','imagify' ) . '</option>';

		foreach( $options as $value => $label ) {
			$output .= '<option value="' . $value . '" ' . selected( $status, $value, false ) . '>' . $label . ' (' . ${$value} . ')</option>';
		}

	$output .= '</select>&nbsp;';

	echo $output;
}

/**
 * Modify the query based on the imagify-status variable in $_GET
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 */
add_filter( 'request', '_imagify_sort_attachments_by_status' );
function _imagify_sort_attachments_by_status( $vars ) {
	if ( 'upload.php' !== $GLOBALS['pagenow'] || empty( $_GET['imagify-status'] ) ) {
		return $vars;
	}
	
	$status       = $_GET['imagify-status'];
	$meta_key     = '_imagify_status';
	$meta_compare = '=';
	$relation     = array();
	
	switch( $status ) {
		case 'unoptimized':
			$meta_key     = '_imagify_data';
			$meta_compare = 'NOT EXISTS';
		break;
		
		case 'optimized':
			$status   = 'success';
			$relation = array(
				'key'     => $meta_key,
				'value'   => 'already_optimized',
				'compare' => $meta_compare,
			);
		break;
		
		case 'errors':
			$status = 'error';
		break;
	}
	
	$vars['post_mime_type'] = get_imagify_mime_type();
	$vars = array_merge(
		$vars,
		array(
			'meta_query' => array(
				'relation' => 'or',
				array(
					'key'     => $meta_key,
					'value'   => $status,
					'compare' => $meta_compare,
				),
				$relation
			),
		)
	);
		
	return $vars;
}