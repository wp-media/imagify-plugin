<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Class that handles WP Rocket infos in the "Add Plugins" screen.
 *
 * @since 1.6.9
 */
class Imagify_Rocket_Infos {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0';

	/**
	 * The URL to request.
	 *
	 * @var string
	 */
	const REQUEST_URL = 'https://wp-rocket.me/stat/1.0/wp-rocket/';

	/**
	 * The name of the transient that stores the WP Rocket infos.
	 *
	 * @var string
	 */
	const TRANSIENT_NAME = 'imagify_rocket_plugin_info';

	/**
	 * Time until transient expiration in seconds.
	 *
	 * @access protected
	 * @var    int
	 */
	protected static $transient_expiration;

	/**
	 * Time until transient expiration in seconds, in case of error.
	 *
	 * @access protected
	 * @var    int
	 */
	protected static $error_expiration;

	/**
	 * The headers to send along the request.
	 *
	 * @access protected
	 * @var    array
	 */
	protected static $headers = array();

	/**
	 * The plugin information.
	 *
	 * @access protected
	 * @var    object
	 */
	protected static $plugin_information;

	/**
	 * The single instance of the class.
	 *
	 * @access protected
	 * @var    object
	 */
	protected static $_instance;


	/** ----------------------------------------------------------------------------------------- */
	/** INSTANCE, INIT ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Class constructor: set properties.
	 *
	 * @access protected
	 * @since  1.6.9
	 * @author Grégory Viguier
	 */
	protected function __construct() {
		self::$transient_expiration = 2 * WEEK_IN_SECONDS;
		self::$error_expiration     = 5 * MINUTE_IN_SECONDS;
		self::$headers['X-Locale']  = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
	}

