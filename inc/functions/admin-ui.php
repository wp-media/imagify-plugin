<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Get the optimization data list for a specific media.
 *
 * @since  1.0
 * @since  1.9 Function signature changed.
 * @author Jonathan Buttigieg
 *
 * @param  ProcessInterface $process The optimization process object.
 * @return string                    The output to print.
 */
function get_imagify_attachment_optimization_text( $process ) {
	if ( ! $process->is_valid() ) {
		return '';
	}

	$is_media_page            = Imagify_Views::get_instance()->is_media_page();
	$is_library_page          = Imagify_Views::get_instance()->is_wp_library_page();
	$output                   = $is_media_page ? '' : '<ul class="imagify-datas-list">';
	$output_before            = $is_media_page ? '' : '<li class="imagify-data-item">';
	$output_after             = $is_media_page ? '<br/>' : '</li>';
	$reoptimize_link          = get_imagify_attachment_reoptimize_link( $process );
	$reoptimize_link         .= get_imagify_attachment_optimize_missing_thumbnails_link( $process );
	$reoptimize_link         .= get_imagify_attachment_generate_webp_versions_link( $process );
	$reoptimize_link         .= get_imagify_attachment_delete_webp_versions_link( $process );
	$reoptimize_output        = $reoptimize_link ? $reoptimize_link : '';
	$reoptimize_output_before = '<div class="imagify-datas-actions-links">';
	$reoptimize_output_after  = '</div><!-- .imagify-datas-actions-links -->';
	$error                    = get_imagify_attachment_error_text( $process );
	$media                    = $process->get_media();

	if ( $error ) {
		if ( ! $is_media_page && $reoptimize_link && $media->has_backup() ) {
			$reoptimize_output .= '<span class="attachment-has-backup hidden"></span>';
		}

		$reoptimize_output = $reoptimize_output_before . $reoptimize_output . $reoptimize_output_after;

		return $is_media_page ? $output_before . $error . $reoptimize_output . $output_after : $error . $reoptimize_output;
	}

	$data               = $process->get_data();
	$attachment_id      = $media->get_id();
	$optimization_level = imagify_get_optimization_level_label( $data->get_optimization_level() );

	if ( ! $is_media_page ) {
		$output .= $output_before . '<span class="data">' . __( 'New Filesize:', 'imagify' ) . '</span> <strong class="big">' . $data->get_optimized_size() . '</strong>' . $output_after;
	}

	$chart = '';

	if ( ! $is_media_page ) {
		if ( ! $is_library_page ) {
			// No need to print this on the library page, the event whould be triggered before the handler is attached (the JS file is loaded in the footer).
			$chart = '<script type="text/javascript">jQuery( window ).trigger( "canvasprinted.imagify", [ ".imagify-consumption-chart-' . $attachment_id . '" ] ); </script>';
		}

		$chart = '<span class="imagify-chart">
					<span class="imagify-chart-container">
						<canvas class="imagify-consumption-chart imagify-consumption-chart-' . $attachment_id . '" width="15" height="15"></canvas>
						' . $chart . '
					</span>
				</span>';
	}

	$output .= $output_before;
	$output .= '<span class="data">' . __( 'Original Saving:', 'imagify' ) . '</span> ';
	$output .= '<strong>' . $chart . '<span class="imagify-chart-value">' . $data->get_saving_percent() . '</span>%</strong>';
	$output .= $output_after;

	// More details section.
	if ( ! $is_media_page ) {
		// New list.
		$output .= '</ul>';
		$output .= '<p class="imagify-datas-more-action">';
			$output .= '<a href="#imagify-view-details-' . $attachment_id . '" data-close="' . __( 'Close details', 'imagify' ) . '" data-open="' . __( 'View details', 'imagify' ) . '">';
				$output .= '<span class="the-text">' . __( 'View details', 'imagify' ) . '</span>';
				$output .= '<span class="dashicons dashicons-arrow-down-alt2"></span>';
			$output .= '</a>';
		$output .= '</p>';
		$output .= '<ul id="imagify-view-details-' . $attachment_id . '" class="imagify-datas-list imagify-datas-details">';

		// Not in metabox.
		$output .= $output_before . '<span class="data">' . __( 'Original Filesize:', 'imagify' ) . '</span> <strong class="original">' . $data->get_original_size() . '</strong>' . $output_after;
	}

	$output .= $output_before . '<span class="data">' . __( 'Level:', 'imagify' ) . '</span> <strong>' . $optimization_level . '</strong>' . $output_after;

	if ( $media->is_image() ) {
		$has_webp = $process->has_webp() ? __( 'Yes', 'imagify' ) : __( 'No', 'imagify' );
		$output  .= $output_before . '<span class="data">' . __( 'WebP generated:', 'imagify' ) . '</span> <strong class="big">' . esc_html( $has_webp ) . '</strong>' . $output_after;

		$total_optimized_thumbnails = $data->get_optimized_sizes_count();

		if ( $total_optimized_thumbnails ) {
			$output .= $output_before . '<span class="data">' . __( 'Thumbnails Optimized:', 'imagify' ) . '</span> <strong>' . $total_optimized_thumbnails . '</strong>' . $output_after;
			$output .= $output_before . '<span class="data">' . __( 'Overall Saving:', 'imagify' ) . '</span> <strong>' . $data->get_overall_saving_percent() . '%</strong>' . $output_after;
		}
	}

	// End of list.
	$output .= $is_media_page ? '' : '</ul>';

	// Actions section.
	$output .= $is_media_page ? $output_before : '';
	$output .= $reoptimize_output_before;
	$output .= $reoptimize_output;

	if ( $media->has_backup() ) {
		$url = get_imagify_admin_url( 'restore', [
			'attachment_id' => $attachment_id,
			'context'       => $media->get_context(),
		] );

		$output .= Imagify_Views::get_instance()->get_template( 'button/restore', [
			'url'  => $url,
			'atts' => [
				'class' => $is_media_page ? '' : null,
			],
		] );

		if ( ! $is_library_page ) {
			$output .= '<input id="imagify-original-src" type="hidden" value="' . esc_url( $media->get_backup_url() ) . '">';
			$output .= '<input id="imagify-original-size" type="hidden" value="' . $data->get_original_size() . '">';
			$output .= '<input id="imagify-full-src" type="hidden" value="' . esc_url( $media->get_fullsize_url() ) . '">';

			if ( $media->is_image() ) {
				$dimensions = $media->get_dimensions();

				$output .= '<input id="imagify-full-width" type="hidden" value="' . $dimensions['width'] . '">';
				$output .= '<input id="imagify-full-height" type="hidden" value="' . $dimensions['height'] . '">';
			}
		}
	}

	$output .= $reoptimize_output_after;

	return $output;
}

