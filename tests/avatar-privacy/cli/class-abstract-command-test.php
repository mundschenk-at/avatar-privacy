<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\CLI;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\CLI\Abstract_Command;

/**
 * Avatar_Privacy\CLI\Abstract_Command unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\CLI\Abstract_Command
 * @usesDefaultClass \Avatar_Privacy\CLI\Abstract_Command
 */
class Abstract_Command_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * Tests ::stop_the_insanity.
	 *
	 * @covers ::stop_the_insanity
	 */
	public function test_stop_the_insanity() {
		global $wpdb;
		global $wp_object_cache;

		// Fake globals.
		$wpdb                            = m::mock( \wpdb::class ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->queries                   = [ 'foo', 'bar' ];
		$wp_object_cache                 = m::mock( \WP_Object_Cache::class ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_object_cache->group_ops      = [ 'foo', 'bar' ];
		$wp_object_cache->stats          = [ 'foo', 'bar' ];
		$wp_object_cache->memcache_debug = [ 'foo', 'bar' ];
		$wp_object_cache->cache          = [ 'foo', 'bar' ];

		$sut = m::mock( Abstract_Command::class );

		$this->assertNull( $sut->stop_the_insanity() );

		$this->assertEmpty( $this->get_value( $wpdb, 'queries' ) );
		$this->assertEmpty( $this->get_value( $wp_object_cache, 'group_ops' ) );
		$this->assertEmpty( $this->get_value( $wp_object_cache, 'stats' ) );
		$this->assertEmpty( $this->get_value( $wp_object_cache, 'memcache_debug' ) );
		$this->assertEmpty( $this->get_value( $wp_object_cache, 'cache' ) );
	}

	/**
	 * Tests ::iterator_to_array
	 *
	 * @covers ::iterator_to_array
	 */
	public function test_iterator_to_array() {
		$array = [
			'foo' => 'bar',
			'bar' => 'baz',
			'baz' => 'foo',
		];

		$sut = m::mock( Abstract_Command::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$iterator = new \ArrayIterator( $array );

		$this->assertSame( $array, $sut->iterator_to_array( $iterator ) );
	}
}
