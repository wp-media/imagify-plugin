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
	 * Track a "Media Restored" event in Mixpanel.
	 *
	 * Fires on `imagify_after_restore_media`. Guards on opt-in and on a
	 * successful restore — no event is emitted when `$response` is a WP_Error.
	 *
	 * NOTE: `$files` is intentionally unused; it is kept in the signature to
	 * match the four-argument hook and allow WordPress to pass it cleanly.
	 *
	 * @param ProcessInterface $process  The optimization process instance.
	 * @param bool|\WP_Error   $response True on success, WP_Error on failure.
	 * @param array            $files    The list of files before restoring (unused).
	 * @param array            $data     The optimization data captured before deletion.
	 *
	 * @return void
	 */
	public function track_media_restored( ProcessInterface $process, $response, array $files, array $data ): void {
		if ( ! $this->can_track() ) {
			return;
		}

		if ( is_wp_error( $response ) ) {
			return;
		}

		$media         = $process->get_media();
		$media_context = $media ? $media->get_context() : '';

		$raw_level          = $data['level'] ?? null;
		$optimization_level = is_int( $raw_level ) ? $raw_level : null;

		$next_gen_format = $this->resolve_next_gen_format_from_data( $data );

		$event_data = array_merge(
			$this->get_default_event_properties(),
			[
				'media_context'      => $media_context,
				'optimization_level' => $optimization_level,
				'next_gen_format'    => $next_gen_format,
			]
		);

		$this->mixpanel->track_direct( 'Media Restored', $event_data );
	}

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
				'optimization_format'     => isset( $new_value['optimization_format'] ) ? (string) $new_value['optimization_format'] : null,
				'lossless'                => ! empty( $new_value['lossless'] ),
				'auto_optimize_on_upload' => ! empty( $new_value['auto_optimize'] ),
				'backup_original'         => ! empty( $new_value['backup'] ),
				'resize_larger_images'    => ! empty( $new_value['resize_larger'] ),
				'resize_larger_width'     => isset( $new_value['resize_larger_w'] ) ? (int) $new_value['resize_larger_w'] : null,
				'display_nextgen'         => ! empty( $new_value['display_nextgen'] ),
				'display_nextgen_method'  => isset( $new_value['display_nextgen_method'] ) ? (string) $new_value['display_nextgen_method'] : null,
				'cdn_enabled'             => ! empty( $new_value['cdn_url'] ),
			]
		);

		$this->mixpanel->track_direct( 'Settings Saved', $event_data );
	}

	/**
	 * Track an "Internal State Reset" event in Mixpanel.
	 *
	 * @return void
	 */
	public function track_internal_state_reset(): void {
		if ( ! $this->can_track() ) {
			return;
		}

		$event_data = array_merge(
			$this->get_default_event_properties(),
			[
				'is_multisite' => is_multisite(),
			]
		);

		$this->mixpanel->track_direct( 'Internal State Reset', $event_data );
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
	 * Resolve the next-gen format from the raw optimization data array.
	 *
	 * Used by `track_media_restored()` because `DataInterface::get_optimization_data()`
	 * is deleted before the `imagify_after_restore_media` hook fires; the raw
	 * array captured beforehand is passed as the `$data` hook argument instead.
	 *
	 * @param array $data The optimization data returned by DataInterface::get_optimization_data().
	 *
	 * @return string|null 'avif', 'webp', or null.
	 */
	private function resolve_next_gen_format_from_data( array $data ): ?string {
		$sizes = $data['sizes'] ?? [];

		$avif_size = 'full' . ProcessInterface::AVIF_SUFFIX;
		if ( ! empty( $sizes[ $avif_size ]['success'] ) ) {
			return 'avif';
		}

		$webp_size = 'full' . ProcessInterface::WEBP_SUFFIX;
		if ( ! empty( $sizes[ $webp_size ]['success'] ) ) {
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
