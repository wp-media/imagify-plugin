<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/*
 * Get the optimization data list for a specific attachement.
 *
 * @since 1.0
 *
 * @param 	int    $attachment_id  The attachment ID.
 * @return string  The output to print.
 */
function get_imagify_attachment_optimization_text( $attachment_id ) {
	global $pagenow;
	
	$attachment        = new Imagify_Attachment( $attachment_id );
	$data   	   	   = $attachment->get_data();
	$output 	   	   = ( 'post.php' != $pagenow ) ? '<ul class="imagify-datas-list">' : '';
	$output_before     = ( 'post.php' != $pagenow ) ? '<li class="imagify-data-item">' : '<div class="misc-pub-section misc-pub-imagify imagify-data-item">';
	$output_after  	   = ( 'post.php' != $pagenow ) ? '</li>' : '</div>';
	$reoptimize_output = '';

	if ( $error = get_imagify_attachment_error_text( $attachment_id ) ) {
		return $error;
	}

	$optimization_level = ( 1 == $data['stats']['aggressive'] ) ? __( 'Aggressive', 'imagify' ) : __( 'Normal', 'imagify' );


	if ( imagify_valid_key() && $attachment->has_backup() ) {
		$reoptimize_link   = get_imagify_attachment_reoptimize_link( $attachment_id );
		$reoptimize_output = ( $reoptimize_link ) ? $reoptimize_link : '';
	}

	if ( 'post.php' !== $pagenow ) {
		$output .= $output_before . '<span class="data">' . __( 'New Filesize:', 'imagify' ) . '</span> <strong class="big">' . size_format( $data['sizes']['full']['optimized_size'], 2 ) . '</strong>' . $output_after;
	}

	$chart = '<span class="imagify-chart">
			<span class="imagify-chart-container">
				<canvas id="imagify-consumption-chart" width="15" height="15"></canvas>
			</span>
		</span>';

	$output .= $output_before . '<span class="data">' . __( 'Original Saving:', 'imagify' ) . '</span> <strong>
		' . ( ( 'post.php' != $pagenow ) ? $chart : '' ) . '<span class="imagify-chart-value">' . $data['sizes']['full']['percent'] . '</span>%</strong>' . $output_after;

	// more details section
	if ( 'post.php' != $pagenow  ) {
		// new list
		$output .= '</ul>';
		$output .= '<p class="imagify-datas-more-action"><a href="#imagify-view-details-' . $attachment_id . '" data-close="' . __( 'Close details', 'imagify' ) . '" data-open="' . __( 'View details', 'imagify' ) . '"><span class="the-text">' . __( 'View details', 'imagify' ) . '</span><span class="dashicons dashicons-arrow-down-alt2"></span></a></p>';
		$output .= '<ul id="imagify-view-details-' . $attachment_id . '" class="imagify-datas-list imagify-datas-details">';

		// not in metabox
		$output .= $output_before . '<span class="data">' . __( 'Original Filesize:', 'imagify' ) . '</span> <strong class="original">' . $attachment->get_original_size() . '</strong>' . $output_after;
	}

	$output .= $output_before . '<span class="data">' . __( 'Level:', 'imagify' ) . '</span> <strong>' . $optimization_level . '</strong>' . $output_after;

	if ( $total_optimized_thumbnails = $attachment->get_optimized_sizes_count() ) {
		$output .= $output_before . '<span class="data">' . __( 'Thumbnails Optimized:', 'imagify' ) . '</span> <strong>' . $total_optimized_thumbnails . '</strong>' . $output_after;
		$output .= $output_before . '<span class="data">' . __( 'Overall Saving:', 'imagify' ) . '</span> <strong>' . $data['stats']['percent'] . '%</strong>' . $output_after;
	}

	// end of list
	$output .= ( 'post.php' != $pagenow ) ? '</ul>' : '';

	// actions section
	$output .= ( 'post.php' != $pagenow ) ? '' : $output_before;
	$output .= '<div class="imagify-datas-actions-links">';

	if ( $attachment->has_backup() ) {
		$class   = ( 'post.php' !== $pagenow  ) ? 'button-imagify-restore' : '';
		$output .= '<a id="imagify-restore-' . $attachment_id . '" href="' . get_imagify_admin_url( 'restore-upload', $attachment_id ) . '" class="' . $class . '" data-waiting-label="' . esc_attr__( 'Restoring...', 'imagify' ) . '"><span class="dashicons dashicons-image-rotate"></span>' . __( 'Restore Original', 'imagify' ) . '</a>';	
	}
	$output .= $reoptimize_output;
	$output .= '</div><!-- .imagify-datas-actions-links -->';
	$output .= ( 'post.php' != $pagenow ) ? '' : $output_after;

	return $output;
}

/*
 * Get the error message for a specific attachment.
 *
 * @since 1.0
 *
 * @param 	int    $attachment_id  The attachement ID.
 * @return string  The output to print.
 */
function get_imagify_attachment_error_text( $attachment_id ) {
	global $pagenow;
	$data   = get_post_meta( $attachment_id, '_imagify_data', true );
	$output = '';

	if ( isset( $data['sizes']['full']['success'] ) && ! $data['sizes']['full']['success'] ) {
		$class   = ( 'post.php' !== $pagenow  ) ? 'button-imagify-manual-upload' : '';
		$output .= '<strong>' . $data['sizes']['full']['error'] . '</strong><br/><a id="imagify-upload-' . $attachment_id . '" class="button ' . $class . '" href="' . get_imagify_admin_url( 'manual-upload', $attachment_id ) . '" data-waiting-label="' . esc_attr__( 'Optimizing...', 'imagify' ) . '">' . __( 'Try again', 'imagify' ) . '</a>';
	}

	return $output;
}

/*
 * Get the re-optimize link for a specific attachment.
 *
 * @since 1.0
 *
 * @param 	int    $attachment_id  The attachement ID.
 * @return string  The output to print.
 */
function get_imagify_attachment_reoptimize_link( $attachment_id ) {
	global $pagenow;
	
	$attachment = new Imagify_Attachment( $attachment_id );
	$level      = $attachment->get_optimization_level();
	$output     = '';

	if ( $attachment->has_backup() ) {
		$class  = ( 'post.php' !== $pagenow  ) ? 'button-imagify-manual-override-upload' : '';
		$level  = ( (bool) ! $level ) ? __( 'Aggressive', 'imagify' ) : __( 'Normal', 'imagify' );
		$output = ( get_imagify_option( 'backup' ) ) ? '<a href="' . get_imagify_admin_url( 'manual-override-upload', $attachment_id ) . '" class="' . $class . '" data-waiting-label="' . esc_attr__( 'Optimizing...', 'imagify' ) . '"><span class="dashicons dashicons-admin-generic"></span>' . sprintf( __( 'Re-Optimize to %s', 'imagify' ), $level ) . '</a>' : '';
	}

	return $output;
}