/**
 * Get the error message for a specific attachment.
 *
 * @since  1.0
 * @since  1.9 Function signature changed.
 * @author Jonathan Buttigieg
 *
 * @param  ProcessInterface $process The optimization process object.
 * @return string                    The output to print.
 */
function get_imagify_attachment_error_text( $process ) {
	if ( ! $process->is_valid() ) {
		return '';
	}

	$data = $process->get_data()->get_optimization_data();

	if ( ! isset( $data['sizes']['full']['success'] ) || $data['sizes']['full']['success'] ) {
		return '';
	}

	$class = 'button';
	$media = $process->get_media();
	$url   = get_imagify_admin_url( 'optimize', [
		'attachment_id' => $media->get_id(),
		'context'       => $media->get_context(),
	] );

	if ( ! Imagify_Views::get_instance()->is_media_page() ) {
		$class .= ' button-imagify-optimize';
	}

	return Imagify_Views::get_instance()->get_template( 'button/retry-optimize', [
		'url'   => $url,
		'error' => $data['sizes']['full']['error'],
		'atts'  => [
			'class' => $class,
		],
	] );
}

/**
 * Get the re-optimize link for a specific attachment.
 *
 * @since  1.0
 * @since  1.9 Function signature changed.
 * @author Jonathan Buttigieg
 *
 * @param  ProcessInterface $process The optimization process object.
 * @return string                    The output to print.
 */
