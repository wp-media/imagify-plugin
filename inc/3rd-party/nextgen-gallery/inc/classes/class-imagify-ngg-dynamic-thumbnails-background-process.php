<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles background processing of thumbnails dynamically generated.
 *
 * @since  1.8
 * @author Grégory Viguier
 */
class Imagify_NGG_Dynamic_Thumbnails_Background_Process extends Imagify_Abstract_Background_Process {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.8
	 * @author Grégory Viguier
	 */
	const VERSION = '1.1';

	/**
	 * Action.
	 *
	 * @var    string
	 * @since  1.8
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $action = 'ngg_dynamic_thumbnails';

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.8
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected static $_instance;


	/**
	 * Get the main Instance.
	 *
	 * @since  1.8
	 * @access public
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


	/** ----------------------------------------------------------------------------------------- */
	/** BACKGROUND PROCESS ====================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Push to queue.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $data {
	 *     The data to push in queue.
	 *
	 *     @type int    $id    The image ID. Required.
	 *     @type string $size  The thumbnail size. Required.
	 * }
	 * @return object Class instance.
	 */
	public function push_to_queue( $data ) {
		$key = $data['id'] . '|' . $data['size'];

		$this->data[ $key ] = $data;

		return $this;
	}

	/**
	 * Dispatch.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array|WP_Error
	 */
	public function dispatch() {
		if ( ! empty( $this->data ) ) {
			return parent::dispatch();
		}
	}

	/**
	 * Tell if a task is already in the queue.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $data {
	 *     The data to test against the queue.
	 *
	 *     @type int    $id    The image ID. Required.
	 *     @type string $size  The thumbnail size. Required.
	 * }
	 * @return bool
	 */
	public function is_in_queue( $data ) {
		$key = $data['id'] . '|' . $data['size'];

		return isset( $this->data[ $key ] );
	}

	/**
	 * Task: optimize the thumbnail.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $item {
	 *     The data to test against the queue.
	 *
	 *     @type int    $id    The image ID. Required.
	 *     @type string $size  The thumbnail size. Required.
	 * }
	 * @return bool False to remove the item from the queue.
	 */
	protected function task( $item ) {
		$attachment_id = absint( $item['id'] );
		$size          = sanitize_text_field( $item['size'] );

		if ( ! $attachment_id || ! $size ) {
			return false;
		}

		$attachment = get_imagify_attachment( 'NGG', $attachment_id, 'ngg_optimize_dynamic_thumbnail' );

		$attachment->optimize_new_thumbnail( $size );

		return false;
	}
}
