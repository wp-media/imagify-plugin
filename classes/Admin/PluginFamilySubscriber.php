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
}
