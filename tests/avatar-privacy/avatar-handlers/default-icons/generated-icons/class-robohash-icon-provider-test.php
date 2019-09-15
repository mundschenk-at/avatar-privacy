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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons\Robohash_Icon_Provider;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons\Robohash_Icon_Provider unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons\Robohash_Icon_Provider
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons\Robohash_Icon_Provider
 *
 * @uses ::__construct
 */
class Robohash_Icon_Provider_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generating_Icon_Provider::__construct
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider::__construct
	 */
	public function test_constructor() {
		$generator  = m::mock( Generators\Robohash::class );
		$file_cache = m::mock( Filesystem_Cache::class );
		$sut        = m::mock( Robohash_Icon_Provider::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invokeMethod( $sut, '__construct', [ $generator, $file_cache ] );

		$this->assertAttributeSame( $generator, 'generator', $sut );
		$this->assertAttributeSame( $file_cache, 'file_cache', $sut );
		$this->assertAttributeSame( [ 'robohash' => 0 ], 'valid_types', $sut );
	}

	/**
	 * Tests ::get_filename.
	 *
	 * @covers ::get_filename
	 */
	public function test_get_filename() {
		$identity = 'fake_email_hash';
		$size     = 42;
		$path     = 'some/path';

		$sut = m::mock( Robohash_Icon_Provider::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$sut->shouldReceive( 'get_sub_dir' )->once()->with( $identity )->andReturn( $path );

		$this->assertSame( "robohash/{$path}/{$identity}.svg", $sut->get_filename( $identity, $size ) );
	}

	/**
	 * Tests ::get_name.
	 *
	 * @covers ::get_name
	 */
	public function test_get_name() {
		$translated = 'translated name';

		$sut = m::mock( Robohash_Icon_Provider::class )->makePartial()->shouldAllowMockingProtectedMethods();

		Functions\expect( '__' )->once()->with( m::type( 'string' ), 'avatar-privacy' )->andReturn( $translated );

		$this->assertSame( $translated, $sut->get_name() );
	}
}
