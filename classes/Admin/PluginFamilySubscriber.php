<?php
declare(strict_types=1);

namespace Imagify\Admin;

use Imagify\EventManagement\SubscriberInterface;
use Imagify\Dependencies\WPMedia\PluginFamily\Controller\{ PluginFamily, PluginFamilyInterface };

/**
 * Process plugin family actions.
 */
class PluginFamilySubscriber implements SubscriberInterface, PluginFamilyInterface {

	/**
	 * PluginFamily instance.
	 *
	 * @var PluginFamily
	 */
	protected $plugin_family;

	/**
	 * Instantiate the class
	 *
	 * @param PluginFamily $plugin_family PluginFamily instance.
	 */
	public function __construct( PluginFamily $plugin_family ) {
		$this->plugin_family = $plugin_family;
	}

	/**
	 * Returns an array of events this subscriber listens to
	 *
	 * @return array
	 */
	public static function get_subscribed_events() {
		$events = PluginFamily::get_subscribed_events();

		return $events;
	}

	/**
	 * Process to install & activate plugin.
	 *
	 * @return void
	 */
	public function install_activate() {
		$this->plugin_family->install_activate();
	}

	/**
	 * Display error notice if available.
	 *
	 * @return void
	 */
	public function display_error_notice() {
		$this->plugin_family->display_error_notice();
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$this->plugin_family->enqueue_assets();
	}

	/**
	 * Install Imagify using the ajax request.
	 *
	 * @return void
	 */
	public function install_imagify(): void {
		$this->plugin_family->install_imagify();
	}

	/**
	 * Enqueue Admin assets.
	 *
	 * @param string $page Page ID.
	 * @return void
	 */
	public function enqueue_admin_assets( $page ): void {
		$this->plugin_family->enqueue_admin_assets( $page );
	}

	/**
	 * Insert admin footer JS templates.
	 *
	 * @return void
	 */
	public function insert_footer_templates(): void {
		$this->plugin_family->insert_footer_templates();
	}

	/**
	 * Dismiss promote Imagify using the ajax request.
	 *
	 * @return void
	 */
	public function dismiss_promote_imagify(): void {
		$this->plugin_family->dismiss_promote_imagify();
	}
}
