<?php
/**
 * Fallback autoloading for Strauss-prefixed dependencies.
 *
 * Imagify's dependencies are namespace-prefixed into `Imagify\Dependencies\` (and
 * class-prefixed with `Imagify_` for global classes) by Strauss, which runs from
 * this package's own Composer install scripts. When Imagify is installed as a
 * Composer *dependency* of another project - Bedrock, for instance - Composer
 * does not run a dependency's scripts, so Strauss never runs and the prefixed
 * symbols are never generated. The unprefixed originals are present, because they
 * are declared in `require` and land in the root project's vendor directory.
 *
 * Rather than fail with "Class Imagify\Dependencies\League\Container\Container
 * not found", alias each prefixed symbol to its unprefixed original on demand.
 *
 * @package Imagify
 * @since   2.3.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * Global classes Strauss prefixes with `Imagify_`, mapped to their originals.
 *
 * This must stay an explicit allowlist. The `Imagify_` prefix is also used by
 * Imagify's own global classes (`Imagify_Settings`, `Imagify_WP_Retina_*`, ...),
 * and blindly stripping it would let an unrelated third-party class be aliased in
 * their place.
 *
 * @since 2.3.4
 *
 * @return array<string, string>
 */
function imagify_get_prefixed_global_classes() {
	return [
		'Imagify_WP_Async_Request'      => 'WP_Async_Request',
		'Imagify_WP_Background_Process' => 'WP_Background_Process',
	];
}

/**
 * Resolve a prefixed class name back to the original it was generated from.
 *
 * @since 2.3.4
 *
 * @param  string $class_name The class name being autoloaded.
 * @return string             The unprefixed original, or '' when the name is not
 *                            one Strauss would have produced.
 */
function imagify_unprefix_dependency_class( $class_name ) {
	$namespace_prefix = 'Imagify\\Dependencies\\';

	/**
	 * The `Imagify\Dependencies\` namespace is exclusively Strauss output, so
	 * anything under it can be mapped generically. That keeps this working for
	 * dependencies added later without touching this file.
	 */
	if ( 0 === strpos( $class_name, $namespace_prefix ) ) {
		return substr( $class_name, strlen( $namespace_prefix ) );
	}

	$global_classes = imagify_get_prefixed_global_classes();

	return isset( $global_classes[ $class_name ] ) ? $global_classes[ $class_name ] : '';
}

/**
 * Register the fallback autoloader.
 *
 * Registered last, so Composer's own autoloader always wins. On a normal install
 * the prefixed classes resolve there and this closure is never reached.
 *
 * @since 2.3.4
 *
 * @return void
 */
function imagify_register_dependencies_fallback_autoloader() {
	spl_autoload_register(
		function ( $class_name ) {
			$original = imagify_unprefix_dependency_class( $class_name );

			if ( '' === $original || $original === $class_name ) {
				return;
			}

			/**
			 * Only alias when the original genuinely exists. Passing true here lets
			 * other registered autoloaders resolve it; this autoloader itself bails
			 * out on unprefixed names, so there is no recursion.
			 */
			if (
				! class_exists( $original, true )
				&& ! interface_exists( $original, true )
				&& ! trait_exists( $original, true )
			) {
				return;
			}

			class_alias( $original, $class_name );
		}
	);
}
