<?php
namespace Imagify\Traits;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Trait that simulates a singleton pattern.
 * The idea is more to ease the instance retrieval than to prevent multiple instances.
 * This is temporary, until we get a DI container.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
trait FakeSingletonTrait {

	/**
	 * The "not-so-single" instance of the class.
	 *
	 * @var    object
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected static $_instance;

	/**
	 * Get the main Instance.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( static::$_instance ) ) {
			static::$_instance = new static();
		}

		return static::$_instance;
	}
}
