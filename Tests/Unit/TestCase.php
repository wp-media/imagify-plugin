<?php
/**
 * Test Case for all of the unit tests.
 *
 * @package Imagify\Tests\Unit
 */

namespace Imagify\Tests\Unit;

use ReflectionObject;
use WPMedia\PHPUnit\Unit\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase {
	protected $config;

	protected function setUp() : void {
		if ( empty( $this->config ) ) {
			$this->loadTestDataConfig();
		}

		parent::setUp();
	}

	public function configTestData() {
		if ( empty( $this->config ) ) {
			$this->loadTestDataConfig();
		}

		return isset( $this->config['test_data'] )
			? $this->config['test_data']
			: $this->config;
	}

	protected function loadTestDataConfig() {
		$obj      = new ReflectionObject( $this );
		$filename = $obj->getFileName();

		$this->config = $this->getTestData( dirname( $filename ), basename( $filename, '.php' ) );
	}

	/**
	 * Reset the value of a private/protected property.
	 *
	 * @param string        $property Property name for which to gain access.
	 * @param string|object $class    Class name for a static property, or instance for an instance property.
	 *
	 * @return mixed                   The previous value of the property.
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 */
	protected function resetPropertyValue( $property, $class ) {
		return $this->setPropertyValue( $property, $class, null );
	}

	/**
	 * Set the value of a private/protected property.
	 *
	 * @param string        $property Property name for which to gain access.
	 * @param string|object $class    Class name for a static property, or instance for an instance property.
	 * @param mixed         $value    The value to set to the property.
	 *
	 * @return mixed                   The previous value of the property.
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 */
	protected function setPropertyValue( $property, $class, $value ) {
		$ref = $this->get_reflective_property( $property, $class );

		if ( is_object( $class ) ) {
			$previous = $ref->getValue( $class );
			// Instance property.
			$ref->setValue( $class, $value );
		} else {
			$previous = $ref->getValue();
			// Static property.
			$ref->setValue( $value );
		}

		return $previous;
	}
}
