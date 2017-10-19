<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify NextGen Gallery class.
 *
 * @since  1.5
 * @author Jonathan Buttigieg
 */
class Imagify_NGG {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.3';

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
	 * @author Jonathan Buttigieg
	 * @access protected
	 */
	protected function __construct() {}

	/**
	 * Launch the hooks.
	 *
	 * @since  1.6.5
	 * @author Grégory Viguier
	 * @access public
	 */
	public function init() {
		static $done = false;

		if ( $done ) {
			return;
		}
		$done = true;

		add_action( 'init', array( $this, 'add_mixin' ) );
		add_filter( 'do_imagify_args', array($this, 'adjust_optimize_args'));
	}

	/**
	 * Get the main Instance.
	 *
	 * Ensures only one instance of class is loaded or can be loaded.
	 *
	 * @since  1.6.5
	 * @author Grégory Viguier
	 * @access public
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
	 * @author Jonathan Buttigieg
	 * @access public
	 */
	public function add_mixin() {
		C_Gallery_Storage::get_instance()->get_wrapped_instance()->add_mixin( 'Imagify_NGG_Storage' );
	}


	/**
	 * Don't strip EXIF meta from NGG images, or resize them.
	 *
	 * @param $args
	 * @param $file_path
	 *
	 * @return array
	 */
	function adjust_optimize_args($args)
	{
		if ($args['context'] == 'NGG') $args['keep_exif'] = 1;

		return $args;
	}
}
