<?php

namespace Imagify\Tests\Unit\Functions;

use Imagify\Tests\Unit\TestCase;

/**
 * @group Functions
 * @group Composer
 */
class Test_ImagifyRegisterDependenciesFallbackAutoloader extends TestCase {
	/**
	 * Load the fallback functions, which are required at runtime rather than autoloaded.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'imagify_register_dependencies_fallback_autoloader' ) ) {
			require_once IMAGIFY_PLUGIN_ROOT . 'inc/functions/dependencies.php';
		}

		// Stand-ins for the unprefixed Composer dependencies.
		require_once IMAGIFY_PLUGIN_ROOT . 'Tests/Fixtures/inc/functions/dependenciesFallback.php';

		static $registered = false;

		if ( ! $registered ) {
			imagify_register_dependencies_fallback_autoloader();
			$registered = true;
		}
	}

	/**
	 * A prefixed name resolves to the existing unprefixed class.
	 *
	 * This is what actually breaks on a Composer dependency install: the code says
	 * `Imagify\Dependencies\Foo` while only `Foo` exists on disk.
	 */
	public function testShouldAliasAPrefixedClassToTheExistingOriginal() {
		$this->assertTrue(
			class_exists( 'Imagify\Dependencies\Imagify_Test_Fallback_Dep' ),
			'The prefixed name should resolve to the unprefixed class.'
		);

		$this->assertSame(
			'fallback-ok',
			( new \Imagify\Dependencies\Imagify_Test_Fallback_Dep() )->value(),
			'The alias should point at the real implementation.'
		);
	}

	/**
	 * Interfaces are aliased too - AbstractServiceProvider's contract is one.
	 */
	public function testShouldAliasAPrefixedInterface() {
		$this->assertTrue( interface_exists( 'Imagify\Dependencies\Imagify_Test_Fallback_Contract' ) );
	}

	/**
	 * A prefixed name with no original must stay unresolved rather than blow up.
	 */
	public function testShouldNotResolveAPrefixedClassWithNoOriginal() {
		$this->assertFalse( class_exists( 'Imagify\Dependencies\Totally\Absent\Klass' ) );
	}

	/**
	 * Imagify's own global classes must not be aliased to a same-named third-party
	 * class. `Imagify_Test_Foreign_Victim` exists unprefixed here, so a greedy
	 * `Imagify_` strip would wrongly bind to it.
	 */
	public function testShouldNotAliasImagifyOwnGlobalClasses() {
		$this->assertFalse(
			class_exists( 'Imagify_Test_Foreign_Victim' ),
			'A non-allowlisted Imagify_ class must not be aliased.'
		);
	}
}
