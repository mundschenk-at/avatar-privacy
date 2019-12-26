<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

use Mockery as m;

/**
 * Avatar_Privacy_Uninstallation_Requirements unit test.
 *
 * @coversDefaultClass \Avatar_Privacy_Uninstallation_Requirements
 * @usesDefaultClass \Avatar_Privacy_Uninstallation_Requirements
 */
class Avatar_Privacy_Uninstallation_Requirements_Test extends TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var \Avatar_Privacy_Uninstallation_Requirements
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$this->sut = m::mock( \Avatar_Privacy_Uninstallation_Requirements::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {

		Functions\expect( 'wp_parse_args' )->andReturnUsing(
			function( $args, $defaults ) {
				return \array_merge( $defaults, $args );
			}
		);
		$req = m::mock( \Avatar_Privacy_Uninstallation_Requirements::class )->makePartial();
		$req->__construct( 'some_file' );

		$this->assertSame( 'Avatar Privacy', $this->get_value( $req, 'plugin_name' ) );
		$this->assertSame( 'avatar-privacy', $this->get_value( $req, 'textdomain' ) );
		$this->assertSame(
			[
				'php'       => '5.6.0',
				'multibyte' => false,
				'utf-8'     => false,
			],
			$this->get_value( $req, 'install_requirements' )
		);
	}
}
