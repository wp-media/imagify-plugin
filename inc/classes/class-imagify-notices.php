<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles the admin notices.
 *
 * @since  1.6.10
 * @author Grégory Viguier
 */
class Imagify_Notices extends Imagify_Notices_Deprecated {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.1';

	/**
	 * Name of the transient storing temporary notices.
	 *
	 * @var string
	 */
	const TEMPORARY_NOTICES_TRANSIENT_NAME = 'imagify_temporary_notices';

	/**
	 * Name of the user meta that stores the dismissed notice IDs.
	 *
	 * @var string
	 */
	const DISMISS_META_NAME = '_imagify_ignore_notices';

	/**
	 * Action used in the nonce to dismiss a notice.
	 *
	 * @var string
	 */
	const DISMISS_NONCE_ACTION = 'imagify-dismiss-notice';

	/**
	 * Action used in the nonce to deactivate a plugin.
	 *
	 * @var string
	 */
	const DEACTIVATE_PLUGIN_NONCE_ACTION = 'imagify-deactivate-plugin';

	/**
	 * List of notice IDs.
	 * They correspond to method names and IDs stored in the "dismissed" transient.
	 * Only use "-" character, not "_".
	 *
	 * @var array
	 */
	protected static $notice_ids = array(
		// This warning is displayed when the API key is empty. Dismissible.
		'welcome-steps',
		// This warning is displayed when the API key is wrong. Dismissible.
		'wrong-api-key',
		// This warning is displayed if some plugins are active. NOT dismissible.
		'plugins-to-deactivate',
		// This notice is displayed when external HTTP requests are blocked via the WP_HTTP_BLOCK_EXTERNAL constant. Dismissible.
		'http-block-external',
		// This warning is displayed when the grid view is active on the library. Dismissible.
		'grid-view',
		// This warning is displayed to warn the user that the quota is almost consumed for the current month. Dismissible.
		'almost-over-quota',
		// This warning is displayed if the backup folder is not writable. NOT dismissible.
		'backup-folder-not-writable',
		// This notice is displayed to rate the plugin after 100 optimizations & 7 days after the first installation. Dismissible.
		'rating',
		// Add a message about WP Rocket on the "Bulk Optimization" screen. Dismissible.
		'wp-rocket',
	);

	/**
	 * List of user capabilities to use for each notice.
	 * Default value 'manage' is not listed.
	 *
	 * @var array
	 */
	protected static $capabilities = array(
		'grid-view'                  => 'optimize',
		'backup-folder-not-writable' => 'bulk-optimize',
		'rating'                     => 'bulk-optimize',
		'wp-rocket'                  => 'bulk-optimize',
	);

	/**
	 * List of plugins that conflict with Imagify.
	 *
	 * @var array
	 */
	protected static $conflicting_plugins = array(
		'wp-smush'     => 'wp-smushit/wp-smush.php',                                   // WP Smush.
		'wp-smush-pro' => 'wp-smush-pro/wp-smush.php',                                 // WP Smush Pro.
		'kraken'       => 'kraken-image-optimizer/kraken.php',                         // Kraken.io.
		'tinypng'      => 'tiny-compress-images/tiny-compress-images.php',             // TinyPNG.
		'shortpixel'   => 'shortpixel-image-optimiser/wp-shortpixel.php',              // Shortpixel.
		'ewww'         => 'ewww-image-optimizer/ewww-image-optimizer.php',             // EWWW Image Optimizer.
		'ewww-cloud'   => 'ewww-image-optimizer-cloud/ewww-image-optimizer-cloud.php', // EWWW Image Optimizer Cloud.
		'imagerecycle' => 'imagerecycle-pdf-image-compression/wp-image-recycle.php',   // ImageRecycle.
	);

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $_instance;

	/**
	 * The constructor.
	 *
	 * @return void
	 */
	protected function __construct() {}


