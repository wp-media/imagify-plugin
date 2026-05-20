<?php
namespace Imagify\ThirdParty\NGG;

use Imagify\Traits\InstanceGetterTrait;

/**
 * Imagify NextGen Gallery class.
 *
 * @since  1.5
 * @author Jonathan Buttigieg
 */
class Main {
	use InstanceGetterTrait;

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.1';

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

		add_filter( 'imagify_register_context', [ $this, 'register_context' ] );
		add_filter( 'imagify_context_class_name', [ $this, 'add_context_class_name' ], 10, 2 );
		add_filter( 'imagify_process_class_name', [ $this, 'add_process_class_name' ], 10, 2 );
		add_filter( 'imagify_bulk_class_name', [ $this, 'add_bulk_class_name' ], 10, 2 );
		add_action( 'init', [ $this, 'add_mixin' ] );
	}

	/**
	 * Register the context used for NGG.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $contexts An array of context names.
	 * @return array
	 */
	public function register_context( $contexts ) {
		$contexts[] = 'ngg';
		return $contexts;
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

	/**
	 * Filter the name of the class to use for the bulk optimization.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int    $class_name The class name.
	 * @param  string $context    The context name.
	 * @return string
	 */
	public function add_bulk_class_name( $class_name, $context ) {
		if ( 'ngg' === $context ) {
			return '\\Imagify\\ThirdParty\\NGG\\Bulk\\NGG';
		}

		return $class_name;
	}

	/**
	 * Add custom NGG mixin to override its functions.
	 *
	 * The POPE mixin API (C_Gallery_Storage + Mixin) was removed in NextGEN Gallery v4.x.
	 * Guard both class checks to avoid a fatal error on v4.x installs while preserving
	 * the behaviour on v3.x.
	 *
	 * @since  1.5
	 * @since  2.2.8 Skip silently when the mixin API is unavailable (NGG v4.x+).
	 * @access public
	 * @author Jonathan Buttigieg
	 */
	public function add_mixin() {
		if ( ! class_exists( 'Mixin' ) || ! class_exists( 'C_Gallery_Storage' ) ) {
			return;
		}

		\C_Gallery_Storage::get_instance()->get_wrapped_instance()->add_mixin( '\\Imagify\\ThirdParty\\NGG\\NGGStorage' );
	}
}
