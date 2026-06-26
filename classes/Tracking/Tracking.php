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
