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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Rings;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Rings unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Rings
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Rings
 */
class Rings_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * Tests ::build.
	 *
	 * @covers ::build
	 */
	public function test_build() {
		$seed = 'fake email hash';
		$size = 42;
		$data = 'fake SVG image';

		$sut = m::mock( Rings::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$sut->shouldReceive( 'generateSVGImage' )->once()->with( $seed, true )->andReturn( $data );

		$this->assertSame( $data, $sut->build( $seed, $size ) );
	}
}
