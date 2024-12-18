<?php
declare( strict_types=1 );

namespace Imagify\Admin\Stats;

use Imagify\EventManagement\SubscriberInterface;

/**
 * Stats Subscriber.
 */
class Subscriber implements SubscriberInterface {

    /**
	 * Controller instance.
	 *
	 * @var Controller
	 */
    protected $controller;

    /**
	 * Instantiate the class
	 *
	 * @param Controller $controller Controller instance.
	 */
    public function __construct( Controller $controller ) {
        $this->controller = $controller;
    }

	/**
	 * Returns an array of events this subscriber listens to
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
        $events = [];

        $events['init'] = 'register_actions';
        $events['admin_init'] = 'register_stats_option';
        $events['imagify_deactivation'] = 'unregister_actions';

        foreach ( Controller::get_actions() as $action ) {
            $events[ $action ] = str_replace( '_as', '', $action );
        }

        return $events;
	}

     /**
     * Register actions to run in background.
     *
     * @return void
     */
    public function register_actions(): void {
        $this->controller->register_actions();
    }

    /**
     * Cancel all occurrence of scheduled action.
     *
     * @return void
     */
    public function unregister_actions(): void {
        $this->controller->unregister_actions();
    }

    /**
     * Register admin stat options.
     *
     * @return void
     */
    public function register_stats_option(): void {
        $this->controller->register_stats_option();
    }

    /**
     * Count number of attachments.
     *
     * @return void
     */
    public function imagify_count_attachments(): void {
        $this->controller->imagify_count_attachments();
    }

     /**
     * Count number of optimized attachments with an error.
     *
     * @return void
     */
    public function imagify_count_error_attachments(): void {
        $this->controller->imagify_count_error_attachments();
    }

    /**
     * Count number of optimized attachments (by Imagify or an other tool before).
     *
     * @return void
     */
    public function imagify_count_optimized_attachments(): void {
        $this->controller->imagify_count_optimized_attachments();
    }

     /**
     * Count percent, original & optimized size of all images optimized by Imagify.
     *
     * @return void
     */
    public function imagify_count_saving_data(): void {
        $this->controller->imagify_count_saving_data();
    }

     /**
     * Gets the estimated total size of the images not optimized.
     *
     * @return void
     */
    public function imagify_calculate_total_size_images_library(): void {
        $this->controller->imagify_calculate_total_size_images_library();
    }

     /**
     * Gets the estimated average size of the images uploaded per month.
     *
     * @return void
     */
    public function imagify_calculate_average_size_images_per_month(): void {
        $this->controller->imagify_calculate_average_size_images_per_month();
    }
}
