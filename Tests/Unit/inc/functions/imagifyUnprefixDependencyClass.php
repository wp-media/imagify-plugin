<?php

namespace Imagify\Tests\Unit\Functions;

use Imagify\Tests\Unit\TestCase;

/**
 * @group Functions
 * @group Composer
 */
class Test_ImagifyUnprefixDependencyClass extends TestCase {
	/**
	 * Load the fallback functions, which are required at runtime rather than autoloaded.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'imagify_unprefix_dependency_class' ) ) {
			require_once IMAGIFY_PLUGIN_ROOT . 'inc/functions/dependencies.php';
		}
	}

	/**
	 * Anything under the Imagify\Dependencies\ namespace maps generically, so
	 * dependencies added to the Strauss config later keep working untouched.
	 */
	public function testShouldMapAnyPrefixedNamespaceToItsOriginal() {
		$cases = [
			'Imagify\Dependencies\League\Container\Container' => 'League\Container\Container',
			'Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider' => 'League\Container\ServiceProvider\AbstractServiceProvider',
			'Imagify\Dependencies\WPMedia\PluginFamily\Controller\PluginFamily' => 'WPMedia\PluginFamily\Controller\PluginFamily',
			'Imagify\Dependencies\WPMedia\Mixpanel\Optin' => 'WPMedia\Mixpanel\Optin',
			'Imagify\Dependencies\Something\Added\Later'  => 'Something\Added\Later',
		];

		foreach ( $cases as $prefixed => $expected ) {
			$this->assertSame( $expected, imagify_unprefix_dependency_class( $prefixed ) );
		}
	}

	/**
	 * Global classes come from an explicit allowlist.
	 */
	public function testShouldMapAllowlistedGlobalClasses() {
		$this->assertSame( 'WP_Background_Process', imagify_unprefix_dependency_class( 'Imagify_WP_Background_Process' ) );
		$this->assertSame( 'WP_Async_Request', imagify_unprefix_dependency_class( 'Imagify_WP_Async_Request' ) );
	}

	/**
	 * The important negative case.
	 *
	 * Imagify uses the `Imagify_` prefix for its own global classes too. Stripping
	 * it blindly would let an unrelated third-party class be aliased in their
	 * place - `Imagify_WP_Retina_2x` must never resolve to some other plugin's
	 * `WP_Retina_2x`.
	 */
	public function testShouldNotTouchImagifyOwnGlobalClasses() {
		$own = [
			'Imagify_Settings',
			'Imagify_Views',
			'Imagify_Options',
			'Imagify_WP_Retina_2x',
			'Imagify_WP_Time_Capsule',
			'Imagify_Abstract_Background_Process',
		];

		foreach ( $own as $class_name ) {
			$this->assertSame( '', imagify_unprefix_dependency_class( $class_name ), $class_name . ' must not be unprefixed.' );
		}
	}

	/**
	 * Unrelated names are ignored.
	 */
	public function testShouldIgnoreUnrelatedClassNames() {
		foreach ( [ 'League\Container\Container', 'WP_Background_Process', 'stdClass', '' ] as $class_name ) {
			$this->assertSame( '', imagify_unprefix_dependency_class( $class_name ) );
		}
	}
}
