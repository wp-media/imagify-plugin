<?php
declare(strict_types=1);

namespace Imagify\Media;

use Imagify\EventManagement\SubscriberInterface;
use Imagify\Media\Upload\Upload;

/**
 * Media Subscriber
 */
class Subscriber implements SubscriberInterface {

	/**
	 * Upload instance.
	 *
	 * @var Upload
	 */
	private $upload;
	/**
	 * Constructor
	 *
	 * @param Upload $upload Upload Instance.
	 */
	public function __construct( Upload $upload ) {
		$this->upload = $upload;
	}

	/**
	 * Returns an array of events that this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'restrict_manage_posts' => 'imagify_attachments_filter_dropdown',
		];
	}

	/**
	 * Adds a dropdown that allows filtering on the attachments Imagify status.
	 *
	 * @return void
	 */
	public function imagify_attachments_filter_dropdown() {
		if ( ! \Imagify_Views::get_instance()->is_wp_library_page() ) {
			return;
		}
		$this->upload->add_imagify_filter_to_attachments_dropdown();
	}
}