function get_imagify_attachment_reoptimize_link( $process ) {
	if ( ! $process->is_valid() ) {
		return '';
	}

	$data = $process->get_data();

	if ( ! $data->get_optimization_status() ) {
		// Not optimized yet.
		return '';
	}

	// Stop the process if the API key isn't valid.
	if ( ! Imagify_Requirements::is_api_key_valid() ) {
		return '';
	}

	$is_already_optimized = $data->is_already_optimized();
	$media                = $process->get_media();
	$can_reoptimize       = $is_already_optimized || $media->has_backup();

	// Don't display anything if there is no backup or the image has been optimized.
	if ( ! $can_reoptimize ) {
		return '';
	}

	$output      = '';
	$views       = Imagify_Views::get_instance();
	$media_level = $data->get_optimization_level();
	$data        = [];
	$url_args    = [
		'attachment_id' => $media->get_id(),
		'context'       => $media->get_context(),
	];

	if ( Imagify_Views::get_instance()->is_media_page() ) {
		$data['atts'] = [
			'class' => '',
		];
	}

	foreach ( [ 2, 1, 0 ] as $level ) {
		/**
		 * Display a link if:
		 * - the level is lower than the one used to optimize the media,
		 * - or, the level is higher and the media is not already optimized.
		 */
		if ( $media_level < $level || ( $media_level > $level && ! $is_already_optimized ) ) {
			$url_args['optimization_level'] = $level;
			$data['optimization_level']     = $level;
			$data['url']                    = get_imagify_admin_url( 'manual-reoptimize', $url_args );

			$output .= $views->get_template( 'button/re-optimize', $data );
			$output .= '<br/>';
		}
	}

	return $output;
}

/**
 * Get the link to optimize missing thumbnail sizes for a specific attachment.
 *
 * @since  1.6.10
 * @since  1.9 Function signature changed.
 * @author Grégory Viguier
 *
 * @param  ProcessInterface $process The optimization process object.
 * @return string                    The output to print.
 */
function get_imagify_attachment_optimize_missing_thumbnails_link( $process ) {
	if ( ! $process->is_valid() ) {
		return '';
	}

	$media = $process->get_media();

	if ( ! $media->is_image() || ! Imagify_Requirements::is_api_key_valid() || ! $media->has_backup() ) {
		return '';
	}

	$context = $media->get_context();

	/**
	 * Allow to not display the "Optimize missing thumbnails" link.
	 *
	 * @since  1.6.10
	 * @since  1.9 The $attachment object is replaced by a $process object.
	 * @author Grégory Viguier
	 *
	 * @param bool             $display True to display the link. False to not display it.
	 * @param ProcessInterface $process The optimization process object.
	 * @param string           $context The context.
	 */
	$display = apply_filters( 'imagify_display_missing_thumbnails_link', true, $process, $context );

	// Stop the process if the filter is false.
	if ( ! $display ) {
		return '';
	}

	$missing_sizes = $process->get_missing_sizes();

	if ( ! $missing_sizes || is_wp_error( $missing_sizes ) ) {
		return '';
	}

	$url = get_imagify_admin_url( 'optimize-missing-sizes', [
		'attachment_id' => $media->get_id(),
		'context'       => $context,
	] );

	return Imagify_Views::get_instance()->get_template( 'button/optimize-missing-sizes', [
		'url'   => $url,
		'count' => count( $missing_sizes ),
	] );
}

/**
 * Get the link to generate WebP versions if they are missing.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  ProcessInterface $process The optimization process object.
 * @return string                    The output to print.
 */
function get_imagify_attachment_generate_webp_versions_link( $process ) {
	if ( ! $process->is_valid() ) {
		return '';
	}

	if ( ! get_imagify_option( 'convert_to_webp' ) ) {
		return '';
	}

	$media = $process->get_media();

	if ( ! $media->is_image() || ! Imagify_Requirements::is_api_key_valid() || ! $media->has_backup() ) {
		return '';
	}

	$data = $process->get_data();

	if ( ! $data->is_optimized() && ! $data->is_already_optimized() ) {
		return '';
	}

	if ( $process->has_webp() ) {
		return '';
	}

	$context = $media->get_context();

	/**
	 * Allow to not display the "Generate WebP versions" link.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 *
	 * @param bool             $display True to display the link. False to not display it.
	 * @param ProcessInterface $process The optimization process object.
	 * @param string           $context The context.
	 */
	$display = apply_filters( 'imagify_display_generate_webp_versions_link', true, $process, $context );

	// Stop the process if the filter is false.
	if ( ! $display ) {
		return '';
	}

	$url = get_imagify_admin_url( 'generate-webp-versions', [
		'attachment_id' => $media->get_id(),
		'context'       => $context,
	] );

	$output = Imagify_Views::get_instance()->get_template( 'button/generate-webp', [
		'url' => $url,
	] );

	return $output . '<br/>';
}

