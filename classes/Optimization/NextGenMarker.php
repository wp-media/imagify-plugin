<?php
declare(strict_types=1);

namespace Imagify\Optimization;

/**
 * Builds the fragile serialized-data needles used to detect, via a `LIKE`/`NOT LIKE` on the
 * `_imagify_data` postmeta, whether a media has a successful next-gen size or was permanently
 * refused by the API.
 *
 * These strings depend on the exact key order Imagify writes when serializing a size's
 * optimization data (see `Imagify\Optimization\Data\WP::update_size_optimization_data()`), so any
 * consumer must go through this class rather than retyping the literal.
 *
 * Two other copies of the same literals currently exist in `classes/Bulk/WP.php` (around lines
 * 320 and 325, for the `permanent_error` and `success` markers respectively) and in
 * `classes/Optimization/Process/AbstractProcess::has_avif()`. They predate this helper and were
 * left untouched to avoid destabilizing branches with in-flight changes; whoever next touches
 * those call sites should consider adopting this class instead.
 */
class NextGenMarker {
	/**
	 * Builds the needle matching a size that was successfully converted to the given next-gen
	 * format (e.g. `@imagify-webp";a:4:{s:7:"success";b:1;`).
	 *
	 * @param string $suffix The format suffix, e.g. `ProcessInterface::WEBP_SUFFIX` or `::AVIF_SUFFIX`.
	 *
	 * @return string
	 */
	public static function success( string $suffix ): string {
		return $suffix . '";a:4:{s:7:"success";b:1;';
	}

	/**
	 * Builds the needle matching a size the API permanently refused to convert (as opposed to a
	 * transient failure, which is retried and does not carry the `permanent_error` key).
	 *
	 * @param string $suffix The format suffix, e.g. `ProcessInterface::WEBP_SUFFIX` or `::AVIF_SUFFIX`.
	 *
	 * @return string
	 */
	public static function permanent_error( string $suffix ): string {
		return $suffix . '";a:3:{s:15:"permanent_error";b:1;';
	}
}
