<?php
declare(strict_types=1);

namespace Imagify\CDN;

use Imagify\EventManagement\SubscriberInterface;

/**
 * CDN subscriber
 */
class CDN implements SubscriberInterface {
	/**
	 * Array of events this subscriber listens to
	 *
	 * @return array
	 */
	public static function get_subscribed_events() {
		return [
			'imagify_cdn_source_url' => 'get_cdn_source',
		];
	}

	/**
	 * Get the CDN "source".
	 *
	 * @since 1.9.3
	 *
	 * @param string $option_url An URL to use instead of the one stored in the option. It is used only if no constant/filter.
	 *
	 * @return array  {
	 *     @type string $source Where does it come from? Possible values are 'constant', 'filter', or 'option'.
	 *     @type string $name   Who? Can be a constant name, a plugin name, or an empty string.
	 *     @type string $url    The CDN URL, with a trailing slash. An empty string if no URL is set.
	 * }
	 */
	public function get_cdn_source( $option_url = '' ) {
		if ( defined( 'IMAGIFY_CDN_URL' ) && IMAGIFY_CDN_URL && is_string( IMAGIFY_CDN_URL ) ) {
			// Use a constant.
			$source = [
				'source' => 'constant',
				'name'   => 'IMAGIFY_CDN_URL',
				'url'    => IMAGIFY_CDN_URL,
			];
		} else {
			// Maybe use a filter.
			$filter_source = [
				'name' => null,
				'url'  => null,
			];

			/**
			 * Provide a custom CDN source.
			 *
			 * @since 1.9.3
			 *
			 * @param array $filter_source {
			 *     @type $name string The name of which provides the URL (plugin name, etc).
			 *     @type $url  string The CDN URL.
			 * }
			 */
			$filter_source = apply_filters( 'imagify_cdn_source', $filter_source );

			if ( ! empty( $filter_source['url'] ) ) {
				$source = [
					'source' => 'filter',
					'name'   => ! empty( $filter_source['name'] ) ? $filter_source['name'] : '',
					'url'    => $filter_source['url'],
				];
			}
		}

		if ( empty( $source['url'] ) ) {
			// No constant, no filter: use the option.
			$source = [
				'source' => 'option',
				'name'   => '',
				'url'    => $option_url && is_string( $option_url ) ? $option_url : get_imagify_option( 'cdn_url' ),
			];
		}

		if ( empty( $source['url'] ) ) {
			// Nothing set.
			return [
				'source' => 'option',
				'name'   => '',
				'url'    => '',
			];
		}

		$source['url'] = $this->sanitize_cdn_url( $source['url'] );

		if ( empty( $source['url'] ) ) {
			// Not an URL.
			return [
				'source' => 'option',
				'name'   => '',
				'url'    => '',
			];
		}

		return $source;
	}

	/**
	 * Sanitize the CDN URL value.
	 *
	 * @since 1.9.3
	 *
	 * @param string $url The URL to sanitize.
	 *
	 * @return string
	 */
	public function sanitize_cdn_url( $url ) {
		$url = sanitize_text_field( $url );

		if ( ! $url || ! preg_match( '@^https?://.+\.[^.]+@i', $url ) ) {
			// Not an URL.
			return '';
		}

		return trailingslashit( $url );
	}
}