/**
 * Get the link to delete WebP versions when the status is "already_optimized".
 *
 * @since  1.9.6
 * @author Grégory Viguier
 *
 * @param  ProcessInterface $process The optimization process object.
 * @return string                    The output to print.
 */
function get_imagify_attachment_delete_webp_versions_link( $process ) {
	if ( ! $process->is_valid() ) {
		return '';
	}

	$media    = $process->get_media();
	$context  = $media->get_context();
	$media_id = $media->get_id();

	if ( ! imagify_get_context( $context )->current_user_can( 'manual-restore', $media_id ) ) {
		imagify_die();
	}

	$data = $process->get_data();

	if ( ! $data->is_already_optimized() || ! $process->has_webp() ) {
		return '';
	}

	$class = '';
	$url   = get_imagify_admin_url( 'delete-webp-versions', [
		'attachment_id' => $media_id,
		'context'       => $context,
	] );

	if ( ! Imagify_Views::get_instance()->is_media_page() ) {
		$class .= 'button-imagify-delete-webp';
	}

	return Imagify_Views::get_instance()->get_template( 'button/delete-webp', [
		'url'  => $url,
		'atts' => [
			'class' => $class,
		],
	] );
}

/**
 * Get all data to diplay for a specific media.
 *
 * @since  1.2
 * @since  1.9 Function signature changed.
 * @author Jonathan Buttigieg
 *
 * @param  ProcessInterface $process        The optimization process object.
 * @param  bool             $with_container Set to false to not return the HTML container.
 * @return string                           The output to print.
 */
function get_imagify_media_column_content( $process, $with_container = true ) {
	if ( ! $process->is_valid() ) {
		return __( 'This media is not valid.', 'imagify' );
	}

	if ( ! $process->current_user_can( 'manual-optimize' ) ) {
		return __( 'You are not allowed to optimize this file.', 'imagify' );
	}

	$media = $process->get_media();

	// Check if the media is supported.
	if ( ! $media->is_supported() ) {
		return __( 'This media is not supported.', 'imagify' );
	}

	// Check if the media has the required WP data.
	if ( ! $media->has_required_media_data() ) {
		return __( 'This media lacks the required metadata and cannot be optimized.', 'imagify' );
	}

	$data = $process->get_data();

	// Check if the API key is valid.
	if ( ! Imagify_Requirements::is_api_key_valid() && ! $data->is_optimized() ) {
		$output  = __( 'Invalid API key', 'imagify' );
		$output .= '<br/>';
		$output .= '<a href="' . esc_url( get_imagify_admin_url() ) . '">' . __( 'Check your Settings', 'imagify' ) . '</a>';
		return $output;
	}

	$media_id  = $media->get_id();
	$context   = $media->get_context();
	$views     = Imagify_Views::get_instance();
	$is_locked = $process->is_locked();

	if ( $is_locked ) {
		switch ( $is_locked ) {
			case 'optimizing':
				$lock_label = __( 'Optimizing...', 'imagify' );
				break;
			case 'restoring':
				$lock_label = __( 'Restoring...', 'imagify' );
				break;
			default:
				$lock_label = __( 'Processing...', 'imagify' );
		}

		if ( ! $with_container ) {
			return $views->get_template( 'button/processing', [ 'label' => $lock_label ] );
		}

		return $views->get_template( 'container/data-actions', [
			'media_id' => $media_id,
			'context'  => $context,
			'content'  => $views->get_template( 'button/processing', [ 'label' => $lock_label ] ),
		] );
	}

	// Check if the image was optimized.
	if ( ! $data->get_optimization_status() ) {
		$output = Imagify_Views::get_instance()->get_template( 'button/optimize', [
			'url' => get_imagify_admin_url( 'manual-optimize', [
				'attachment_id' => $media_id,
				'context'       => $context,
			] ),
		] );

		if ( $media->has_backup() ) {
			$output .= '<span class="attachment-has-backup hidden"></span>';
		}
	} else {
		$output = get_imagify_attachment_optimization_text( $process );
	}

	if ( ! $with_container ) {
		return $output;
	}

	return $views->get_template( 'container/data-actions', [
		'media_id' => $media_id,
		'context'  => $context,
		'content'  => $output,
	] );
}