	/**
	 * Get the main Instance.
	 *
	 * @access public
	 * @since  1.6.9
	 * @author Grégory Viguier
	 *
	 * @return object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Hooks init.
	 *
	 * @access public
	 * @since  1.6.9
	 * @author Grégory Viguier
	 */
	public function init() {
		global $wp_version;

		// Filter the plugin API results to inject WP Rocket.
		add_filter( 'plugins_api_result', array( $this, 'add_api_result' ), 11, 3 );

		// Filter the iframe src to return WP Rocket's site URL (More Details popup).
		if ( version_compare( $wp_version, '4.9' ) >= 0 ) {
			add_filter( 'self_admin_url',    array( $this, 'filter_iframe_src' ), 100, 2 );
		} else {
			add_filter( 'network_admin_url', array( $this, 'filter_iframe_src' ), 100, 2 );
			add_filter( 'user_admin_url',    array( $this, 'filter_iframe_src' ), 100, 2 );
			add_filter( 'admin_url',         array( $this, 'filter_iframe_src' ), 100, 2 );
		}

		// Filter the iframe action links to display a "Buy Now" button.
		add_filter( 'plugin_install_action_links', array( $this, 'add_action_link' ), 10, 2 );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Filter the plugin API results to inject WP Rocket.
	 *
	 * @access public
	 * @since  1.6.9
	 * @author Grégory Viguier
	 *
	 * @param object $result Response object or WP_Error object.
	 * @param string $action The type of information being requested from the Plugin Install API.
	 * @param object $args   Plugin API arguments.
	 *
	 * @return array|object  Updated array of results or WP_Error object.
	 */
	public function add_api_result( $result, $action, $args ) {
		if ( is_wp_error( $result ) || empty( $args->browse ) ) {
			return $result;
		}

		if ( 'featured' !== $args->browse && 'recommended' !== $args->browse && 'popular' !== $args->browse ) {
			return $result;
		}

		if ( ! isset( $result->info['page'] ) || 1 < $result->info['page'] ) {
			return $result;
		}

		if ( defined( 'WP_ROCKET_VERSION' ) || file_exists( WP_PLUGIN_DIR . '/wp-rocket/wp-rocket.php' ) ) {
			return $result;
		}

		$plugin_info = $this->get_plugin_info();

		if ( is_wp_error( $plugin_info ) ) {
			return $result;
		}

		if ( 'featured' === $args->browse ) {
			array_push( $result->plugins, $plugin_info );
		} else {
			array_unshift( $result->plugins, $plugin_info );
		}

		if ( isset( $result->info['results'] ) ) {
			++$result->info['results'];
		}

		return $result;
	}

	/**
	 * Filter the iframe src to return WP Rocket's site URL (More Details popup).
	 *
	 * @access public
	 * @since  1.6.9
	 * @author Grégory Viguier
	 *
	 * @param  string $url  The complete URL including scheme and path.
	 * @param  string $path Path relative to the URL. Blank string if no path is specified.
	 * @return string
	 */
	public function filter_iframe_src( $url, $path ) {
		if ( ! preg_match( '@/wp-admin/(?:network/user/)?plugin-install.php\?@', $url ) ) {
			return $url;
		}

		$parsed_url = wp_parse_url( $url );

		if ( empty( $parsed_url['query'] ) ) {
			return $url;
		}

		$parsed_url['query'] = htmlspecialchars_decode( $parsed_url['query'] );
		wp_parse_str( $parsed_url['query'], $params );

		if ( ! isset( $params['tab'], $params['plugin'] ) || 'plugin-information' !== $params['tab'] || 'wp-rocket' !== $params['plugin'] ) {
			return $url;
		}

		$url = imagify_get_wp_rocket_url( false, array(
			'utm_source'   => 'wpaddplugins',
			'utm_medium'   => 'imagify',
			'utm_campaign' => 'moredetails',
		) );

		return $url . '#TB_iframe=true&width=772&height=712';
	}

	/**
	 * Filter the iframe action links to display a "Buy Now" button.
	 *
	 * @access public
	 * @since  1.6.9
	 * @author Grégory Viguier
	 *
	 * @param  array $links  Links for plugin.
	 * @param  array $plugin Plugin data.
	 * @return array
	 */
	public function add_action_link( $links, $plugin ) {
		if ( empty( $plugin['slug'] ) || 'wp-rocket' !== $plugin['slug'] ) {
			return $links;
		}

		$link = '<a class="button" target="_blank" data-slug="wp-rocket" href="%s" aria-label="%s" data-name="WP Rocket">%s</a>';
		$url  = imagify_get_wp_rocket_url( false, array(
			'utm_source'   => 'wpaddplugins',
			'utm_medium'   => 'imagify',
			'utm_campaign' => 'imagify',
		) );

		array_unshift( $links, sprintf(
			$link,
			esc_url( $url ),
			/* translators: %s is a plugin name. */
			esc_attr( sprintf( __( 'Buy %s now', 'imagify' ), 'WP Rocket' ) ),
			__( 'Buy Now', 'imagify' )
		) );

		return $links;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS TO GET THE PLUGIN INFOS =========================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the plugin infos.
	 *
	 * @access public
	 * @since  1.6.9
	 * @author Grégory Viguier
	 *
	 * @return object The plugin informations object or WP_Error object.
	 */
	public function get_plugin_info() {
		if ( ! isset( self::$plugin_information ) ) {
			// Get the plugin info stored in a transient.
			self::$plugin_information = $this->get_stored_data();
		}

		if ( self::$plugin_information ) {
			// We have something.
			return self::$plugin_information;
		}

		// Fetch new data.
		self::$plugin_information = wp_remote_get( self::REQUEST_URL, array(
			'headers' => self::$headers,
			'timeout' => 15,
		) );

		if ( is_wp_error( self::$plugin_information ) ) {
			// Requests are blocked.
			return $this->store_data();
		}

		$response_code = wp_remote_retrieve_response_code( self::$plugin_information );

		if ( 200 !== $response_code ) {
			// Trouble on the WP Rocket site's side.
			self::$plugin_information = new WP_Error( 'imagify_response_code', 'Information about the plugin could not be retrieved.' );
			return $this->store_data();
		}

		self::$plugin_information = maybe_unserialize( wp_remote_retrieve_body( self::$plugin_information ) );

		if ( ! is_object( self::$plugin_information ) ) {
			// Trouble on the WP Rocket site's side.
			self::$plugin_information = new WP_Error( 'imagify_format', 'Information about the plugin could not be retrieved.' );
			return $this->store_data();
		}

		self::$plugin_information->short_description = 'The best WordPress caching plugin to speed up your site, optimize your SEO and increase your conversions. It’s never been easier to improve your loading time in a few clicks.';

		return $this->store_data();
	}

	/**
	 * Get the data stored in the database.
	 *
	 * @access public
	 * @since  1.6.9
	 * @author Grégory Viguier
	 *
	 * @return object The plugin informations object or WP_Error object.
	 */
	protected function get_stored_data() {
		self::$plugin_information = get_site_transient( self::TRANSIENT_NAME );
		return $this->translate_informations();
	}

	/**
	 * Store the data into in the database.
	 *
	 * @access public
	 * @since  1.6.9
	 * @author Grégory Viguier
	 *
	 * @return object The plugin informations object or WP_Error object.
	 */
	protected function store_data() {
		$expiration = self::$transient_expiration;

		if ( is_wp_error( self::$plugin_information ) ) {
			$expiration = self::$error_expiration;
		}

		set_site_transient( self::TRANSIENT_NAME, self::$plugin_information, $expiration );

		return $this->translate_informations();
	}

	/**
	 * Translate some of the plugin informations.
	 *
	 * @access public
	 * @since  1.6.9
	 * @author Grégory Viguier
	 *
	 * @return object The plugin informations object or WP_Error object.
	 */
	protected function translate_informations() {
		if ( ! self::$plugin_information || is_wp_error( self::$plugin_information ) ) {
			return self::$plugin_information;
		}

		/* translators: %s is a plugin version. */
		self::$plugin_information->requires = preg_replace( '@([\d.]+) or higher@', sprintf( __( '%s or higher', 'imagify' ), '$1' ), self::$plugin_information->requires );
		self::$plugin_information->requires = str_replace( 'Requires PHP:', __( 'Requires PHP:', 'imagify' ), self::$plugin_information->requires );
		self::$plugin_information->homepage = imagify_get_wp_rocket_url( false, array(
			'utm_source'   => 'wpaddplugins',
			'utm_medium'   => 'imagify',
			'utm_campaign' => 'imagify',
		) );
		self::$plugin_information->short_description = __( 'The best WordPress caching plugin to speed up your site, optimize your SEO and increase your conversions. It’s never been easier to improve your loading time in a few clicks.', 'imagify' );

		return self::$plugin_information;
	}
}

/**
 * Returns the main instance of the Imagify_Rocket_Infos class.
 *
 * @since  1.6.9
 * @author Grégory Viguier
 *
 * @return object The Imagify_Rocket_Infos instance.
 */
function imagify_rocket_infos() {
	return Imagify_Rocket_Infos::get_instance();
}
