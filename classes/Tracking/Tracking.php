<?php
declare(strict_types=1);

namespace Imagify\Tracking;

use Imagify\Optimization\Process\ProcessInterface;

/**
 * Concrete tracking class for Imagify media optimization events.
 *
 * @since 2.3.0
 */
class Tracking extends BaseTracking {

	/**
	 * Track a "Media Optimized" event in Mixpanel.
	 *
	 * @param ProcessInterface $process The optimization process instance.
	 * @param array            $item    The optimization item data.
	 *
	 * @return void
	 */
	public function track_media_optimized( ProcessInterface $process, array $item ): void {
		if ( ! $this->can_track() ) {
			return;
		}

		if ( ! in_array( 'full', $item['sizes_done'] ?? [], true ) ) {
			return;
		}

		$data      = $process->get_data();
		$full_data = $data ? $data->get_size_data( 'full' ) : null;

		if ( empty( $full_data['success'] ) ) {
			return;
		}

		$original_size   = (int) ( $full_data['original_size'] ?? 0 );
		$optimized_size  = (int) ( $full_data['optimized_size'] ?? 0 );
		$savings_percent = $original_size > 0
			? round( ( ( $original_size - $optimized_size ) / $original_size ) * 100, 2 )
			: 0;

		$media      = $process->get_media();
		$media_type = $media ? $media->get_mime_type() : '';

		$optimization_level = $data ? $data->get_optimization_level() : null;

		$next_gen_format = $this->resolve_next_gen_format( $process );
		$trigger         = $this->resolve_trigger( $item );

		$event_data = array_merge(
			$this->get_default_event_properties(),
			[
				'optimization_level' => $optimization_level,
				'media_type'         => $media_type,
				'original_size'      => $original_size,
				'optimized_size'     => $optimized_size,
				'savings_percent'    => $savings_percent,
				'next_gen_format'    => $next_gen_format,
				'trigger'            => $trigger,
			]
		);

		$this->mixpanel->track_direct( 'Media Optimized', $event_data );
	}

	/**
	 * Track a "Settings Saved" event in Mixpanel.
	 *
	 * @param array $old_value The previous option value. Intentionally unused — kept for
	 *                         hook-signature symmetry. On multisite the first hook arg is the
	 *                         option name (string), not the old value, so this parameter is
	 *                         unreliable and must not be used for business logic.
	 * @param array $new_value The new option value.
	 *
	 * @return void
	 */
	public function track_settings_saved( array $old_value, array $new_value ): void {
		if ( ! $this->can_track() ) {
			return;
		}

		$event_data = array_merge(
			$this->get_default_event_properties(),
			[
				'optimization_level'      => isset( $new_value['optimization_level'] ) ? (int) $new_value['optimization_level'] : null,
				'auto_optimize_on_upload' => ! empty( $new_value['auto_optimize'] ),
				'resize_larger_images'    => ! empty( $new_value['resize_larger'] ),
				'next_gen_images_webp'    => ! empty( $new_value['convert_to_webp'] ),
				'next_gen_images_avif'    => ! empty( $new_value['convert_to_avif'] ),
				'backup_original'         => ! empty( $new_value['backup'] ),
			]
		);

		$this->mixpanel->track_direct( 'Settings Saved', $event_data );
	}

	/**
	 * Resolve the next-gen format generated for the full size.
	 *
	 * @param ProcessInterface $process The optimization process instance.
	 *
	 * @return string|null 'avif', 'webp', or null.
	 */
	protected function resolve_next_gen_format( ProcessInterface $process ): ?string {
		$data = $process->get_data();

		if ( ! $data ) {
			return null;
		}

		$avif_size = 'full' . ProcessInterface::AVIF_SUFFIX;
		$avif_data = $data->get_size_data( $avif_size );

		if ( ! empty( $avif_data['success'] ) ) {
			return 'avif';
		}

		$webp_size = 'full' . ProcessInterface::WEBP_SUFFIX;
		$webp_data = $data->get_size_data( $webp_size );

		if ( ! empty( $webp_data['success'] ) ) {
			return 'webp';
		}

		return null;
	}

	/**
	 * Resolve the trigger for the optimization event.
	 *
	 * @param array $item The optimization item data.
	 *
	 * @return string 'auto', 'bulk', or 'manual'.
	 */
	protected function resolve_trigger( array $item ): string {
		if ( ! empty( $item['data']['is_new_upload'] ) ) {
			return 'auto';
		}

		if ( get_transient( 'imagify_wp_optimize_running' ) || get_transient( 'imagify_custom-folders_optimize_running' ) ) {
			return 'bulk';
		}

		return 'manual';
	}
}
