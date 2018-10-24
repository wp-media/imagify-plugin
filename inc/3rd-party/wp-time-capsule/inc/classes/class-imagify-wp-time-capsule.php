<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Class that handles our partnership with WP Time Capsule.
 *
 * @since  1.8.2
 * @author Grégory Viguier
 */
class Imagify_WP_Time_Capsule {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.8.2
	 * @author Grégory Viguier
	 */
	const VERSION = '1.0';

	/**
	 * Partner ID.
	 *
	 * @var    string
	 * @since  1.8.2
	 * @author Grégory Viguier
	 */
	const PARTNER_ID = 'imagify';

	/**
	 * Affiliate base URL.
	 *
	 * @var    string
	 * @since  1.8.2
	 * @author Grégory Viguier
	 */
	const BASE_URL = 'https://wptimecapsule.com/updates/?partner=';

	/**
	 * The single instance of the class.
	 *
	 * @var    object Imagify_WP_Time_Machine
	 * @since  1.8.2
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected static $_instance;

	/**
	 * Temporary state property that tells if the button in the iframe should be added.
	 *
	 * @var    bool
	 * @since  1.8.2
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $add_iframe_button = false;


	/** ----------------------------------------------------------------------------------------- */
	/** INSTANCE ================================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the main Instance.
	 *
	 * @since  1.8.2
	 * @access public
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
	 * The constructor.
	 *
	 * @since  1.8.2
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected function __construct() {}


	/** ----------------------------------------------------------------------------------------- */
	/** INIT ==================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Launch the hooks.
	 *
	 * @since  1.8.2
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		if ( ! is_admin() ) {
			return;
		}

		if ( defined( 'IMAGIFY_DISPLAY_PARTNERS' ) && ! IMAGIFY_DISPLAY_PARTNERS ) {
			return;
		}

		add_filter( 'imagify_deactivatable_partners', array( $this, 'add_self' ) );

		if ( ! get_imagify_option( 'partner_links' ) ) {
			return;
		}

		add_action( 'in_plugin_update_message-' . plugin_basename( IMAGIFY_FILE ), array( $this, 'print_link_in_plugin_update_message' ), 10, 2 );
		add_action( 'admin_print_styles-update-core.php',                          array( $this, 'add_style_to_core_update' ) );
		add_action( 'admin_footer-update-core.php',                                array( $this, 'add_script_to_core_update' ) );
		add_filter( 'plugins_api_result',                                          array( $this, 'maybe_print_link_in_iframe_init' ), 10, 3 );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Add this partner to the list of partners affected by the display option.
	 *
	 * @since  1.8.2
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $partners An array of partner names.
	 * @return array
	 */
	public function add_self( $partners ) {
		$partners[] = 'WP Time Capsule';
		return $partners;
	}

	/**
	 * Add a link in Imagify's update message (plugins list).
	 *
	 * @since  1.8.2
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $plugin_data {
	 *     An array of plugin metadata.
	 *
	 *     @type string $name        The human-readable name of the plugin.
	 *     @type string $plugin_uri  Plugin URI.
	 *     @type string $version     Plugin version.
	 *     @type string $description Plugin description.
	 *     @type string $author      Plugin author.
	 *     @type string $author_uri  Plugin author URI.
	 *     @type string $text_domain Plugin text domain.
	 *     @type string $domain_path Relative path to the plugin's .mo file(s).
	 *     @type bool   $network     Whether the plugin can only be activated network wide.
	 *     @type string $title       The human-readable title of the plugin.
	 *     @type string $author_name Plugin author's name.
	 *     @type bool   $update      Whether there's an available update. Default null.
	 * }
	 * @param array $response {
	 *     An array of metadata about the available plugin update.
	 *
	 *     @type int    $id          Plugin ID.
	 *     @type string $slug        Plugin slug.
	 *     @type string $new_version New plugin version.
	 *     @type string $url         Plugin URL.
	 *     @type string $package     Plugin update package URL.
	 * }
	 */
	public function print_link_in_plugin_update_message( $plugin_data, $response ) {
		if ( empty( $response->package ) || ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$link  = '<a href="' . esc_url( $this->get_url() ) . '" target="_blank" class="imagify-wp-time-capsule-link">';
		$zelda = '<span class="dashicons dashicons-external" aria-hidden="true"></span>';

		echo ' ';
		/* translators: 1 is a link opening, 2 is a "new window" icon, 3 is the link closing. */
		printf( __( 'You can also %1$sbackup and update %2$s%3$s.', 'imagify' ), $link, $zelda, '</a>' );
	}

	/**
	 * Print some CSS in the Core Update page for the link into the Imagify row.
	 *
	 * @since  1.8.2
	 * @access public
	 * @author Grégory Viguier
	 */
	public function add_style_to_core_update() {
		$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );

		if ( $action && 'upgrade-core' !== $action ) {
			return;
		}

		echo "<style type='text/css'>\n";
		echo ".imagify-wp-time-capsule-link:after { content: '\\f504'; font-size: 16px; vertical-align: top; font-family: dashicons; font-style: normal; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }\n";
		echo "</style>\n";
	}

