<?php
declare(strict_types=1);

namespace Imagify\Abilities;

/**
 * Resolves a WordPress media identifier for MCP abilities.
 *
 * Accepts one of:
 *  - `media_id`       (int)  existing attachment ID, passthrough.
 *  - `media_url`      (string) absolute URL; resolved via WP core `attachment_url_to_postid()`.
 *  - `media_filename` (string) basename; matched against `_wp_attached_file` post meta.
 *
 * First match wins. When 2+ attachments share the same filename, returns
 * `WP_Error` so the caller can prompt the user to use the numeric ID instead.
 *
 * Custom-folder media is intentionally out of scope here: it has no
 * attachment ID and is resolved downstream by the abilities that support it
 * (e.g. `RestoreMedia` falls back to `imagify_get_optimization_process()`
 * with the `custom-folders` context after a successful media_id resolution).
 *
 * @since 2.3.1
 */
final class MediaResolver {

	/**
	 * Maximum number of attachment candidates fetched for filename lookups.
	 *
	 * Bounds the ambiguity check so a single bad request cannot drag a large
	 * result set back from the database.
	 *
	 * @since 2.3.1
	 */
	const MAX_FILENAME_CANDIDATES = 5;

	/**
	 * Optional factory used to build the WP_Query instance for filename lookups.
	 *
	 * Exposed for unit tests so the resolver can be exercised without
	 * bootstrapping a real WP_Query. Production code leaves it null and the
	 * resolver constructs `new WP_Query( $args )` directly.
	 *
	 * @since 2.3.1
	 *
	 * @var callable|null
	 */
	private static $query_factory;

	/**
	 * Set a custom query factory for filename lookups.
	 *
	 * Intended for unit tests. The factory receives the WP_Query args array
	 * and must return an object exposing a `posts` array property.
	 *
	 * @since 2.3.1
	 *
	 * @param callable|null $factory Factory closure or null to clear.
	 * @return void
	 */
	public static function set_query_factory( $factory ): void {
		self::$query_factory = $factory;
	}

	/**
	 * Resolve a single attachment ID from the ability input.
	 *
	 * @since 2.3.1
	 *
	 * @param array $args Raw input arguments. Reads keys: `media_id` (int),
	 *                    `media_url` (string), `media_filename` (string).
	 * @return int|\WP_Error Attachment ID on success; WP_Error on missing,
	 *                       ambiguous, or unresolved identifiers.
	 */
	public static function resolve_id( array $args ) {
		if ( ! empty( $args['media_id'] ) ) {
			$media_id = (int) $args['media_id'];

			if ( $media_id > 0 ) {
				return $media_id;
			}
		}

		if ( ! empty( $args['media_url'] ) ) {
			return self::resolve_by_url( (string) $args['media_url'] );
		}

		if ( ! empty( $args['media_filename'] ) ) {
			return self::resolve_by_filename( (string) $args['media_filename'] );
		}

		return new \WP_Error(
			'missing_identifier',
			__( 'Provide one of: media_id (integer), media_url (string), or media_filename (string).', 'imagify' )
		);
	}

	/**
	 * Resolve an attachment ID from an absolute URL.
	 *
	 * @since 2.3.1
	 *
	 * @param string $url Raw URL provided by the caller.
	 * @return int|\WP_Error
	 */
	private static function resolve_by_url( string $url ) {
		$clean_url = esc_url_raw( $url );

		if ( '' === $clean_url ) {
			return new \WP_Error(
				'invalid_media_url',
				__( 'The provided media_url is not a valid URL.', 'imagify' )
			);
		}

		$media_id = attachment_url_to_postid( $clean_url );

		if ( ! $media_id ) {
			return new \WP_Error(
				'media_not_found',
				__( 'No media attachment matches the provided media_url.', 'imagify' )
			);
		}

		return (int) $media_id;
	}

	/**
	 * Resolve an attachment ID from a filename (basename match).
	 *
	 * Compares against the basename of `_wp_attached_file` post meta so a
	 * caller can pass `hero.jpg` or `2024/01/hero.jpg` interchangeably.
	 *
	 * @since 2.3.1
	 *
	 * @param string $filename Raw filename provided by the caller.
	 * @return int|\WP_Error
	 */
	private static function resolve_by_filename( string $filename ) {
		$safe_filename = sanitize_file_name( $filename );

		if ( '' === $safe_filename ) {
			return new \WP_Error(
				'invalid_media_filename',
				__( 'The provided media_filename is not a valid filename.', 'imagify' )
			);
		}

		$base = basename( $safe_filename );

		if ( '' === $base ) {
			return new \WP_Error(
				'invalid_media_filename',
				__( 'The provided media_filename is not a valid filename.', 'imagify' )
			);
		}

		$query_args = [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'fields'         => 'ids',
			'posts_per_page' => self::MAX_FILENAME_CANDIDATES,
			'no_found_rows'  => true,
			'meta_query'     => [
				[
					'key'     => '_wp_attached_file',
					'value'   => $base,
					'compare' => 'LIKE',
				],
			],
		];

		$query = self::$query_factory
			? ( self::$query_factory )( $query_args )
			: new \WP_Query( $query_args );

		$candidates = isset( $query->posts ) && is_array( $query->posts ) ? $query->posts : [];

		if ( empty( $candidates ) ) {
			return new \WP_Error(
				'media_not_found',
				sprintf(
					/* translators: %s: the requested filename. */
					__( 'No media attachment matches the filename "%s".', 'imagify' ),
					$base
				)
			);
		}

		if ( count( $candidates ) > 1 ) {
			return new \WP_Error(
				'ambiguous_media_filename',
				sprintf(
					/* translators: %d: number of matching attachments. */
					_n(
						'Multiple attachments (%d) share this filename. Re-run with the numeric media_id instead.',
						'Multiple attachments (%d) share this filename. Re-run with the numeric media_id instead.',
						count( $candidates ),
						'imagify'
					),
					count( $candidates )
				)
			);
		}

		return (int) $candidates[0];
	}
}
