<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Class that handles templates and menus.
 *
 * @since  1.7
 * @author Grégory Viguier
 */
class Imagify_Views {

	/**
	 * Class version.
	 *
	 * @var   string
	 * @since 1.7
	 */
	const VERSION = '1.0';

	/**
	 * Slug used for the settings page URL.
	 *
	 * @var    string
	 * @since  1.7
	 * @access protected
	 */
	protected $slug_settings;

	/**
	 * Slug used for the bulk optimization page URL.
	 *
	 * @var    string
	 * @since  1.7
	 * @access protected
	 */
	protected $slug_bulk;

	/**
	 * Slug used for the "custom folders" page URL.
	 *
	 * @var    string
	 * @since  1.7
	 * @access protected
	 */
	protected $slug_files;

	/**
	 * Stores the "custom folders" files list instance.
	 *
	 * @var    object Imagify_Files_List_Table
	 * @since  1.7
	 * @access protected
	 */
	protected $list_table;

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.7
	 * @access protected
	 */
	protected static $_instance;


	/** ----------------------------------------------------------------------------------------- */
	/** INSTANCE/INIT =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * The constructor.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access protected
	 */
	protected function __construct() {
		$this->slug_settings = IMAGIFY_SLUG;
		$this->slug_bulk     = IMAGIFY_SLUG . '-bulk-optimization';
		$this->slug_files    = IMAGIFY_SLUG . '-files';
	}

	/**
	 * Get the main Instance.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
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
	 * Launch the hooks.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function init() {
		// Menu items.
		add_action( 'admin_menu', array( $this, 'add_site_menus' ) );
		add_action( ( imagify_is_active_for_network() ? 'network_' : '' ) . 'admin_menu', array( $this, 'add_network_menus' ) );

		// Action links in plugins list.
		$basename = plugin_basename( IMAGIFY_FILE );
		add_filter( 'plugin_action_links_' . $basename,               array( $this, 'plugin_action_links' ) );
		add_filter( 'network_admin_plugin_action_links_' . $basename, array( $this, 'plugin_action_links' ) );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** MENU ITEMS ============================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Add the bulk optimization sub-menu under "Library".
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function add_site_menus() {
		add_media_page( __( 'Bulk Optimization', 'imagify' ), __( 'Bulk Optimization', 'imagify' ), imagify_get_capacity( 'bulk-optimize' ), $this->get_bulk_page_slug(), array( $this, 'display_bulk_page' ) );
	}

	/**
	 * Add menu items for the the settings and for the "custom folders" files.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function add_network_menus() {
		global $submenu;

		$capa_settings = imagify_get_capacity();
		$capa_files    = imagify_get_capacity( 'optimize-file' );

		if ( imagify_is_active_for_network() ) {
			// Plugin is network activated.
			if ( imagify_can_optimize_custom_folders() ) {
				// Main item: custom folders. Sub-menu item: settings.
				$screen_id = add_menu_page( __( 'Themes and Plugins Images', 'imagify' ), 'Imagify', $capa_files, $this->get_files_page_slug(), array( $this, 'display_files_list' ) );
				add_submenu_page( $this->get_files_page_slug(), 'Imagify', __( 'Settings', 'imagify' ), $capa_settings, $this->get_settings_page_slug(), array( $this, 'display_settings_page' ) );

				// Change the sub-menu label.
				if ( ! empty( $submenu[ $this->get_files_page_slug() ] ) ) {
					$submenu[ $this->get_files_page_slug() ][0][0] = __( 'Optimized Files', 'imagify' ); // WPCS: override ok.
				}

				// On the "Themes and Plugins Images" page, load the data.
				add_action( 'load-' . $screen_id, array( $this, 'load_files_list' ) );
				return;
			}

			// Main item: settings (edge case).
			add_menu_page( 'Imagify', 'Imagify', $capa_settings, $this->get_settings_page_slug(), array( $this, 'display_settings_page' ) );
			return;
		}

		if ( imagify_can_optimize_custom_folders() ) {
			// Sub-menu item: custom folders.
			$screen_id = add_media_page( __( 'Themes and Plugins Images', 'imagify' ), __( 'Optimized Files', 'imagify' ), $capa_files, $this->get_files_page_slug(), array( $this, 'display_files_list' ) );

			// On the "Themes and Plugins Images" page, load the data.
			add_action( 'load-' . $screen_id, array( $this, 'load_files_list' ) );
		}

		// Sub-menu item: settings.
		add_options_page( 'Imagify', 'Imagify', $capa_settings, $this->get_settings_page_slug(), array( $this, 'display_settings_page' ) );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** PLUGIN ACTION LINKS ===================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Add links to the plugin row in the plugins list.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  array $actions An array of action links.
	 * @return array
	 */
	public function plugin_action_links( $actions ) {
		array_unshift( $actions, sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( imagify_get_external_url( 'documentation' ) ), __( 'Documentation', 'imagify' ) ) );
		array_unshift( $actions, sprintf( '<a href="%s">%s</a>', esc_url( get_imagify_admin_url( 'bulk-optimization' ) ), __( 'Bulk Optimization', 'imagify' ) ) );
		array_unshift( $actions, sprintf( '<a href="%s">%s</a>', esc_url( get_imagify_admin_url() ), __( 'Settings', 'imagify' ) ) );
		return $actions;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** MAIN PAGE TEMPLATES ===================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * The main settings page.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function display_settings_page() {
		$this->print_template( 'page-settings' );
	}

	/**
	 * The bulk optimization page.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function display_bulk_page() {
		$this->print_template( 'page-bulk' );
	}

	/**
	 * The page displaying the "custom folders" files.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function display_files_list() {
		$this->print_template( 'page-files-list' );
	}

	/**
	 * Initiate the "custom folders" list table data.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function load_files_list() {
		// Nothing yet.
	}


	/** ----------------------------------------------------------------------------------------- */
	/** GETTERS ================================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the settings page slug.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return string
	 */
	public function get_settings_page_slug() {
		return $this->slug_settings;
	}

	/**
	 * Get the bulk optimization page slug.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return string
	 */
	public function get_bulk_page_slug() {
		return $this->slug_bulk;
	}

	/**
	 * Get the "custom folders" files page slug.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return string
	 */
	public function get_files_page_slug() {
		return $this->slug_files;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** GENERIC TEMPLATE TOOLS ================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get a template contents.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  string $template The template name.
	 * @param  mixed  $data     Some data to pass to the template.
	 * @return string|bool      The page contents. False if the template doesn't exist.
	 */
	public function get_template( $template, $data = array() ) {
		$path = IMAGIFY_INC_PATH . 'views/' . $template . '.php';

		if ( ! imagify_get_filesystem()->exists( $path ) ) {
			return false;
		}

		ob_start();
		include $path;
		$contents = ob_get_clean();

		return trim( (string) $contents );
	}

	/**
	 * Print a template.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param string $template The template name.
	 * @param mixed  $data     Some data to pass to the template.
	 */
	public function print_template( $template, $data = array() ) {
		echo $this->get_template( $template, $data );
	}
}
