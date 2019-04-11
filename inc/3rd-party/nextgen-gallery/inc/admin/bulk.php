<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_filter( 'imagify_bulk_page_types', 'imagify_ngg_bulk_page_types' );
/**
 * Filter the types to display in the bulk optimization page.
 *
 * @since  1.7.1
 * @author Grégory Viguier
 *
 * @param  array $types The folder types displayed on the page. If a folder type is "library", the context should be suffixed after a pipe character. They are passed as array keys.
 * @return array
 */
function imagify_ngg_bulk_page_types( $types ) {
	if ( ! empty( $_GET['page'] ) && imagify_get_ngg_bulk_screen_slug() === $_GET['page'] ) { // WPCS: CSRF ok.
		$types['library|ngg'] = 1;
	}

	return $types;
}

add_filter( 'imagify_bulk_stats', 'imagify_ngg_bulk_stats', 10, 2 );
/**
 * Filter the generic stats used in the bulk optimization page.
 *
 * @since  1.7.1
 * @author Grégory Viguier
 *
 * @param  array $data  The data.
 * @param  array $types The folder types. They are passed as array keys.
 * @return array
 */
function imagify_ngg_bulk_stats( $data, $types ) {
	if ( ! isset( $types['library|ngg'] ) ) {
		return $data;
	}

	add_filter( 'imagify_count_saving_data', 'imagify_ngg_count_saving_data', 8 );
	$total_saving_data = imagify_count_saving_data();
	remove_filter( 'imagify_count_saving_data', 'imagify_ngg_count_saving_data', 8 );

	// Global chart.
	$data['total_attachments']             += imagify_ngg_count_attachments();
	$data['unoptimized_attachments']       += imagify_ngg_count_unoptimized_attachments();
	$data['optimized_attachments']         += imagify_ngg_count_optimized_attachments();
	$data['errors_attachments']            += imagify_ngg_count_error_attachments();
	// Stats block.
	$data['already_optimized_attachments'] += $total_saving_data['count'];
	$data['original_human']                += $total_saving_data['original_size'];
	$data['optimized_human']               += $total_saving_data['optimized_size'];

	return $data;
}

add_filter( 'imagify_bulk_page_data', 'imagify_ngg_bulk_page_data', 10, 2 );
/**
 * Filter the data to use on the bulk optimization page.
 *
 * @since  1.7
 * @since  1.7.1 Added the $types parameter.
 * @author Grégory Viguier
 *
 * @param  array $data  The data to use.
 * @param  array $types The folder types displayed on the page. They are passed as array keys.
 * @return array
 */
function imagify_ngg_bulk_page_data( $data, $types ) {
	if ( ! isset( $types['library|ngg'] ) ) {
		return $data;
	}

	// Limits.
	$data['unoptimized_attachment_limit'] += imagify_get_unoptimized_attachment_limit();
	// Group.
	$data['groups']['ngg'] = array(
		/**
		 * The group_id corresponds to the file names like 'part-bulk-optimization-results-row-{$group_id}'.
		 * It is also used in get_imagify_localize_script_translations().
		 */
		'group_id' => 'library',
		'context'  => 'ngg',
		'title'    => __( 'NextGen Galleries', 'imagify' ),
		/* translators: 1 is the opening of a link, 2 is the closing of this link. */
		'footer'   => sprintf( __( 'You can also re-optimize your images more finely directly in each %1$sgallery%2$s.', 'imagify' ), '<a href="' . esc_url( admin_url( 'admin.php?page=nggallery-manage-gallery' ) ) . '">', '</a>' ),
	);

	return $data;
}

add_filter( 'imagify_optimization_errors_url', 'imagify_ngg_optimization_errors_url', 10, 2 );
/**
 * Provide a URL to a page displaying optimization errors for the NGG context.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  string $url     The URL.
 * @param  string $context The context.
 * @return string
 */
function imagify_ngg_optimization_errors_url( $url, $context ) {
	if ( 'ngg' === $context ) {
		return admin_url( 'admin.php?page=nggallery-manage-gallery' );
	}

	return $url;
}