	/** ----------------------------------------------------------------------------------------- */
	/** INIT ==================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the main Instance.
	 *
	 * @since  1.6.10
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
	 * Launch the hooks.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 */
	public function init() {
		// For generic purpose.
		add_action( 'all_admin_notices',                     array( $this, 'render_notices' ) );
		add_action( 'wp_ajax_imagify_dismiss_notice',        array( $this, 'admin_post_dismiss_notice' ) );
		add_action( 'admin_post_imagify_dismiss_notice',     array( $this, 'admin_post_dismiss_notice' ) );
		// For specific notices.
		add_action( 'imagify_dismiss_notice',                array( $this, 'clear_scheduled_rating' ) );
		add_action( 'admin_post_imagify_deactivate_plugin',  array( $this, 'deactivate_plugin' ) );
		add_action( 'imagify_not_almost_over_quota_anymore', array( $this, 'renew_almost_over_quota_notice' ) );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Maybe display some notices.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 */
	public function render_notices() {
		foreach ( $this->get_notice_ids() as $notice_id ) {
			// Get the name of the method that will tell if this notice should be displayed.
			$callback = 'display_' . str_replace( '-', '_', $notice_id );

			if ( ! method_exists( $this, $callback ) ) {
				continue;
			}

			$data = call_user_func( array( $this, $callback ) );

			if ( $data ) {
				// The notice must be displayed: render the view.
				Imagify_Views::get_instance()->print_template( 'notice-' . $notice_id, $data );
			}
		}

		// Temporary notices.
		$this->render_temporary_notices();
	}

	/**
	 * Process a dismissed notice.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 * @see    _do_admin_post_imagify_dismiss_notice()
	 */
	public function admin_post_dismiss_notice() {
		imagify_check_nonce( self::DISMISS_NONCE_ACTION );

		$notice  = ! empty( $_GET['notice'] ) ? esc_html( wp_unslash( $_GET['notice'] ) ) : false;
		$notices = $this->get_notice_ids();
		$notices = array_flip( $notices );

		if ( ! $notice || ! isset( $notices[ $notice ] ) || ! $this->user_can( $notice ) ) {
			imagify_die();
		}

		self::dismiss_notice( $notice );

		/**
		 * Fires when a notice is dismissed.
		 *
		 * @since 1.4.2
		 *
		 * @param int $notice The notice slug
		*/
		do_action( 'imagify_dismiss_notice', $notice );

		imagify_maybe_redirect();
		wp_send_json_success();
	}

	/**
	 * Stop the rating cron when the notice is dismissed.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 * @see    _imagify_clear_scheduled_rating()
	 *
	 * @param string $notice The notice name.
	 */
	public function clear_scheduled_rating( $notice ) {
		if ( 'rating' === $notice ) {
			set_site_transient( 'do_imagify_rating_cron', 'no' );
			Imagify_Cron_Rating::get_instance()->unschedule_event();
		}
	}

	/**
	 * Disable a plugin which can be in conflict with Imagify.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 * @see    _imagify_deactivate_plugin()
	 */
	public function deactivate_plugin() {
		imagify_check_nonce( self::DEACTIVATE_PLUGIN_NONCE_ACTION );

		if ( empty( $_GET['plugin'] ) || ! $this->user_can( 'plugins-to-deactivate' ) ) {
			imagify_die();
		}

		$plugin  = esc_html( wp_unslash( $_GET['plugin'] ) );
		$plugins = $this->get_conflicting_plugins();
		$plugins = array_flip( $plugins );

		if ( empty( $plugins[ $plugin ] ) ) {
			imagify_die();
		}

		deactivate_plugins( $plugin );

		imagify_maybe_redirect();
		wp_send_json_success();
	}

	/**
	 * Renew the "almost-over-quota" notice when the consumed quota percent decreases back below 80%.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 */
	public function renew_almost_over_quota_notice() {
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT umeta_id, user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value LIKE %s", self::DISMISS_META_NAME, '%almost-over-quota%' ) );

		if ( ! $results ) {
			return;
		}

		// Prevent multiple queries to the DB by caching user metas.
		$not_cached = array();

		foreach ( $results as $result ) {
			if ( ! wp_cache_get( $result->umeta_id, 'user_meta' ) ) {
				$not_cached[] = $result->umeta_id;
			}
		}

		if ( $not_cached ) {
			update_meta_cache( 'user', $not_cached );
		}

		// Renew the notice for all users.
		foreach ( $results as $result ) {
			self::renew_notice( 'almost-over-quota', $result->user_id );
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** NOTICES ================================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if the 'welcome-steps' notice should be displayed.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function display_welcome_steps() {
		static $display;

		if ( isset( $display ) ) {
			return $display;
		}

		$display = false;

		if ( ! $this->user_can( 'welcome-steps' ) ) {
			return $display;
		}

		if ( imagify_is_screen( 'imagify-settings' ) ) {
			return $display;
		}

		if ( self::notice_is_dismissed( 'welcome-steps' ) || get_imagify_option( 'api_key' ) ) {
			return $display;
		}

		$display = true;
		return $display;
	}

	/**
	 * Tell if the 'wrong-api-key' notice should be displayed.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function display_wrong_api_key() {
		static $display;

		if ( isset( $display ) ) {
			return $display;
		}

		$display = false;

		if ( ! $this->user_can( 'wrong-api-key' ) ) {
			return $display;
		}

		if ( ! imagify_is_screen( 'bulk' ) ) {
			return $display;
		}

		if ( self::notice_is_dismissed( 'wrong-api-key' ) || ! get_imagify_option( 'api_key' ) || Imagify_Requirements::is_api_key_valid() ) {
			return $display;
		}

		$display = true;
		return $display;
	}

	/**
	 * Tell if the 'plugins-to-deactivate' notice should be displayed.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @return array An array of plugins to deactivate.
	 */
	public function display_plugins_to_deactivate() {
		static $display;

		if ( isset( $display ) ) {
			return $display;
		}

		if ( ! $this->user_can( 'plugins-to-deactivate' ) ) {
			$display = false;
			return $display;
		}

		$display = $this->get_conflicting_plugins();
		return $display;
	}

	/**
	 * Tell if the 'plugins-to-deactivate' notice should be displayed.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function display_http_block_external() {
		static $display;

		if ( isset( $display ) ) {
			return $display;
		}

		$display = false;

		if ( ! $this->user_can( 'http-block-external' ) ) {
			return $display;
		}

		if ( imagify_is_screen( 'imagify-settings' ) ) {
			return $display;
		}

		if ( self::notice_is_dismissed( 'http-block-external' ) || ! Imagify_Requirements::is_imagify_blocked() ) {
			return $display;
		}

		$display = true;
		return $display;
	}

	/**
	 * Tell if the 'grid-view' notice should be displayed.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function display_grid_view() {
		global $wp_version;
		static $display;

		if ( isset( $display ) ) {
			return $display;
		}

		$display = false;

		if ( ! $this->user_can( 'grid-view' ) ) {
			return $display;
		}

		if ( ! imagify_is_screen( 'library' ) ) {
			return $display;
		}

		$media_library_mode = get_user_option( 'media_library_mode', get_current_user_id() );

		if ( 'list' === $media_library_mode || self::notice_is_dismissed( 'grid-view' ) || version_compare( $wp_version, '4.0' ) < 0 ) {
			return $display;
		}

		// Don't display the notice if the API key isn't valid.
		if ( ! Imagify_Requirements::is_api_key_valid() ) {
			return $display;
		}

		$display = true;
		return $display;
	}

	/**
	 * Tell if the 'almost-over-quota' notice should be displayed.
	 *
	 * @since  1.7.0
	 * @author Geoffrey Crofte
	 *
	 * @return bool|object An Imagify user object. False otherwise.
	 */
	public function display_almost_over_quota() {
		static $display;

		if ( isset( $display ) ) {
			return $display;
		}

		$display = false;

		if ( ! $this->user_can( 'almost-over-quota' ) ) {
			return $display;
		}

		if ( ! imagify_is_screen( 'imagify-settings' ) && ! imagify_is_screen( 'bulk' ) ) {
			return $display;
		}

		if ( self::notice_is_dismissed( 'almost-over-quota' ) ) {
			return $display;
		}

		$user = new Imagify_User();

		// Don't display the notice if the user's unconsumed quota is superior to 20%.
		if ( $user->get_percent_unconsumed_quota() > 20 ) {
			return $display;
		}

		$display = $user;
		return $display;
	}

	/**
	 * Tell if the 'backup-folder-not-writable' notice should be displayed.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function display_backup_folder_not_writable() {
		global $post_id;
		static $display;

		if ( isset( $display ) ) {
			return $display;
		}

		$display = false;

		if ( ! $this->user_can( 'backup-folder-not-writable' ) ) {
			return $display;
		}

		// Every places where images can be optimized, automatically or not (+ the settings page).
		if ( ! imagify_is_screen( 'imagify-settings' ) && ! imagify_is_screen( 'library' ) && ! imagify_is_screen( 'upload' ) && ! imagify_is_screen( 'bulk' ) && ! imagify_is_screen( 'media-modal' ) ) {
			return $display;
		}

		if ( ! get_imagify_option( 'backup' ) ) {
			return $display;
		}

		if ( Imagify_Requirements::attachments_backup_dir_is_writable() ) {
			return $display;
		}

		$display = true;
		return $display;
	}

	/**
	 * Tell if the 'rating' notice should be displayed.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @return bool|int
	 */
	public function display_rating() {
		static $display;

		if ( isset( $display ) ) {
			return $display;
		}

		$display = false;

		if ( ! $this->user_can( 'rating' ) ) {
			return $display;
		}

		if ( ! imagify_is_screen( 'bulk' ) && ! imagify_is_screen( 'library' ) && ! imagify_is_screen( 'upload' ) ) {
			return $display;
		}

		if ( self::notice_is_dismissed( 'rating' ) ) {
			return $display;
		}

		$user_images_count = (int) get_site_transient( 'imagify_user_images_count' );

		if ( ! $user_images_count || get_site_transient( 'imagify_seen_rating_notice' ) ) {
			return $display;
		}

		$display = $user_images_count;
		return $display;
	}

	/**
	 * Tell if the 'wp-rocket' notice should be displayed.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function display_wp_rocket() {
		static $display;

		if ( isset( $display ) ) {
			return $display;
		}

		$display = false;

		if ( ! $this->user_can( 'wp-rocket' ) ) {
			return $display;
		}

		if ( ! imagify_is_screen( 'bulk' ) ) {
			return $display;
		}

		$plugins = get_plugins();

		if ( isset( $plugins['wp-rocket/wp-rocket.php'] ) || self::notice_is_dismissed( 'wp-rocket' ) ) {
			return $display;
		}

		$display = true;
		return $display;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TEMPORARY NOTICES ======================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Maybe display some notices.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function render_temporary_notices() {
		if ( is_network_admin() ) {
			$notices = $this->get_network_temporary_notices();
		} else {
			$notices = $this->get_site_temporary_notices();
		}

		if ( ! $notices ) {
			return;
		}

		$views = Imagify_Views::get_instance();

		foreach ( $notices as $i => $notice_data ) {
			$notices[ $i ]['type'] = ! empty( $notice_data['type'] ) ? $notice_data['type'] : 'error';
		}

		$views->print_template( 'notice-temporary', $notices );
	}

	/**
	 * Get temporary notices for the network.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_network_temporary_notices() {
		$notices = get_site_transient( self::TEMPORARY_NOTICES_TRANSIENT_NAME );

		if ( false === $notices ) {
			return array();
		}

		delete_site_transient( self::TEMPORARY_NOTICES_TRANSIENT_NAME );

		return $notices && is_array( $notices ) ? $notices : array();
	}

	/**
	 * Create a temporary notice for the network.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array|object|string $notice_data Some data, with the message to display.
	 */
	public function add_network_temporary_notice( $notice_data ) {
		$notices = get_site_transient( self::TEMPORARY_NOTICES_TRANSIENT_NAME );
		$notices = is_array( $notices ) ? $notices : array();

		if ( is_wp_error( $notice_data ) ) {
			$notice_data = $notice_data->get_error_messages();
			$notice_data = implode( '<br/>', $notice_data );
		}

		if ( is_string( $notice_data ) ) {
			$notice_data = array(
				'message' => $notice_data,
			);
		} elseif ( is_object( $notice_data ) ) {
			$notice_data = (array) $notice_data;
		}

		if ( ! is_array( $notice_data ) || empty( $notice_data['message'] ) ) {
			return;
		}

		$notices[] = $notice_data;

		set_site_transient( self::TEMPORARY_NOTICES_TRANSIENT_NAME, $notices, 30 );
	}

	/**
	 * Get temporary notices for the current site.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_site_temporary_notices() {
		$notices = get_transient( self::TEMPORARY_NOTICES_TRANSIENT_NAME );

		if ( false === $notices ) {
			return array();
		}

		delete_transient( self::TEMPORARY_NOTICES_TRANSIENT_NAME );

		return $notices && is_array( $notices ) ? $notices : array();
	}

	/**
	 * Create a temporary notice for the current site.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array|string $notice_data Some data, with the message to display.
	 */
	public function add_site_temporary_notice( $notice_data ) {
		$notices = get_transient( self::TEMPORARY_NOTICES_TRANSIENT_NAME );
		$notices = is_array( $notices ) ? $notices : array();

		if ( is_string( $notice_data ) ) {
			$notice_data = array(
				'message' => $notice_data,
			);
		} elseif ( is_object( $notice_data ) ) {
			$notice_data = (array) $notice_data;
		}

		if ( ! is_array( $notice_data ) || empty( $notice_data['message'] ) ) {
			return;
		}

		$notices[] = $notice_data;

		set_transient( self::TEMPORARY_NOTICES_TRANSIENT_NAME, $notices, 30 );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** PUBLIC TOOLS ============================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Renew a dismissed Imagify notice.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @param string $notice  A notice ID.
	 * @param int    $user_id A user ID.
	 */
	public static function renew_notice( $notice, $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		$notices = get_user_meta( $user_id, self::DISMISS_META_NAME, true );
		$notices = $notices && is_array( $notices ) ? array_flip( $notices ) : array();

		if ( ! isset( $notices[ $notice ] ) ) {
			return;
		}

		unset( $notices[ $notice ] );
		$notices = array_flip( $notices );
		$notices = array_filter( $notices );
		$notices = array_values( $notices );

		update_user_meta( $user_id, self::DISMISS_META_NAME, $notices );
	}

	/**
	 * Dismiss an Imagify notice.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 * @see    imagify_dismiss_notice()
	 *
	 * @param  string $notice  A notice ID.
	 * @param  int    $user_id A user ID.
	 */
	public static function dismiss_notice( $notice, $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		$notices = get_user_meta( $user_id, self::DISMISS_META_NAME, true );
		$notices = $notices && is_array( $notices ) ? array_flip( $notices ) : array();

		if ( isset( $notices[ $notice ] ) ) {
			return;
		}

		$notices   = array_flip( $notices );
		$notices[] = $notice;
		$notices   = array_filter( $notices );
		$notices   = array_values( $notices );

		update_user_meta( $user_id, self::DISMISS_META_NAME, $notices );
	}

	/**
	 * Tell if an Imagify notice is dismissed.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 * @see    imagify_notice_is_dismissed()
	 *
	 * @param  string $notice  A notice ID.
	 * @param  int    $user_id A user ID.
	 * @return bool
	 */
	public static function notice_is_dismissed( $notice, $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		$notices = get_user_meta( $user_id, self::DISMISS_META_NAME, true );
		$notices = $notices && is_array( $notices ) ? array_flip( $notices ) : array();

		return isset( $notices[ $notice ] );
	}

	/**
	 * Tell if one or more notices will be displayed later in the page.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function has_notices() {
		foreach ( self::$notice_ids as $notice_id ) {
			$callback = 'display_' . str_replace( '-', '_', $notice_id );

			if ( method_exists( $this, $callback ) && call_user_func( array( $this, $callback ) ) ) {
				return true;
			}
		}

		return false;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** INTERNAL TOOLS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get all notice IDs.
	 *
	 * @since  1.6.10
	 * @since  1.10 Cast return value to array.
	 * @author Grégory Viguier
	 *
	 * @return array The filtered notice ids.
	 */
	protected function get_notice_ids() {
		/**
		 * Filter the notices Imagify can display.
		 *
		 * @since  1.6.10
		 * @author Grégory Viguier
		 *
		 * @param array $notice_ids An array of notice "IDs".
		 */
		return (array) apply_filters( 'imagify_notices', self::$notice_ids );
	}

	/**
	 * Tell if the current user can see the notices.
	 * Notice IDs that are not listed in self::$capabilities are assumed as 'manage'.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @param  string $notice_id A notice ID.
	 * @return bool
	 */
	protected function user_can( $notice_id ) {
		$capability = isset( self::$capabilities[ $notice_id ] ) ? self::$capabilities[ $notice_id ] : 'manage';

		return imagify_get_context( 'wp' )->current_user_can( $capability );
	}

	/**
	 * Get a list of plugins that can conflict with Imagify.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	protected function get_conflicting_plugins() {
		/**
		 * Filter the recommended plugins to deactivate to prevent conflicts.
		 *
		 * @since 1.0
		 *
		 * @param string $plugins List of recommended plugins to deactivate.
		*/
		$plugins = apply_filters( 'imagify_plugins_to_deactivate', self::$conflicting_plugins );

		return array_filter( $plugins, 'is_plugin_active' );
	}
}
