<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_action( 'attachment_submitbox_misc_actions', '_imagify_attachment_submitbox_misc_actions', IMAGIFY_INT_MAX );
/**
 * Add a "Optimize It" button or the Imagify optimization data in the attachment submit area.
 *
 * @since 1.0
 */
function _imagify_attachment_submitbox_misc_actions() {
	global $post;

	if ( ! imagify_get_context( 'wp' )->current_user_can( 'manual-optimize', $post->ID ) ) {
		return;
	}

	$process = imagify_get_optimization_process( $post->ID, 'wp' );

	if ( ! $process->is_valid() ) {
		return;
	}

	$media = $process->get_media();

	if ( ! $media->is_supported() ) {
		return;
	}

	if ( ! $media->has_required_media_data() ) {
		return;
	}

	$data  = $process->get_data();
	$views = Imagify_Views::get_instance();

	if ( ! Imagify_Requirements::is_api_key_valid() && ! $data->is_optimized() ) {
		?>
		<div class="misc-pub-section misc-pub-imagify"><h4><?php esc_html_e( 'Imagify', 'imagify' ); ?></h4></div>
		<div class="misc-pub-section misc-pub-imagify">
			<?php esc_html_e( 'Invalid API key', 'imagify' ); ?>
			<br/>
			<a href="<?php echo esc_url( get_imagify_admin_url() ); ?>">
				<?php esc_html_e( 'Check your Settings', 'imagify' ); ?>
			</a>
		</div>
		<?php
	} else {
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
			?>
			<div class="misc-pub-section misc-pub-imagify">
				<?php $views->print_template( 'button/processing', [ 'label' => $lock_label ] ); ?>
			</div>
			<?php
		} elseif ( $data->is_optimized() || $data->is_already_optimized() || $data->is_error() ) {
			?>
			<div class="misc-pub-section misc-pub-imagify"><h4><?php esc_html_e( 'Imagify', 'imagify' ); ?></h4></div>
			<div class="misc-pub-section misc-pub-imagify imagify-data-item">
				<?php echo get_imagify_attachment_optimization_text( $process ); ?>
			</div>
			<?php
		} else {
			$url = get_imagify_admin_url( 'optimize', array( 'attachment_id' => $post->ID ) );
			?>
			<div class="misc-pub-section misc-pub-imagify">
				<a class="button-primary" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Optimize', 'imagify' ); ?></a>
			</div>
			<?php
		}
	}

	if ( $media->has_backup() && $data->is_optimized() ) {
		?>
		<input id="imagify-full-original" type="hidden" value="<?php echo esc_url( $media->get_backup_url() ); ?>">
		<input id="imagify-full-original-size" type="hidden" value="<?php echo esc_attr( $data->get_original_size( true, 0 ) ); ?>">
		<?php
	}
}
