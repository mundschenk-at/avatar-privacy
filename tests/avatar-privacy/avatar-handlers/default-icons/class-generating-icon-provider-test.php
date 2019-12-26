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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Avatar_Handlers\Default_Icons;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generating_Icon_Provider;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generator;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generating_Icon_Provider unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generating_Icon_Provider
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generating_Icon_Provider
 */
class Generating_Icon_Provider_Test extends \Avatar_Privacy\Tests\TestCase {


	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses \Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider::__construct
	 */
	public function test_constructor() {
		// Dependencies.
		$generator  = m::mock( Generator::class );
		$file_cache = m::mock( Filesystem_Cache::class );
		$types      = [ 'foobar', 'barfoo', 'rhabarber' ];

		$mock = m::mock( Generating_Icon_Provider::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invoke_method( $mock, '__construct', [ $generator, $file_cache, $types ] );

		$this->assert_attribute_same( $generator, 'generator', $mock );
		$this->assert_attribute_same( $file_cache, 'file_cache', $mock );
	}

	/**
	 * Tests ::get_icon_url.
	 *
	 * @covers ::get_icon_url
	 *
	 * @uses ::__construct
	 * @uses \Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider::__construct
	 */
	public function test_get_icon_url() {
		// Dependencies.
		$generator  = m::mock( Generator::class );
		$file_cache = m::mock( Filesystem_Cache::class );
		$types      = [ 'foobar', 'barfoo', 'rhabarber' ];

		// System-under-test.
		$sut = m::mock( Generating_Icon_Provider::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$this->invoke_method( $sut, '__construct', [ $generator, $file_cache, $types ] );

		// Input parameters.
		$identity = 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b';
		$size     = 64;

		// Intermediate data.
		$filename = 'the filename';
		$basedir  = 'the/file/cache/base/directory/';
		$data     = 'binary image data';

		// Expected result.
		$url = 'some URL';

		$sut->shouldReceive( 'get_filename' )->once()->with( $identity, $size )->andReturn( $filename );

		$file_cache->shouldReceive( 'get_base_dir' )->once()->with()->andReturn( $basedir );

		$generator->shouldReceive( 'build' )->once()->with( $identity, $size )->andReturn( $data );

		$file_cache->shouldReceive( 'set' )->once()->with( $filename, $data )->andReturn( true );
		$file_cache->shouldReceive( 'get_url' )->once()->with( $filename )->andReturn( $url );

		$this->assertSame( $url, $sut->get_icon_url( $identity, $size ) );
	}

	/**
	 * Tests ::get_sub_dir.
	 *
	 * @covers ::get_sub_dir
	 */
	public function test_get_sub_dir() {
		$sut = m::mock( Generating_Icon_Provider::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->assertSame( 'f/0', $sut->get_sub_dir( 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b' ) );
	}
}
