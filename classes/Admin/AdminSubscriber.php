<?php
declare(strict_types=1);

namespace Imagify\Admin;

use Imagify\EventManagement\SubscriberInterface;
use Imagify\User\User;

/**
 * Admin Subscriber
 */
class AdminSubscriber implements SubscriberInterface {

	/**
	 * User instance.
	 *
	 * @var User
	 */
	protected $user;

	/**
	 * Instantiate the class
	 *
	 * @param User $user User instance.
	 */
	public function __construct( User $user ) {
		$this->user = $user;
	}

	/**
	 * Returns an array of events this subscriber listens to
	 *
	 * @return array
	 */
	public static function get_subscribed_events() {
		$basename = plugin_basename( IMAGIFY_FILE );

		return [
			'plugin_action_links_' . $basename => 'plugin_action_links',
			'network_admin_plugin_action_links_' . $basename => 'plugin_action_links',
		];
	}

	/**
	 * Add links to the plugin row in the plugins list.
	 *
	 * @since 1.7
	 *
	 * @param  array $actions An array of action links.
	 * @return array
	 */
	public function plugin_action_links( $actions ) {
		$text  = 1 !== $this->user->get_plan_id() ? __( 'Documentation', 'imagify' ) : __( 'Upgrade', 'imagify' );
		$url   = 1 !== $this->user->get_plan_id() ? 'documentation' : 'subscription';
		$class = 1 !== $this->user->get_plan_id() ? '' : ' class="imagify-plugin-upgrade"';

		array_unshift(
			$actions,
			sprintf(
				'<a href="%s" target="_blank"%s>%s</a>',
				esc_url( imagify_get_external_url( $url ) ),
				$class,
				$text
			)
		);

		array_unshift(
			$actions,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_imagify_admin_url( 'bulk-optimization' ) ),
				__( 'Bulk Optimization', 'imagify' )
			)
		);

		array_unshift(
			$actions,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_imagify_admin_url() ),
				__( 'Settings', 'imagify' )
			)
		);

		return $actions;
	}
}
