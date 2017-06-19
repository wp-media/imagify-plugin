<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify NextGen Gallery class.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
class Imagify_NGG {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.2';

	/**
	 * The single instance of the class.
	 *
	 * @access  protected
	 * @since   1.5
	 *
	 * @var object
	 */
	protected static $_instance;

	/**
	 * The constructor.
	 *
	 * @since  1.5
	 * @since  1.6.5 Doesn't launch the hooks anymore.
	 * @author Jonathan Buttigieg
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Launch the hooks.
	 *
	 * @since  1.6.5
	 * @author Grégory Viguier
	 */
	public function init() {
		static $done = false;

		if ( $done ) {
			return;
		}
		$done = true;

		add_action( 'init', array( $this, 'add_mixin' ) );
	}

	/**
	 * Get the main Instance.
	 *
	 * Ensures only one instance of class is loaded or can be loaded.
	 *
	 * @access  public
	 * @since   1.6.5
	 * @author  Grégory Viguier
	 *
	 * @return object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add custom NGG mixin to override its functions.
	 *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
	 * @return void
	 */
	function add_mixin() {
		include_once( 'class-imagify-ngg-storage.php' );
		C_Gallery_Storage::get_instance()->get_wrapped_instance()->add_mixin( 'Imagify_NGG_Storage' );
	}
}

/**
 * Returns the main instance of the Imagify_NGG class.
 *
 * @since 1.6.5
 * @author Grégory Viguier
 *
 * @return object The Imagify_NGG instance.
 */
function imagify_ngg() {
	return Imagify_NGG::get_instance();
}
