<?php
/**
 * Stand-ins for unprefixed Composer dependencies.
 *
 * These stand in for unprefixed Composer dependencies: the fallback autoloader
 * should alias `Imagify\Dependencies\<name>` onto them.
 *
 * @package Imagify\Tests
 */

if ( ! interface_exists( 'Imagify_Test_Fallback_Contract' ) ) {
	interface Imagify_Test_Fallback_Contract {}
}

if ( ! class_exists( 'Imagify_Test_Fallback_Dep' ) ) {
	class Imagify_Test_Fallback_Dep {
	/**
	 * Marker so the test can prove the alias points at this implementation.
	 *
	 * @return string
	 */
		public function value() {
			return 'fallback-ok';
		}
	}
}

/**
 * Stands in for a third-party class that a greedy `Imagify_` strip could wrongly
 * bind one of Imagify's own global class names onto.
 */
if ( ! class_exists( 'Test_Foreign_Victim' ) ) {
	class Test_Foreign_Victim {}
}
