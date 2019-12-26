<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2017-2019 Peter Putzer.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  ***
 *
 * @package mundschenk-at/avatar-privacy/tests
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Avatar_Privacy\Tests;

use Brain\Monkey;
use Symfony\Bridge\PhpUnit\SetUpTearDownTrait;

/**
 * Abstract base class for \PHP_Typography\* unit tests.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase {
	/**
	 * The SetUpTearDownTrait from symfony/phpunit-bridge is used to allow
	 * test cases to be compatible with PHPUnit 8 and earlier versions at the
	 * same time (needed for PHP 7.4 support).
	 */
	use SetUpTearDownTrait;

	/**
	 * Redirects ::setUp to polymorphic ::set_up.
	 *
	 * @since 2.3.3
	 *
	 * @return void
	 */
	private function doSetUp() {
		$this->set_up();
	}

	/**
	 * Redirects ::tearDown to polymorphic ::tear_down.
	 *
	 * @since 2.3.3
	 *
	 * @return void
	 */
	private function doTearDown() {
		$this->tear_down();
	}

	/**
	 * Sets up Brain Monkey.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tears down Brain Monkey.
	 *
	 * @since 2.3.3 Renamed to `tear_down`.
	 */
	protected function tear_down() {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Return encoded HTML string (everything except <>"').
	 *
	 * @param string $html A HTML fragment.
	 */
	protected function clean_html( $html ) {
		// Convert everything except Latin and Cyrillic and Thai.
		static $convmap = [
			// Simple Latin characters.
			0x80,   0x03ff,   0, 0xffffff, // @codingStandardsIgnoreLine.
			// Cyrillic characters.
			0x0514, 0x0dff, 0, 0xffffff, // @codingStandardsIgnoreLine.
			// Thai characters.
			0x0e7f, 0x10ffff, 0, 0xffffff, // @codingStandardsIgnoreLine.
		];

		return str_replace( [ '&lt;', '&gt;' ], [ '<', '>' ], mb_encode_numericentity( htmlentities( $html, ENT_NOQUOTES, 'UTF-8', false ), $convmap, 'UTF-8' ) );
	}

	/**
	 * Call protected/private method of a class.
	 *
	 * @since 2.3.3 Renamed to `invoke_method`. Parameter `classname` has been removed.
	 *
	 * @param object $object      Instantiated object that we will run method on.
	 * @param string $method_name Method name to call.
	 * @param array  $parameters  Array of parameters to pass into method.
	 *
	 * @return mixed Method return.
	 *
	 * @throws \RuntimeException    The method could not be found in the object.
	 */
	protected function invoke_method( $object, $method_name, array $parameters = [] ) {

		$reflection = new \ReflectionObject( $object );
		while ( ! empty( $reflection ) ) {
			try {
				$method = $reflection->getMethod( $method_name );
				$method->setAccessible( true );
				return $method->invokeArgs( $object, $parameters );
			} catch ( \ReflectionException $e ) {
				// Try again with superclass.
				$reflection = $reflection->getParentClass();
			}
		}

		throw new \RuntimeException( "Method $method_name not found in object." );
	}

	/**
	 * Call protected/private method of a class.
	 *
	 * @param string $classname   A class that we will run the method on.
	 * @param string $method_name Method name to call.
	 * @param array  $parameters  Array of parameters to pass into method.
	 *
	 * @return mixed Method return.
	 */
	protected function invokeStaticMethod( $classname, $method_name, array $parameters = [] ) {
		$reflection = new \ReflectionClass( $classname );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( null, $parameters );
	}

	/**
	 * Sets the value of a private/protected property of a class.
	 *
	 * @param string     $classname     A class whose property we will access.
	 * @param string     $property_name Property to set.
	 * @param mixed|null $value         The new value.
	 */
	protected function setStaticValue( $classname, $property_name, $value ) {
		$reflection = new \ReflectionClass( $classname );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );
		$property->setValue( $value );
	}

	/**
	 * Sets the value of a private/protected property of a class.
	 *
	 * @since 2.3.3 Renamed to `set_value`. Parameter `classname` has been removed.
	 *
	 * @param object     $object        Instantiated object that we will run method on.
	 * @param string     $property_name Property to set.
	 * @param mixed|null $value         The new value.
	 *
	 * @throws \RuntimeException    The attribute could not be found in the object.
	 */
	protected function set_value( $object, $property_name, $value ) {

		$reflection = new \ReflectionObject( $object );
		while ( ! empty( $reflection ) ) {
			try {
				$property = $reflection->getProperty( $property_name );
				$property->setAccessible( true );
				$property->setValue( $object, $value );
				return;
			} catch ( \ReflectionException $e ) {
				// Try again with superclass.
				$reflection = $reflection->getParentClass();
			}
		}

		throw new \RuntimeException( "Attribute $property_name not found in object." );
	}

	/**
	 * Retrieves the value of a private/protected property of a class.
	 *
	 * @param string $classname     A class whose property we will access.
	 * @param string $property_name Property to set.
	 *
	 * @return mixed
	 */
	protected function getStaticValue( $classname, $property_name ) {
		$reflection = new \ReflectionClass( $classname );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );

		return $property->getValue();
	}

	/**
	 * Retrieves the value of a private/protected property of a class.
	 *
	 * @since 2.3.3 Renamed to `get_value`. Parameter `classname` has been removed.
	 *
	 * @param object $object        Instantiated object that we will run method on.
	 * @param string $property_name Property to set.
	 *
	 * @return mixed
	 *
	 * @throws \RuntimeException    The attribute could not be found in the object.
	 */
	protected function get_value( $object, $property_name ) {

		$reflection = new \ReflectionObject( $object );
		while ( ! empty( $reflection ) ) {
			try {
				$property = $reflection->getProperty( $property_name );
				$property->setAccessible( true );
				$value = $property->getValue( $object );
				break;
			} catch ( \ReflectionException $e ) {
				// Try again with superclass.
				$reflection = $reflection->getParentClass();
			}
		}

		if ( isset( $value ) ) {
			return $value;
		}

		throw new \RuntimeException( "Attribute $property_name not found in object." );
	}

	/**
	 * Reports an error identified by $message if $attribute in $object is not the same as $value.
	 *
	 * @since 2.3.3
	 *
	 * @param mixed  $value     The comparison value.
	 * @param string $attribute The attribute name.
	 * @param object $object    The object.
	 * @param string $message   Optional. Default ''.
	 */
	protected function assert_attribute_same( $value, $attribute, $object, $message = '' ) {
		return $this->assertSame( $value, $this->get_value( $object, $attribute ), $message );
	}

	/**
	 * Reports an error identified by $message if $attribute in $object does not have the $key.
	 *
	 * @since 2.3.3 Renamed to `assert_attribute_array_has_key`.
	 *
	 * @param string $key       The array key.
	 * @param string $attribute The attribute name.
	 * @param object $object    The object.
	 * @param string $message   Optional. Default ''.
	 */
	protected function assert_attribute_array_has_key( $key, $attribute, $object, $message = '' ) {
		return $this->assertArrayHasKey( $key, $this->get_value( $object, $attribute ), $message );
	}

	/**
	 * Reports an error identified by $message if $attribute in $object does have the $key.
	 *
	 * @since 2.3.3 Renamed to `assert_attribute_array_not_has_key`.
	 *
	 * @param string $key       The array key.
	 * @param string $attribute The attribute name.
	 * @param object $object    The object.
	 * @param string $message   Optional. Default ''.
	 */
	protected function assert_attribute_array_not_has_key( $key, $attribute, $object, $message = '' ) {
		return $this->assertArrayNotHasKey( $key, $this->get_value( $object, $attribute ), $message );
	}
}
