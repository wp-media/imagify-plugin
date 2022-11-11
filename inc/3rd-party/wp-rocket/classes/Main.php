<?php
namespace Imagify\ThirdParty\WPRocket;

use Imagify\Traits\InstanceGetterTrait;

/**
 * Compat class for WP Rocket plugin.
 *
 * @since 1.9.3
 */
class Main {
	use InstanceGetterTrait;

	/**
	 * Launch the hooks.
	 *
	 * @since 1.9.3
	 */
	public function init() {
		add_filter( 'imagify_cdn_source', [ $this, 'set_cdn_source' ] );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Provide a custom CDN source.
	 *
	 * @since 1.9.3
	 *
	 * @param  array $source {
	 *     An array of arguments.
	 *
	 *     @type $name string The name of which provides the URL (plugin name, etc).
	 *     @type $url  string The CDN URL.
	 * }
	 * @return array
	 */
	public function set_cdn_source( $source ) {
		if ( ! function_exists( 'get_rocket_option' ) ) {
			return $source;
		}

		if ( ! get_rocket_option( 'cdn' ) ) {
			return $source;
		}

		$container = apply_filters( 'rocket_container', null );

		if ( is_object( $container ) && method_exists( $container, 'get' ) ) {
			$cdn = $container->get( 'cdn' );

			if ( $cdn && method_exists( $cdn, 'get_cdn_urls' ) ) {
				$url = $cdn->get_cdn_urls( [ 'all', 'images' ] );
			}
		}

		if ( ! isset( $url ) && function_exists( 'get_rocket_cdn_cnames' ) ) {
			$url = get_rocket_cdn_cnames( [ 'all', 'images' ] );
		}

		if ( empty( $url ) ) {
			return $source;
		}

		$url = reset( $url );

		if ( ! $url ) {
			return $source;
		}

		if ( ! preg_match( '@^(https?:)?//@i', $url ) ) {
			$url = '//' . $url;
		}

		$scheme = wp_parse_url( \Imagify_Filesystem::get_instance()->get_site_root_url() );
		$scheme = ! empty( $scheme['scheme'] ) ? $scheme['scheme'] : null;
		$url    = set_url_scheme( $url, $scheme );

		$source['name'] = 'WP Rocket';
		$source['url']  = $url;

		return $source;
	}
}
