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
		add_action( 'imagify_assets_enqueued', [ $this, 'dequeue_sweetalert' ] );
		add_filter( 'imagify_cdn_source',      [ $this, 'set_cdn_source' ] );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Don't load Imagify CSS & JS files on WP Rocket options screen to avoid conflict with older version of SweetAlert.
	 *
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
	 */
	public function dequeue_sweetalert() {
		if ( ! defined( 'WP_ROCKET_PLUGIN_SLUG' ) ) {
			return;
		}

		if ( ! imagify_is_screen( 'settings_page_' . WP_ROCKET_PLUGIN_SLUG ) && ! imagify_is_screen( 'settings_page_' . WP_ROCKET_PLUGIN_SLUG . '-network' ) ) {
			return;
		}

		\Imagify_Assets::get_instance()->dequeue_script( array( 'sweetalert-core', 'sweetalert', 'notices' ) );
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
		if ( ! function_exists( 'get_rocket_cdn_cnames' ) || ! function_exists( 'get_rocket_option' ) ) {
			return $source;
		}

		if ( ! get_rocket_option( 'cdn' ) ) {
			return $source;
		}

		$url = get_rocket_cdn_cnames( [ 'all', 'images' ] );

		if ( ! $url ) {
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
