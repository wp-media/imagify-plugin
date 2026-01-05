<?php
namespace Imagify\EventManagement;

interface EventManagerAwareSubscriberInterface extends SubscriberInterface {
	/**
	 * Set the WordPress event manager for the subscriber.
	 *
	 * @since 3.1
	 * @author Remy Perona
	 *
	 * @param EventManager $event_manager EventManager instance.
	 */
	public function set_event_manager( EventManager $event_manager );
}
