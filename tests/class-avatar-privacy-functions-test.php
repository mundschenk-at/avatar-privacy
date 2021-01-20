<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2021 Peter Putzer.
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

use Avatar_Privacy\Tests\TestCase;

use Mockery as m;

use Avatar_Privacy\Factory;
use Avatar_Privacy\Components\Comments;


/**
 * Unit tests for Avatar Privacy functions.
 */
class Avatar_Privacy_Functions_Test extends TestCase {

	/**
	 * Tests avapr_get_avatar_checkbox function.
	 *
	 * @covers avapr_get_avatar_checkbox
	 *
	 * @uses Avatar_Privacy\get_gravatar_checkbox
	 * @uses Avatar_Privacy\Factory::get
	 * @uses Avatar_Privacy\Components\Comments::get_gravatar_checkbox
	 */
	public function test_avapr_get_avatar_checkbox() {
		$result   = 'USE_GRAVATAR_MARKUP';
		$factory  = m::mock( Factory::class );
		$comments = m::mock( Comments::class );
		$this->set_static_value( Factory::class, 'factory', $factory );

		Functions\expect( '_deprecated_function' )->once()->with( 'avapr_get_avatar_checkbox', '2.3.0', 'Avatar_Privacy\get_gravatar_checkbox' );
		Functions\expect( 'is_user_logged_in' )->once()->andReturn( false );

		$factory->shouldReceive( 'create' )->once()->with( Comments::class )->andReturn( $comments );
		$comments->shouldReceive( 'get_gravatar_checkbox_markup' )->once()->andReturn( $result );

		$this->assertSame( $result, \avapr_get_avatar_checkbox() );
	}

	/**
	 * Tests avapr_get_avatar_checkbox function.
	 *
	 * @covers avapr_get_avatar_checkbox
	 *
	 * @uses Avatar_Privacy\get_gravatar_checkbox
	 */
	public function test_avapr_get_avatar_checkbox_user_is_logged_in() {
		Functions\expect( '_deprecated_function' )->once()->with( 'avapr_get_avatar_checkbox', '2.3.0', 'Avatar_Privacy\get_gravatar_checkbox' );
		Functions\expect( 'is_user_logged_in' )->once()->andReturn( true );

		$this->assertSame( '', \avapr_get_avatar_checkbox() );
	}

	/**
	 * Tests is_gd_image function.
	 *
	 * @covers is_gd_image
	 */
	public function test_is_gd_image_ok() {
		$resource = \imageCreate( 10, 10 );

		$this->assertTrue( \is_gd_image( $resource ) );

		\imageDestroy( $resource );
	}

	/**
	 * Tests is_gd_image function.
	 *
	 * @covers is_gd_image
	 */
	public function test_is_gd_image_not_ok() {
		$this->assertFalse( \is_gd_image( false ) );
	}
}
