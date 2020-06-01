<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

use Avatar_Privacy\Core;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

use Mockery as m;

use Symfony\Bridge\PhpUnit\SetUpTearDownTrait;

/**
 * Unit tests for Avatar Privacy functions.
 */
class Avatar_Privacy_Functions_Test extends TestCase {

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$filesystem = [
			'plugin' => [
				'public' => [
					'partials' => [
						'comments' => [
							'use-gravatar.php' => 'USE_GRAVATAR',
						],
					],
				],
			],
		];

		// Set up virtual filesystem.
		$root = vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path
	}

	/**
	 * Tests run method.
	 *
	 * @covers ::avapr_get_avatar_checkbox
	 *
	 * @uses \Avatar_Privacy\get_gravatar_checkbox
	 * @uses \Avatar_Privacy\Components\Comments::get_gravatar_checkbox
	 */
	public function test_avapr_get_avatar_checkbox() {
		Functions\expect( '_deprecated_function' )->once()->with( 'avapr_get_avatar_checkbox', '2.3.0', 'Avatar_Privacy\get_gravatar_checkbox' );
		Functions\expect( 'is_user_logged_in' )->once()->andReturn( false );

		$this->assertSame( 'USE_GRAVATAR', \avapr_get_avatar_checkbox() );
	}

	/**
	 * Tests run method.
	 *
	 * @covers ::avapr_get_avatar_checkbox
	 *
	 * @uses \Avatar_Privacy\get_gravatar_checkbox
	 */
	public function test_avapr_get_avatar_checkbox_user_is_logged_in() {
		Functions\expect( '_deprecated_function' )->once()->with( 'avapr_get_avatar_checkbox', '2.3.0', 'Avatar_Privacy\get_gravatar_checkbox' );
		Functions\expect( 'is_user_logged_in' )->once()->andReturn( true );

		$this->assertSame( '', \avapr_get_avatar_checkbox() );
	}
}
