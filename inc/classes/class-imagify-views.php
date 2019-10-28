<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

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
	const VERSION = '1.1';

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
	 * A list of JS templates to print at the end of the page.
	 *
	 * @var    array
	 * @since  1.9
	 * @access protected
	 */
	protected $templates_in_footer = [];

	/**
	 * Stores the "custom folders" files list instance.
	 *
	 * @var    object Imagify_Files_List_Table
	 * @since  1.7
	 * @access protected
	 */
	protected $list_table;

	/**
	 * Filesystem object.
	 *
	 * @var    object Imagify_Filesystem
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

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
		$this->filesystem    = Imagify_Filesystem::get_instance();
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
		add_action( 'admin_menu', [ $this, 'add_site_menus' ] );

		if ( imagify_is_active_for_network() ) {
			add_action( 'network_admin_menu', [ $this, 'add_network_menus' ] );
		}

		// Action links in plugins list.
		$basename = plugin_basename( IMAGIFY_FILE );
		add_filter( 'plugin_action_links_' . $basename,               [ $this, 'plugin_action_links' ] );
		add_filter( 'network_admin_plugin_action_links_' . $basename, [ $this, 'plugin_action_links' ] );

		// Save the "per page" option value from the files list screen.
		add_filter( 'set-screen-option', [ 'Imagify_Files_List_Table', 'save_screen_options' ], 10, 3 );

		// JS templates in footer.
		add_action( 'admin_print_footer_scripts', [ $this, 'print_js_templates' ] );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** MENU ITEMS ============================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Add sub-menus for all sites.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function add_site_menus() {
		$wp_context = imagify_get_context( 'wp' );

		// Sub-menu item: bulk optimization.
		add_media_page( __( 'Bulk Optimization', 'imagify' ), __( 'Bulk Optimization', 'imagify' ), $wp_context->get_capacity( 'bulk-optimize' ), $this->get_bulk_page_slug(), [ $this, 'display_bulk_page' ] );

		if ( imagify_is_active_for_network() ) {
			return;
		}

		/**
		 * Plugin is not network activated.
		 */
		if ( imagify_can_optimize_custom_folders() ) {
			// Sub-menu item: custom folders list.
			$cf_context = imagify_get_context( 'custom-folders' );
			$screen_id  = add_media_page( __( 'Other Media optimized by Imagify', 'imagify' ), __( 'Other Media', 'imagify' ), $cf_context->get_capacity( 'optimize' ), $this->get_files_page_slug(), [ $this, 'display_files_list' ] );

			if ( $screen_id ) {
				// Load the data for this page.
				add_action( 'load-' . $screen_id, [ $this, 'load_files_list' ] );
			}
		}

		// Sub-menu item: settings.
		add_options_page( 'Imagify', 'Imagify', $wp_context->get_capacity( 'manage' ), $this->get_settings_page_slug(), [ $this, 'display_settings_page' ] );
	}

	/**
	 * Add menu and sub-menus in the network admin when Imagify is network-activated.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function add_network_menus() {
		global $submenu;

		$wp_context = imagify_get_context( 'wp' );

		if ( ! imagify_can_optimize_custom_folders() ) {
			// Main item: settings (edge case).
			add_menu_page( 'Imagify', 'Imagify', $wp_context->get_capacity( 'manage' ), $this->get_settings_page_slug(), array( $this, 'display_settings_page' ) );
			return;
		}

		$cf_context = imagify_get_context( 'custom-folders' );

		// Main item: bulk optimization (custom folders).
		add_menu_page( __( 'Bulk Optimization', 'imagify' ), 'Imagify', $cf_context->current_user_can( 'bulk-optimize' ), $this->get_bulk_page_slug(), array( $this, 'display_bulk_page' ) );

		// Sub-menu item: custom folders list.
		$screen_id = add_submenu_page( $this->get_bulk_page_slug(), __( 'Other Media optimized by Imagify', 'imagify' ), __( 'Other Media', 'imagify' ), $cf_context->current_user_can( 'bulk-optimize' ), $this->get_files_page_slug(), array( $this, 'display_files_list' ) );

		// Sub-menu item: settings.
		add_submenu_page( $this->get_bulk_page_slug(), 'Imagify', __( 'Settings', 'imagify' ), $wp_context->get_capacity( 'manage' ), $this->get_settings_page_slug(), array( $this, 'display_settings_page' ) );

		// Change the sub-menu label.
		if ( ! empty( $submenu[ $this->get_bulk_page_slug() ] ) ) {
			$submenu[ $this->get_bulk_page_slug() ][0][0] = __( 'Bulk Optimization', 'imagify' ); // WPCS: override ok.
		}

		if ( $screen_id ) {
			// On the "Other Media optimized by Imagify" page, load the data.
			add_action( 'load-' . $screen_id, array( $this, 'load_files_list' ) );
		}
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
		$types = array();
		$data  = array(
			// Limits.
			'unoptimized_attachment_limit' => 0,
			// What to optimize.
			'icon'                         => 'images-alt2',
			'title'                        => __( 'Optimize your media files', 'imagify' ),
			'groups'                       => array(),
		);

		if ( imagify_is_screen( 'bulk' ) ) {
			if ( ! is_network_admin() ) {
				/**
				 * Library: in each site.
				 */
				$types['library|wp'] = 1;
			}

			if ( imagify_can_optimize_custom_folders() && ( imagify_is_active_for_network() && is_network_admin() || ! imagify_is_active_for_network() ) ) {
				/**
				 * Custom folders: in network admin only if network activated, in each site otherwise.
				 */
				$types['custom-folders|custom-folders'] = 1;
			}
		}

		/**
		 * Filter the types to display in the bulk optimization page.
		 *
		 * @since  1.7.1
		 * @author Grégory Viguier
		 *
		 * @param array $types The folder types displayed on the page. If a folder type is "library", the context should be suffixed after a pipe character. They are passed as array keys.
		 */
		$types = apply_filters( 'imagify_bulk_page_types', $types );
		$types = array_filter( (array) $types );

		if ( isset( $types['library|wp'] ) ) {
			// Limits.
			$data['unoptimized_attachment_limit'] += imagify_get_unoptimized_attachment_limit();
			// Group.
			$data['groups']['library'] = array(
				/**
				 * The group_id corresponds to the file names like 'part-bulk-optimization-results-row-{$group_id}'.
				 * It is also used in get_imagify_localize_script_translations().
				 */
				'group_id' => 'library',
				'context'  => 'wp',
				'title'    => __( 'Media Library', 'imagify' ),
				/* translators: 1 is the opening of a link, 2 is the closing of this link. */
				'footer'   => sprintf( __( 'You can also re-optimize your media files from your %1$sMedia Library%2$s screen.', 'imagify' ), '<a href="' . esc_url( admin_url( 'upload.php' ) ) . '">', '</a>' ),
			);
		}

		if ( isset( $types['custom-folders|custom-folders'] ) ) {
			if ( ! Imagify_Folders_DB::get_instance()->has_items() ) {
				// New Feature!
				$data['no-custom-folders'] = true;
			} elseif ( Imagify_Folders_DB::get_instance()->has_active_folders() ) {
				// Group.
				$data['groups']['custom-folders'] = array(
					'group_id' => 'custom-folders',
					'context'  => 'custom-folders',
					'title'    => __( 'Custom folders', 'imagify' ),
					/* translators: 1 is the opening of a link, 2 is the closing of this link. */
					'footer'   => sprintf( __( 'You can re-optimize your media files more finely directly in the %1$smedia management%2$s.', 'imagify' ), '<a href="' . esc_url( get_imagify_admin_url( 'files-list' ) ) . '">', '</a>' ),
				);
			}
		}

		// Add generic stats.
		$data = array_merge( $data, imagify_get_bulk_stats( $types, array(
			'fullset' => true,
		) ) );

		/**
		 * Filter the data to use on the bulk optimization page.
		 *
		 * @since  1.7
		 * @since  1.7.1 Added the $types parameter.
		 * @author Grégory Viguier
		 *
		 * @param array $data  The data to use.
		 * @param array $types The folder types displayed on the page. They are passed as array keys.
		 */
		$data = apply_filters( 'imagify_bulk_page_data', $data, $types );

		$this->print_template( 'page-bulk', $data );
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
		// Instantiate the list.
		$this->list_table = new Imagify_Files_List_Table( array(
			'screen' => 'imagify-files',
		) );

		// Query the Items.
		$this->list_table->prepare_items();
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
	/** PAGE TESTS ============================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if we’re displaying the settings page.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return bool
	 */
	public function is_settings_page() {
		global $pagenow;

		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );

		if ( $this->get_settings_page_slug() !== $page ) {
			return false;
		}

		if ( imagify_is_active_for_network() ) {
			return 'admin.php' === $pagenow;
		}

		return 'options-general.php' === $pagenow;
	}

	/**
	 * Tell if we’re displaying the bulk optimization page.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return bool
	 */
	public function is_bulk_page() {
		global $pagenow;

		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );

		return 'upload.php' === $pagenow && $this->get_bulk_page_slug() === $page;
	}

	/**
	 * Tell if we’re displaying the custom files list page.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return bool
	 */
	public function is_files_page() {
		global $pagenow;

		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );

		return 'upload.php' === $pagenow && $this->get_files_page_slug() === $page;
	}

	/**
	 * Tell if we’re displaying the WP media library page.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return bool
	 */
	public function is_wp_library_page() {
		global $pagenow;

		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );

		return 'upload.php' === $pagenow && ! $page;
	}

	/**
	 * Tell if we’re displaying a media page.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return bool
	 */
	public function is_media_page() {
		global $pagenow, $typenow;

		return 'post.php' === $pagenow && 'attachment' === $typenow;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** QUOTA =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the remaining quota in percent.
	 *
	 * @since  1.8.1
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return int
	 */
	public function get_quota_percent() {
		static $quota;

		if ( isset( $quota ) ) {
			return $quota;
		}

		$user  = new Imagify_User();
		$quota = $user->get_percent_unconsumed_quota();

		return $quota;
	}

	/**
	 * Get the HTML class used for the quota (to change the color when out of quota for example).
	 *
	 * @since  1.8.1
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return string
	 */
	public function get_quota_class() {
		static $class;

		if ( isset( $class ) ) {
			return $class;
		}

		$quota = $this->get_quota_percent();
		$class = 'imagify-bar-';

		if ( $quota <= 20 ) {
			$class .= 'negative';
		} elseif ( $quota <= 50 ) {
			$class .= 'neutral';
		} else {
			$class .= 'positive';
		}

		return $class;
	}

	/**
	 * Get the HTML tag used for the quota (the weather-like icon).
	 *
	 * @since  1.8.1
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return string
	 */
	public function get_quota_icon() {
		static $icon;

		if ( isset( $icon ) ) {
			return $icon;
		}

		$quota = $this->get_quota_percent();

		if ( $quota <= 20 ) {
			$icon = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'stormy.svg" width="64" height="63" alt="" />';
		} elseif ( $quota <= 50 ) {
			$icon = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'cloudy-sun.svg" width="63" height="64" alt="" />';
		} else {
			$icon = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'sun.svg" width="63" height="64" alt="" />';
		}

		return $icon;
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
		$path = str_replace( '_', '-', $template );
		$path = IMAGIFY_PATH . 'views/' . $template . '.php';

		if ( ! $this->filesystem->exists( $path ) ) {
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

	/**
	 * Add a template to the list of JS templates to print at the end of the page.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param string $template The template name.
	 */
	public function print_js_template_in_footer( $template ) {
		if ( isset( $this->templates_in_footer[ $template ] ) ) {
			return;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		switch ( $template ) {
			case 'button/processing':
				$data = [ 'label' => '{{ data.label }}' ];
				break;
			default:
				$data = [];
		}

		$this->templates_in_footer[ $template ] = $data;
	}

	/**
	 * Print the JS templates that have been added to the "queue".
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 * @access public
	 */
	public function print_js_templates() {
		if ( ! $this->templates_in_footer ) {
			return;
		}

		foreach ( $this->templates_in_footer as $template => $data ) {
			$template_id = str_replace( [ '/', '_' ], '-', $template );

			echo '<script type="text/html" id="tmpl-imagify-' . $template_id . '">';
				$this->print_template( $template, $data );
			echo '</script>';
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Create HTML attributes from an array.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $attributes A list of attribute pairs.
	 * @return string            HTML attributes.
	 */
	public function build_attributes( $attributes ) {
		if ( ! $attributes || ! is_array( $attributes ) ) {
			return '';
		}

		$out = '';

		foreach ( $attributes as $attribute => $value ) {
			if ( '' === $value ) {
				continue;
			}

			$out .= ' ' . $attribute . '="' . esc_attr( $value ) . '"';
		}

		return $out;
	}
}
