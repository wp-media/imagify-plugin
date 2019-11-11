<?php
namespace Imagify\ThirdParty\WPRocket;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Compat class for WP Rocket plugin.
 *
 * @since  1.9.3
 * @author Grégory Viguier
 */
class Main {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * Launch the hooks.
	 *
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		add_action( 'admin_init',         [ $this, 'dequeue_sweetalert' ] );
		add_filter( 'imagify_cdn_source', [ $this, 'set_cdn_source' ] );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Remove all Imagify admin notices + CSS & JS files on WP Rocket (< 3.0) options screen to avoid conflict with older version of SweetAlert.
	 *
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
	 */
	public function dequeue_sweetalert() {
		if ( ! defined( 'WP_ROCKET_VERSION' ) || ! defined( 'WP_ROCKET_PLUGIN_SLUG' ) ) {
			return;
		}

		if ( version_compare( WP_ROCKET_VERSION, '3.0' ) >= 0 ) {
			return;
		}

		if ( ! imagify_is_screen( 'settings_page_' . WP_ROCKET_PLUGIN_SLUG ) && ! imagify_is_screen( 'settings_page_' . WP_ROCKET_PLUGIN_SLUG . '-network' ) ) {
			return;
		}

		remove_action( 'all_admin_notices',     [ \Imagify_Notices::get_instance(), 'render_notices' ] );
		remove_action( 'admin_enqueue_scripts', [ \Imagify_Assets::get_instance(), 'enqueue_styles_and_scripts' ], IMAGIFY_INT_MAX );
	}

	/**
	 * Provide a custom CDN source.
	 *
	 * @since  1.9.3
	 * @author Grégory Viguier
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
