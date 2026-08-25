<?php
declare(strict_types=1);

namespace Imagify\Media;

use Imagify\EventManagement\SubscriberInterface;
use Imagify\Media\Upload\Upload;
use Imagify\Optimization\NextGenMarker;
use WP_Query;

/**
 * Media Subscriber
 */
class Subscriber implements SubscriberInterface {

	/**
	 * Upload instance.
	 *
	 * @var Upload
	 */
	private $upload;
	/**
	 * Constructor
	 *
	 * @param Upload $upload Upload Instance.
	 */
	public function __construct( Upload $upload ) {
		$this->upload = $upload;
	}

	/**
	 * Returns an array of events that this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			// @action
			'restrict_manage_posts' => 'imagify_attachments_filter_dropdown',
			// @action
			'pre_get_posts'         => 'filter_missing_nextgen_query',
		];
	}

	/**
	 * Adds a dropdown that allows filtering on the attachments Imagify status.
	 *
	 * @return void
	 */
	public function imagify_attachments_filter_dropdown() {
		if ( ! \Imagify_Views::get_instance()->is_wp_library_page() ) {
			return;
		}
		$this->upload->add_imagify_filter_to_attachments_dropdown();
	}

	/**
	 * Narrows the Media Library list table (list mode only, `WP_Query`-based) to attachments that
	 * are optimized but have no successful next-gen size recorded, when
	 * `?imagify-status=missing-nextgen` is requested.
	 *
	 * The predicate mirrors `Imagify\Bulk\WP::get_optimized_media_ids_without_format()` on purpose:
	 * the list and the "missing next-gen" count it feeds must never disagree.
	 *
	 * @param WP_Query $query The current query, by reference.
	 *
	 * @return void
	 */
	public function filter_missing_nextgen_query( WP_Query $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! \Imagify_Views::get_instance()->is_wp_library_page() ) {
			return;
		}

		$status = isset( $_GET['imagify-status'] ) ? sanitize_text_field( wp_unslash( $_GET['imagify-status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'missing-nextgen' !== $status ) {
			return;
		}

		$formats = imagify_nextgen_images_formats();

		if ( empty( $formats ) ) {
			// No next-gen format is enabled: nothing can be "missing" a format that isn't
			// generated in the first place. Show no results rather than the whole library.
			$query->set( 'post__in', [ 0 ] );
			return;
		}

		$format         = $this->get_enabled_format( $formats );
		$nextgen_suffix = $this->get_nextgen_suffix( $format );

		if ( '' === $nextgen_suffix ) {
			// The enabled format is not one we know how to detect: showing the whole library
			// would be worse than showing nothing.
			$query->set( 'post__in', [ 0 ] );
			return;
		}

		$query->set(
			'meta_query', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			[
				'relation' => 'AND',
				[
					'key'     => '_imagify_status',
					'value'   => [ 'success', 'already_optimized' ],
					'compare' => 'IN',
				],
				[
					'key'     => '_imagify_data',
					'value'   => NextGenMarker::success( $nextgen_suffix ),
					'compare' => 'NOT LIKE',
				],
				[
					'key'     => '_imagify_data',
					'value'   => NextGenMarker::permanent_error( $nextgen_suffix ),
					'compare' => 'NOT LIKE',
				],
			]
		);

		if ( ! $query->get( 'post_mime_type' ) ) {
			$query->set( 'post_mime_type', $this->get_convertible_mime_types( $format ) );
		}
	}

	/**
	 * Reads the first enabled next-gen format.
	 *
	 * `imagify_nextgen_images_formats()` is filterable by third parties, so the array may come
	 * back as a list (`[ 'webp' ]`) rather than the keyed shape core builds (`[ 'webp' => 'webp' ]`).
	 * Reading the key would then yield the integer `0` — which is what made `strtoupper()` fatal
	 * on PHP 8 — so the value is read instead, and anything that is not a string is discarded.
	 *
	 * @param array $formats The enabled next-gen formats.
	 *
	 * @return string The format in lowercase, or an empty string if it is unusable.
	 */
	private function get_enabled_format( array $formats ): string {
		$format = current( $formats );

		return is_string( $format ) ? strtolower( $format ) : '';
	}

	/**
	 * Resolves the next-gen suffix (e.g. `@imagify-webp`) for the given format.
	 *
	 * An unrecognised format resolves to an empty string rather than raising an error, since the
	 * format ultimately comes from a public filter.
	 *
	 * @param string $format The next-gen format, lowercase.
	 *
	 * @return string The suffix, or an empty string if the format is not recognised.
	 */
	private function get_nextgen_suffix( string $format ): string {
		$process_class = imagify_get_optimization_process_class_name( 'wp' );
		$suffixes      = [
			'avif' => constant( $process_class . '::AVIF_SUFFIX' ),
			'webp' => constant( $process_class . '::WEBP_SUFFIX' ),
		];

		return isset( $suffixes[ $format ] ) ? $suffixes[ $format ] : '';
	}

	/**
	 * Lists the mime types that can hold a next-gen version of the given format.
	 *
	 * Mirrors `Imagify\Bulk\WP::get_optimized_media_ids_without_format()`: images only — a PDF has
	 * no next-gen version, so it can never be "missing" one — minus the target format's own mime
	 * type, since an image that already *is* WebP is not missing a WebP version.
	 *
	 * @param string $format The next-gen format being looked for, e.g. `webp`.
	 *
	 * @return array The mime types, as extension => mime type pairs.
	 */
	private function get_convertible_mime_types( string $format ): array {
		$mime_types = imagify_get_mime_types( 'image' );

		return array_filter(
			$mime_types,
			function ( $mime ) use ( $format ) {
				return 'image/' . strtolower( $format ) !== $mime;
			}
		);
	}
}
