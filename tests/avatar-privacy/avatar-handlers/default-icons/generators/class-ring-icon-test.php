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

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Ring_Icon;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Ring_Icon unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Ring_Icon
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Ring_Icon
 */
class Ring_Icon_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Ring_Icon
	 */
	private $sut;

	/**
	 * Tests ::get_svg_image_data.
	 *
	 * @covers ::get_svg_image_data
	 */
	public function test_get_svg_image_data() {
		$sut = m::mock( Ring_Icon::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$icon = 'fake SVG icon data';
		$seed = 'fake email hash';

		$sut->shouldReceive( 'generateSVGImage' )->once()->with( $seed, true )->andReturn( $icon );

		$this->assertSame( $icon, $sut->get_svg_image_data( $seed ) );
	}
}
