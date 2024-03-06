<?php
declare(strict_types=1);

namespace Imagify;

use Imagify\Admin\AdminBar;
use Imagify\Bulk\Bulk;
use Imagify\CLI\{BulkOptimizeCommand, GenerateMissingNextgenCommand};
use Imagify\Dependencies\League\Container\Container;
use Imagify\Dependencies\League\Container\ServiceProvider\ServiceProviderInterface;
use Imagify\EventManagement\{EventManager, SubscriberInterface};
use Imagify\Notices\Notices;
use Imagify_Filesystem;

/**
 * Main plugin class.
 */
class Plugin {
	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Is the plugin loaded
	 *
	 * @var boolean
	 */
	private $loaded = false;

	/**
	 * Absolute path to the plugin (with trailing slash).
	 *
	 * @var string
	 */
	private $plugin_path;

	/**
	 * Instantiate the class.
	 *
	 * @since 1.9
	 *
	 * @param Container $container Instance of the container.
	 * @param array     $plugin_args {
	 *     An array of arguments.
	 *
	 *     @type string $plugin_path Absolute path to the plugin (with trailing slash).
	 * }
	 */
	public function __construct( Container $container, $plugin_args ) {
		$this->container   = $container;
		$this->plugin_path = $plugin_args['plugin_path'];

		add_filter( 'imagify_container', [ $this, 'get_container' ] );
	}

	/**
	 * Returns the container instance.
	 *
	 * @return Container
	 */
	public function get_container() {
		return $this->container;
	}

	/**
	 * Checks if the plugin is loaded
	 *
	 * @return boolean
	 */
	private function is_loaded(): bool {
		return $this->loaded;
	}

	/**
	 * Plugin init.
	 *
	 * @param array $providers Array of service providers.
	 *
	 * @since 1.9
	 */
	public function init( $providers ) {
		if ( $this->is_loaded() ) {
			return;
		}

		$this->container->share(
			'event_manager',
			function () {
				return new EventManager();
			}
		);

		$this->container->share(
			'filesystem',
			function() {
				return new Imagify_Filesystem();
			}
		);

		$this->include_files();

		class_alias( '\\Imagify\\Traits\\InstanceGetterTrait', '\\Imagify\\Traits\\FakeSingletonTrait' );

		\Imagify_Auto_Optimization::get_instance()->init();
		\Imagify_Options::get_instance()->init();
		\Imagify_Data::get_instance()->init();
		\Imagify_Folders_DB::get_instance()->init();
		\Imagify_Files_DB::get_instance()->init();
		\Imagify_Cron_Library_Size::get_instance()->init();
		\Imagify_Cron_Rating::get_instance()->init();
		\Imagify_Cron_Sync_Files::get_instance()->init();
		\Imagify\Auth\Basic::get_instance()->init();
		\Imagify\Job\MediaOptimization::get_instance()->init();
		Bulk::get_instance()->init();
		AdminBar::get_instance()->init();

		if ( is_admin() ) {
			Notices::get_instance()->init();
			\Imagify_Admin_Ajax_Post::get_instance()->init();
			\Imagify_Settings::get_instance()->init();
			\Imagify_Views::get_instance()->init();
			\Imagify\Imagifybeat\Core::get_instance()->init();
			\Imagify\Imagifybeat\Actions::get_instance()->init();
		}

		if ( ! wp_doing_ajax() ) {
			\Imagify_Assets::get_instance()->init();
		}

		add_action( 'init', [ $this, 'maybe_activate' ] );

		// Load plugin translations.
		imagify_load_translations();

		imagify_add_command( new BulkOptimizeCommand() );
		imagify_add_command( new GenerateMissingNextgenCommand() );

		foreach ( $providers as $service_provider ) {
			$provider_instance = new $service_provider();
			$this->container->addServiceProvider( $provider_instance );

			// Load each service provider's subscribers if found.
			$this->load_subscribers( $provider_instance );
		}

		/**
		 * Fires when Imagify is fully loaded.
		 *
		 * @since 1.0
		 * @since 1.9 Added the class instance as parameter.
		 *
		 * @param \Imagify_Plugin $plugin Instance of this class.
		 */
		do_action( 'imagify_loaded', $this );

		$this->loaded = true;
	}

	/**
	 * Include plugin files.
	 *
	 * @since 1.9
	 */
	public function include_files() {
		$instance_getter_path = $this->plugin_path . 'classes/Traits/InstanceGetterTrait.php';

		if ( file_exists( $instance_getter_path . '.suspected' ) && ! file_exists( $instance_getter_path ) ) {
			// Trolling greedy antiviruses.
			require_once $instance_getter_path . '.suspected';
		}

		$inc_path = $this->plugin_path . 'inc/';

		require_once $inc_path . '/Dependencies/ActionScheduler/action-scheduler.php';
		require_once $inc_path . 'deprecated/deprecated.php';
		require_once $inc_path . 'deprecated/3rd-party.php';
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
	 * @since 1.9
	 * @see   imagify_set_activation()
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
		 * @since 1.9
		 *
		 * @param int $user_id ID of the user activating the plugin.
		 */
		do_action( 'imagify_activation', (int) $user_id );
	}

	/**
	 * Load list of event subscribers from service provider.
	 *
	 * @param ServiceProviderInterface $service_provider Instance of service provider.
	 *
	 * @return void
	 */
	private function load_subscribers( ServiceProviderInterface $service_provider ) {
		if ( empty( $service_provider->get_subscribers() ) ) {
			return;
		}

		foreach ( $service_provider->get_subscribers() as $subscriber ) {
			$subscriber_object = $this->container->get( $subscriber );

			if ( $subscriber_object instanceof SubscriberInterface ) {
				$this->container->get( 'event_manager' )->add_subscriber( $subscriber_object );
			}
		}
	}
}
