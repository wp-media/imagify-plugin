<?php
declare(strict_types=1);

namespace Imagify\Abilities;

use WP_Error;

/**
 * Resolves the media identifier accepted by the MCP media abilities.
 *
 * Callers driving an AI assistant know an image by its filename or URL far more
 * often than by its attachment ID, so the media abilities accept any of:
 *
 * - `media_id`       (int)    attachment ID, used as-is.
 * - `media_url`      (string) absolute URL, resolved with `attachment_url_to_postid()`.
 * - `media_filename` (string) file name, matched against the `_wp_attached_file` meta.
 *
 * Precedence is `media_id`, then `media_url`, then `media_filename`. A filename
 * matching several attachments is reported as ambiguous, with the matching IDs,
 * rather than silently acting on the first one.
 *
 * @since 2.3.3
 */
final class MediaResolver {

	/**
	 * Maximum number of attachments fetched when matching a filename.
	 *
	 * Bounds the query while leaving enough room to report a useful list of
	 * candidates when a filename is ambiguous.
	 *
	 * @var int
	 */
	const MAX_CANDIDATES = 20;

	/**
	 * Returns the input-schema properties describing the filename and URL inputs.
	 *
	 * Shared by every media ability so the three schemas stay in sync.
	 *
	 * @since 2.3.3
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_input_schema_properties(): array {
		return [
			'media_filename' => [
				'type'        => 'string',
				'description' => __( 'The media file name, for example "hero-banner.jpg". Use instead of media_id when only the file name is known.', 'imagify' ),
			],
			'media_url'      => [
				'type'        => 'string',
				'description' => __( 'The absolute URL of the media. Use instead of media_id when only the URL is known.', 'imagify' ),
			],
		];
	}

	/**
	 * Resolve the ability arguments into a single attachment ID.
	 *
	 * @since 2.3.3
	 *
	 * @param array $args Ability input arguments.
	 * @return int|WP_Error Attachment ID, or WP_Error when the identifier is
	 *                      missing, unknown, or ambiguous.
	 */
	public static function resolve_id( array $args ) {
		if ( isset( $args['media_id'] ) && (int) $args['media_id'] > 0 ) {
			return (int) $args['media_id'];
		}

		if ( isset( $args['media_url'] ) && '' !== trim( (string) $args['media_url'] ) ) {
			return self::resolve_by_url( trim( (string) $args['media_url'] ) );
		}

		if ( isset( $args['media_filename'] ) && '' !== trim( (string) $args['media_filename'] ) ) {
			return self::resolve_by_filename( trim( (string) $args['media_filename'] ) );
		}

		return new WP_Error(
			'imagify_missing_media_identifier',
			__( 'Provide one of media_id, media_url, or media_filename to identify the media.', 'imagify' )
		);
	}

	/**
	 * Resolve an attachment ID from an absolute URL.
	 *
	 * @since 2.3.3
	 *
	 * @param string $url Absolute URL of the media.
	 * @return int|WP_Error
	 */
	private static function resolve_by_url( string $url ) {
		$media_id = attachment_url_to_postid( $url );

		if ( $media_id > 0 ) {
			return (int) $media_id;
		}

		// Next-gen and resized URLs have no attachment of their own, so retry on
		// the file name after peeling off their suffixes: `hero-300x200.jpg.webp`
		// must still resolve to the `hero.jpg` attachment.
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );

		if ( '' !== $path ) {
			foreach ( self::get_filename_variants( basename( $path ) ) as $variant ) {
				$by_filename = self::resolve_by_filename( $variant );

				if ( ! is_wp_error( $by_filename ) ) {
					return $by_filename;
				}
			}
		}

