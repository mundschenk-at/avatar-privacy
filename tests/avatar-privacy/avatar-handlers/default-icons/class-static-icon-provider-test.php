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

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icon_Provider;
use Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icon_Provider unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icon_Provider
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icon_Provider
 */
class Static_Icon_Provider_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider::__construct
	 */
	public function test_constructor_array() {
		$mock        = m::mock( Static_Icon_Provider::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$valid_types = [ 'foobar', 'barfoo', 'rhabarber' ];
		$basename    = 'image-basename';

		$mock->__construct( $valid_types, $basename );

		$this->assertAttributeSame( \array_flip( $valid_types ), 'valid_types', $mock, Abstract_Icon_Provider::class );
		$this->assertAttributeSame( $basename, 'icon_basename', $mock );
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider::__construct
	 */
	public function test_constructor_string() {
		$mock        = m::mock( Static_Icon_Provider::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$valid_types = 'foobar';
		$basename    = 'image-basename';

		$mock->__construct( $valid_types, $basename );

		$this->assertAttributeSame( [ 'foobar' => 0 ], 'valid_types', $mock, Abstract_Icon_Provider::class );
		$this->assertAttributeSame( $basename, 'icon_basename', $mock );
	}

	/**
	 * Tests ::get_icon_url.
	 *
	 * @covers ::get_icon_url
	 *
	 * @uses ::__construct
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider::__construct
	 */
	public function test_get_icon_url_small() {
		$types    = [ 'foobar', 'barfoo', 'rhabarber' ];
		$basename = 'image-basename';
		$sut      = m::mock( Static_Icon_Provider::class, [ $types, $basename ] )->makePartial()->shouldAllowMockingProtectedMethods();

		// Input parameters.
		$identity = 'someidentityhash';
		$size     = 64;

		// Expected result.
		$url = 'some URL';

		Functions\expect( 'plugins_url' )->once()->with( "public/images/{$basename}-64.png", \AVATAR_PRIVACY_PLUGIN_FILE )->andReturn( $url );

		$this->assertSame( $url, $sut->get_icon_url( $identity, $size ) );
	}

	/**
	 * Tests ::get_icon_url.
	 *
	 * @covers ::get_icon_url
	 *
	 * @uses ::__construct
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider::__construct
	 */
	public function test_get_icon_large() {
		$types    = [ 'foobar', 'barfoo', 'rhabarber' ];
		$basename = 'image-basename';
		$sut      = m::mock( Static_Icon_Provider::class, [ $types, $basename ] )->makePartial()->shouldAllowMockingProtectedMethods();

		// Input parameters.
		$identity = 'someidentityhash';
		$size     = 65;

		// Expected result.
		$url = 'some URL';

		Functions\expect( 'plugins_url' )->once()->with( "public/images/{$basename}-128.png", \AVATAR_PRIVACY_PLUGIN_FILE )->andReturn( $url );

		$this->assertSame( $url, $sut->get_icon_url( $identity, $size ) );
	}
}
