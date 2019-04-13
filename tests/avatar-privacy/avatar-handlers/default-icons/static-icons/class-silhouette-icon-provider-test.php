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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Silhouette_Icon_Provider;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Silhouette_Icon_Provider unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Silhouette_Icon_Provider
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Silhouette_Icon_Provider
 *
 * @uses ::__construct
 */
class Silhouette_Icon_Provider_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\SVG_Icon_Provider::__construct
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider::__construct
	 */
	public function test_constructor() {
		$sut   = m::mock( Silhouette_Icon_Provider::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$types = \array_flip( [ 'silhouette', 'view-media-artist' ] );

		$this->invokeMethod( $sut, '__construct', [] );

		$this->assertAttributeSame( $types, 'valid_types', $sut );
		$this->assertAttributeSame( 'silhouette', 'icon_basename', $sut );
	}

	/**
	 * Tests ::get_name.
	 *
	 * @covers ::get_name
	 */
	public function test_get_name() {
		$translated = 'translated name';

		$sut = m::mock( Silhouette_Icon_Provider::class )->makePartial()->shouldAllowMockingProtectedMethods();

		Functions\expect( '__' )->once()->with( m::type( 'string' ), 'avatar-privacy' )->andReturn( $translated );

		$this->assertSame( $translated, $sut->get_name() );
	}
}