	/**
	 * Print some JS in the Core Update page to add a link into the Imagify row.
	 *
	 * @since  1.8.2
	 * @access public
	 * @author Grégory Viguier
	 */
	public function add_script_to_core_update() {
		$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );

		if ( $action && 'upgrade-core' !== $action ) {
			return;
		}

		$link = __( 'Backup and Update', 'imagify' ) . ' <span class="dashicons dashicons-external"></span>';
		$link = ' <a class="imagify-wp-time-capsule-link" href="' . esc_url( $this->get_url() ) . '" target="_blank">' . __( 'Backup and Update', 'imagify' ) . ' </a>';

		$output = html_entity_decode( $link, ENT_QUOTES, 'UTF-8' );
		$output = wp_json_encode( $output );
		$output = 'jQuery( "#update-plugins-table .plugin-title p [href*=\"&plugin=imagify&\"]").after( ' . $output . ' );';

		echo "<script type='text/javascript'>\n";
		echo "/* <![CDATA[ */\n";
		echo "$output\n";
		echo "/* ]]> */\n";
		echo "</script>\n";
	}

	/**
	 * Maybe launch the hooks that will add a button in the plugin information modal.
	 *
	 * @since  1.8.2
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  false|object|array $result The result object or array. Default false.
	 * @param  string             $action The type of information being requested from the Plugin Installation API.
	 * @param  object             $args   Plugin API arguments.
	 * @return false|object|array
	 */
	public function maybe_print_link_in_iframe_init( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		if ( empty( $args->slug ) || 'imagify' !== $args->slug ) {
			return $result;
		}

		$res = (object) $result;

		if ( empty( $res->slug ) || 'imagify' !== $res->slug ) {
			return $result;
		}

		if ( empty( $res->download_link ) || ( ! current_user_can( 'install_plugins' ) && ! current_user_can( 'update_plugins' ) ) ) {
			return $result;
		}

		$status = install_plugin_install_status( $res );

		if ( 'update_available' !== $status['status'] ) {
			return $result;
		}

		// OK, it's us.
		$this->add_iframe_button = true;

		add_action( 'admin_head-plugin-install.php', array( $this, 'print_link_in_iframe_init' ), IMAGIFY_INT_MAX );

		return $result;
	}

	/**
	 * Launch a ob_start() at the beginning of the plugin information modal.
	 *
	 * @since  1.8.2
	 * @access public
	 * @author Grégory Viguier
	 */
	public function print_link_in_iframe_init() {
		if ( ! $this->add_iframe_button ) {
			return;
		}

		ob_start();

		add_action( 'admin_footer', array( $this, 'print_link_in_iframe' ), 5 );
	}

	/**
	 * Add a button in the plugin information modal.
	 *
	 * @since  1.8.2
	 * @access public
	 * @author Grégory Viguier
	 */
	public function print_link_in_iframe() {
		global $tab;

		if ( ! $this->add_iframe_button ) {
			return;
		}

		$this->add_iframe_button = false;

		$footer = preg_quote( "<div id='$tab-footer'>", '@' );
		$button = __( 'Backup and Update', 'imagify' ) . ' <span class="dashicons dashicons-external" aria-hidden="true" style="margin:-2px 0 -1px"></span>';
		$button = '<a class="button button-primary right imagify-wp-time-capsule-link" href="' . esc_url( $this->get_url() ) . '" target="_blank" style="margin-right:10px">' . $button . '</a>';

		echo preg_replace( "@{$footer}.+</a>@s", '$0' . $button, ob_get_clean() );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the partnership URL.
	 *
	 * @since  1.8.2
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_url() {
		return self::BASE_URL . self::PARTNER_ID;
	}
}
