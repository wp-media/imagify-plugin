<?php
/**
 * Common functionality for all PHP test cases.
 *
 * @package Imagify\tests
 */

namespace Imagify\tests;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

trait TestCaseTrait {

	/**
	 * Set the singleton's `$instance` property to the given instance.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  string $class    Name of the target class.
	 * @param  mixed  $instance Instance of the target object.
	 * @return mixed            Previous value.
	 */
	protected function setSingletonInstance( $class, $instance ) {
		return $this->setPropertyValue( 'instance', $class, $instance );
	}

	/**
	 * Reset the value of a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  string        $property Property name for which to gain access.
	 * @param  string|object $class    Class name for a static property, or instance for an instance property.
	 * @return mixed                   The previous value of the property.
	 */
	protected function resetPropertyValue( $property, $class ) {
		return $this->setPropertyValue( $property, $class, null );
	}

	/**
	 * Set the value of a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  string        $property Property name for which to gain access.
	 * @param  string|object $class    Class name for a static property, or instance for an instance property.
	 * @param  mixed         $value    The value to set to the property.
	 * @return mixed                   The previous value of the property.
	 */
	protected function setPropertyValue( $property, $class, $value ) {
		$ref      = $this->get_reflective_property( $property, $class );
		$previous = $ref->getValue();

		if ( is_object( $class ) ) {
			// Instance property.
			$ref->setValue( $class, $value );
		} else {
			// Static property.
			$ref->setValue( $value );
		}

		return $previous;
	}

	/**
	 * Get the value of a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  string        $property Property name for which to gain access.
	 * @param  string|object $class    Class name for a static property, or instance for an instance property.
	 * @return mixed
	 */
	protected function getPropertyValue( $property, $class ) {
		$ref = $this->get_reflective_property( $property, $class );
		return $ref->getValue();
	}


	/** ----------------------------------------------------------------------------------------- */
	/** REFLECTIONS ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get reflective access to a private/protected method.
	 *
	 * @throws ReflectionException Throws an exception if method does not exist.
	 *
	 * @param  string $method_name Method name for which to gain access.
	 * @param  string $class_name  Name of the target class.
	 * @return ReflectionMethod
	 */
	protected function get_reflective_method( $method_name, $class_name ) {
		$class  = new ReflectionClass( $class_name );
		$method = $class->getMethod( $method_name );
		$method->setAccessible( true );

		return $method;
	}

	/**
	 * Get reflective access to a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  string       $property Property name for which to gain access.
	 * @param  string|mixed $class    Class name or instance.
	 * @return ReflectionProperty
	 */
	protected function get_reflective_property( $property, $class ) {
		$class    = new ReflectionClass( $class );
		$property = $class->getProperty( $property );
		$property->setAccessible( true );

		return $property;
	}

	/**
	 * Set the value of a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  mixed  $value    The value to set for the property.
	 * @param  string $property Property name for which to gain access.
	 * @param  mixed  $instance Instance of the target object.
	 * @return ReflectionProperty
	 */
	protected function set_reflective_property( $value, $property, $instance ) {
		$property = $this->get_reflective_property( $property, $instance );
		$property->setValue( $instance, $value );
		$property->setAccessible( false );

		return $property;
	}
}