		return new WP_Error(
			'imagify_media_not_found',
			sprintf(
				/* translators: %s: media URL provided by the caller. */
				__( 'No media in the library matches the URL "%s".', 'imagify' ),
				$url
			)
		);
	}

	/**
	 * Build the list of file names a URL may stand for, most specific first.
	 *
	 * A URL taken from the front end often points at a derivative of the
	 * original file rather than the original itself: a next-gen version
	 * (`hero.jpg.webp`) or a generated thumbnail (`hero-300x200.jpg`), or both
	 * at once. Each suffix is peeled off in turn so the caller can look for the
	 * attachment the derivative was made from.
	 *
	 * @since 2.3.3
	 *
	 * @param string $basename File name taken from the URL.
	 * @return string[] Unique file names to try, in order.
	 */
	private static function get_filename_variants( string $basename ): array {
		$variants = [ $basename ];

		// Drop a trailing next-gen extension, leaving the original file name.
		$without_nextgen = (string) preg_replace( '/\.(webp|avif)$/i', '', $basename );

		if ( $without_nextgen !== $basename && '' !== $without_nextgen ) {
			$variants[] = $without_nextgen;
		}

		// Drop a trailing thumbnail dimension suffix, on both names collected above.
		foreach ( $variants as $variant ) {
			$without_size = (string) preg_replace( '/-\d+x\d+(\.[^.]+)$/', '$1', $variant );

			if ( $without_size !== $variant && '' !== $without_size ) {
				$variants[] = $without_size;
			}
		}

		return array_unique( $variants );
	}

	/**
	 * Resolve an attachment ID from a file name.
	 *
	 * The `_wp_attached_file` meta stores a path relative to the uploads
	 * directory (`2026/08/hero.jpg`). The match is anchored on the path
	 * separator so `hero.jpg` cannot be satisfied by `my-hero.jpg` or
	 * `hero.jpg.bak`, and callers may pass either the bare file name or a full
	 * relative path — supplying the directory narrows the search, which is how
	 * two same-named files in different month folders are told apart.
	 *
	 * One row beyond the cap is fetched so that "more candidates than we are
	 * willing to list" is reported as ambiguous rather than silently resolved
	 * to whichever match happened to fall inside the window.
	 *
	 * @since 2.3.3
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $filename File name, or uploads-relative path, of the media.
	 * @return int|WP_Error
	 */
	private static function resolve_by_filename( string $filename ) {
		global $wpdb;

		$relative = ltrim( str_replace( '\\', '/', $filename ), '/' );
		$basename = basename( $relative );

		if ( '' === $basename ) {
			return new WP_Error(
				'imagify_invalid_media_filename',
				__( 'The media_filename provided is not a valid file name.', 'imagify' )
			);
		}

		// A caller-supplied directory is kept, so `2026/08/hero.jpg` does not
		// collide with `2025/01/hero.jpg`.
		$needle = ( false !== strpos( $relative, '/' ) ) ? $relative : $basename;

		// Matching in SQL: either the stored path IS the needle (a file at the
		// uploads root, or a full relative path), or it ends with `/` + needle.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Meta LIKE lookup no core API offers; results are request-scoped.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta}
				WHERE meta_key = '_wp_attached_file'
					AND ( meta_value = %s OR meta_value LIKE %s )
				ORDER BY post_id ASC
				LIMIT %d",
				$needle,
				'%/' . $wpdb->esc_like( $needle ),
				self::MAX_CANDIDATES + 1
			)
		);

		// The comparison above follows the column collation, which is
		// case-insensitive on a stock install but need not be. Confirm in PHP so
		// the behaviour does not depend on how the database was created.
		$matches = [];

		foreach ( (array) $rows as $row ) {
			$stored  = (string) $row->meta_value;
			$subject = ( $needle === $basename ) ? basename( $stored ) : $stored;

			if ( 0 === strcasecmp( $subject, $needle ) ) {
				$matches[] = (int) $row->post_id;
			}
		}

		if ( ! $matches ) {
			return new WP_Error(
				'imagify_media_not_found',
				sprintf(
					/* translators: %s: media file name provided by the caller. */
					__( 'No media in the library is named "%s".', 'imagify' ),
					$basename
				)
			);
		}

		if ( count( $matches ) > self::MAX_CANDIDATES ) {
			return new WP_Error(
				'imagify_ambiguous_media_filename',
				sprintf(
					/* translators: 1: media file name provided by the caller, 2: number of attachments listed. */
					__( 'More than %2$d media are named "%1$s". Retry with media_id, or with the full path such as "2026/08/%1$s".', 'imagify' ),
					$basename,
					self::MAX_CANDIDATES
				)
			);
		}

		if ( count( $matches ) > 1 ) {
			return new WP_Error(
				'imagify_ambiguous_media_filename',
				sprintf(
					/* translators: 1: media file name provided by the caller, 2: comma-separated list of attachment IDs. */
					__( 'Several media are named "%1$s" (IDs: %2$s). Retry with media_id set to the one you want.', 'imagify' ),
					$basename,
					implode( ', ', $matches )
				)
			);
		}

		return $matches[0];
	}
}
