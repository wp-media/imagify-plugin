<?php
namespace Imagify\ThirdParty\NGG;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Imagify NextGen Gallery class.
 *
 * @since  1.5
 * @author Jonathan Buttigieg
 */
class Main {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.1';

	/**
	 * The single instance of the class.
	 *
	 * @since  1.5
	 * @access protected
	 *
	 * @var object
	 */
	protected static $_instance;

	/**
	 * The constructor.
	 *
	 * @since  1.5
	 * @since  1.6.5 Doesn't launch the hooks anymore.
	 * @access protected
	 * @author Jonathan Buttigieg
	 */
	protected function __construct() {}

	/**
	 * Launch the hooks.
	 *
	 * @since  1.6.5
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		static $done = false;

		if ( $done ) {
			return;
		}
		$done = true;

		add_filter( 'imagify_context_class_name', [ $this, 'add_context_class_name' ], 10, 2 );
		add_filter( 'imagify_process_class_name', [ $this, 'add_process_class_name' ], 10, 2 );
		add_action( 'init', [ $this, 'add_mixin' ] );
	}

	/**
	 * Get the main Instance.
	 *
	 * Ensures only one instance of class is loaded or can be loaded.
	 *
	 * @since  1.6.5
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

	/**
	 * Add custom NGG mixin to override its functions.
	 *
	 * @since  1.5
	 * @access public
	 * @author Jonathan Buttigieg
	 */
	public function add_mixin() {
		\C_Gallery_Storage::get_instance()->get_wrapped_instance()->add_mixin( '\\Imagify\\ThirdParty\\NGG\\NGGStorage' );
	}

	/**
	 * Filter the name of the class to use to define a context.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int    $class_name The class name.
	 * @param  string $context    The context name.
	 * @return string
	 */
	public function add_context_class_name( $class_name, $context ) {
		if ( 'ngg' === $context ) {
			return '\\Imagify\\ThirdParty\\NGG\\Context\\NGG';
		}

		return $class_name;
	}

	/**
	 * Filter the name of the class to use for the optimization.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int    $class_name The class name.
	 * @param  string $context    The context name.
	 * @return string
	 */
	public function add_process_class_name( $class_name, $context ) {
		if ( 'ngg' === $context ) {
			return '\\Imagify\\ThirdParty\\NGG\\Optimization\\Process\\NGG';
		}

		return $class_name;
	}
}
