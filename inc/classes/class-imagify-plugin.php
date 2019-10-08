<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Main plugin class.
 */
class Imagify_Plugin {
	/**
	 * Absolute path to the plugin (with trailing slash).
	 *
	 * @var    string
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
	 */
	private $plugin_path;

	/**
	 * Require files.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $plugin_args {
	 *     An array of arguments.
	 *
	 *     @type string $plugin_path Absolute path to the plugin (with trailing slash).
	 * }
	 */
	public function __construct( $plugin_args ) {
		$this->plugin_path = $plugin_args['plugin_path'];
	}

	/**
	 * Plugin init.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		$this->include_files();

		class_alias( '\\Imagify\\Traits\\InstanceGetterTrait', '\\Imagify\\Traits\\FakeSingletonTrait' );

		Imagify_Auto_Optimization::get_instance()->init();
		Imagify_Options::get_instance()->init();
		Imagify_Data::get_instance()->init();
		Imagify_Folders_DB::get_instance()->init();
		Imagify_Files_DB::get_instance()->init();
		Imagify_Cron_Library_Size::get_instance()->init();
		Imagify_Cron_Rating::get_instance()->init();
		Imagify_Cron_Sync_Files::get_instance()->init();
		\Imagify\Auth\Basic::get_instance()->init();
		\Imagify\Job\MediaOptimization::get_instance()->init();
		\Imagify\Stats\OptimizedMediaWithoutWebp::get_instance()->init();

		if ( is_admin() ) {
			Imagify_Notices::get_instance()->init();
			Imagify_Admin_Ajax_Post::get_instance()->init();
			Imagify_Settings::get_instance()->init();
			Imagify_Views::get_instance()->init();
			\Imagify\Imagifybeat\Core::get_instance()->init();
			\Imagify\Imagifybeat\Actions::get_instance()->init();
		}

		if ( ! wp_doing_ajax() ) {
			Imagify_Assets::get_instance()->init();
		}

		\Imagify\Webp\Display::get_instance()->init();

		add_action( 'init', [ $this, 'maybe_activate' ] );

		// Load plugin translations.
		imagify_load_translations();

		/**
		 * Fires when Imagify is fully loaded.
		 *
		 * @since 1.0
		 * @since 1.9 Added the class instance as parameter.
		 *
		 * @param \Imagify_Plugin $plugin Instance of this class.
		 */
		do_action( 'imagify_loaded', $this );
	}

	/**
	 * Include plugin files.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function include_files() {
		if ( file_exists( $this->plugin_path . 'vendor/autoload.php' ) ) {
			require_once $this->plugin_path . 'vendor/autoload.php';
		}

		$instance_getter_path = $this->plugin_path . 'classes/Traits/InstanceGetterTrait.php';

		if ( file_exists( $instance_getter_path . '.suspected' ) && ! file_exists( $instance_getter_path ) ) {
			// Trolling greedy antiviruses.
			require_once $instance_getter_path . '.suspected';
		}

		$inc_path = $this->plugin_path . 'inc/';

		require_once $inc_path . 'deprecated/deprecated.php';
		require_once $inc_path . 'deprecated/3rd-party.php';
		require_once $inc_path . 'functions/compat.php';
		require_once $inc_path . 'functions/common.php';
		require_once $inc_path . 'functions/options.php';
		require_once $inc_path . 'functions/formatting.php';
		require_once $inc_path . 'functions/admin.php';
		require_once $inc_path . 'functions/api.php';
		require_once $inc_path . 'functions/media.php';
		require_once $inc_path . 'functions/attachments.php';
		require_once $inc_path . 'functions/process.php';
		require_once $inc_path . 'functions/admin-ui.php';
		require_once $inc_path . 'functions/admin-stats.php';
		require_once $inc_path . 'functions/i18n.php';
		require_once $inc_path . 'functions/partners.php';
		require_once $inc_path . 'common/attachments.php';
		require_once $inc_path . 'common/admin-bar.php';
		require_once $inc_path . 'common/partners.php';
		require_once $inc_path . '3rd-party/3rd-party.php';

		if ( ! is_admin() ) {
			return;
		}

		require_once $inc_path . 'admin/upgrader.php';
		require_once $inc_path . 'admin/upload.php';
		require_once $inc_path . 'admin/media.php';
		require_once $inc_path . 'admin/meta-boxes.php';
		require_once $inc_path . 'admin/custom-folders.php';
	}

	/**
	 * Trigger a hook on plugin activation after the plugin is loaded.
	 *
	 * @since  1.9
	 * @access public
	 * @see    imagify_set_activation()
	 * @author Grégory Viguier
	 */
	public function maybe_activate() {
		if ( imagify_is_active_for_network() ) {
			$user_id = get_site_transient( 'imagify_activation' );
		} else {
			$user_id = get_transient( 'imagify_activation' );
		}

		if ( ! is_numeric( $user_id ) ) {
			return;
		}

		if ( imagify_is_active_for_network() ) {
			delete_site_transient( 'imagify_activation' );
		} else {
			delete_transient( 'imagify_activation' );
		}

		/**
		 * Imagify activation.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param int $user_id ID of the user activating the plugin.
		 */
		do_action( 'imagify_activation', (int) $user_id );
	}
}
