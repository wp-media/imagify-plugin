<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_filter( 'imagify_bulk_page_data', 'imagify_ngg_bulk_page_data' );
/**
 * Filter the data to use on the bulk optimization page.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @param  array $data The data to use.
 * @return array
 */
function imagify_ngg_bulk_page_data( $data ) {
	if ( empty( $_GET['page'] ) || imagify_get_ngg_bulk_screen_slug() !== $_GET['page'] ) { // WPCS: CSRF ok.
		return $data;
	}

	add_filter( 'imagify_get_folder_type_data'          , 'imagify_ngg_get_folder_type_data', 10, 2 );
	add_filter( 'imagify_count_attachments'             , 'imagify_ngg_count_attachments' );
	add_filter( 'imagify_count_optimized_attachments'   , 'imagify_ngg_count_optimized_attachments' );
	add_filter( 'imagify_count_error_attachments'       , 'imagify_ngg_count_error_attachments' );
	add_filter( 'imagify_count_unoptimized_attachments' , 'imagify_ngg_count_unoptimized_attachments' );
	add_filter( 'imagify_percent_optimized_attachments' , 'imagify_ngg_percent_optimized_attachments' );
	add_filter( 'imagify_count_saving_data'             , 'imagify_ngg_count_saving_data', 8 );

	$total_saving_data = imagify_count_saving_data();

	return array(
		// Global chart.
		'optimized_attachments_percent' => imagify_ngg_percent_optimized_attachments(),
		// Stats block.
		'already_optimized_attachments' => $total_saving_data['count'],
		'original_human'                => $total_saving_data['original_size'],
		'optimized_human'               => $total_saving_data['optimized_size'],
		'optimized_percent'             => $total_saving_data['percent'],
		// Limits.
		'unoptimized_attachment_limit'  => imagify_get_unoptimized_attachment_limit(),
		'max_image_size'                => get_imagify_max_image_size(),
		// What to optimize.
		'groups'                        => array(
			'NGG' => array(
				/**
				 * The group_id corresponds to the file names like 'part-bulk-optimization-results-row-{$group_id}'.
				 * It is also used in the underscore template id: 'tmpl-imagify-results-row-{$group_id}' and in get_imagify_localize_script_translations().
				 */
				'group_id'   => 'library',
				'context'    => 'NGG',
				'icon'       => 'images-alt2',
				'title'      => __( 'Optimize the images of your galleries', 'imagify' ),
				'optimizing' => __( 'Optimizing the images of your galleries...', 'imagify' ),
				/* translators: 1 is the opening of a link, 2 is the closing of this link. */
				'footer'     => sprintf( __( 'You can re-optimize your images more finely directly in each %1$sgallery%2$s.', 'imagify' ), '<a href="' . esc_url( admin_url( 'admin.php?page=nggallery-manage-gallery' ) ) . '">', '</a>' ),
				'rows'       => array(
					/**
					 * The 'NGG' key corresponds to the "folder type".
					 * It is used in imagify_get_folder_type_data() for example.
					 */
					'NGG' => array(
						'title' => __( 'NextGen galleries', 'imagify' ),
					),
				),
			),
		),
	);
}

/**
 * Provide custom folder type data.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @param  array  $data        An array with keys corresponding to cell classes, and values formatted with HTML.
 * @param  string $folder_type A folder type.
 * @return array
 */
function imagify_ngg_get_folder_type_data( $data, $folder_type ) {
	if ( 'NGG' !== $folder_type ) {
		return $data;
	}

	// Already filtered in imagify_ngg_bulk_page_data().
	$total_saving_data = imagify_count_saving_data();

	return array(
		'images-optimized' => imagify_ngg_count_optimized_attachments(),
		'errors'           => imagify_ngg_count_error_attachments(),
		'optimized'        => $total_saving_data['optimized_size'],
		'original'         => $total_saving_data['original_size'],
		'errors_url'       => admin_url( 'admin.php?page=nggallery-manage-gallery' ),
	);
}
