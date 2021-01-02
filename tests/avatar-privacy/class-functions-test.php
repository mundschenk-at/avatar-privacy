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

namespace Avatar_Privacy\Tests\Avatar_Privacy;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Factory;
use Avatar_Privacy\Components\Comments;

/**
 * Unit tests for Avatar Privacy functions.
 */
class Functions_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The factory mock.
	 *
	 * @var Factory
	 */
	private $factory;

	/**
	 * The comments component mock.
	 *
	 * @var Comments
	 */
	private $comments;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$this->factory  = m::mock( Factory::class );
		$this->comments = m::mock( Comments::class );
		$this->set_static_value( Factory::class, 'factory', $this->factory );
	}

	/**
	 * Tests run method.
	 *
	 * @covers Avatar_Privacy\get_gravatar_checkbox
	 *
	 * @uses Avatar_Privacy\Factory::get
	 * @uses Avatar_Privacy\Components\Comments::get_gravatar_checkbox
	 */
	public function test_get_gravatar_checkbox() {
		$result = 'USE_GRAVATAR_MARKUP';

		Functions\expect( 'is_user_logged_in' )->once()->andReturn( false );
		$this->factory->shouldReceive( 'create' )->once()->with( Comments::class )->andReturn( $this->comments );
		$this->comments->shouldReceive( 'get_gravatar_checkbox_markup' )->once()->andReturn( $result );

		$this->assertSame( $result, \Avatar_Privacy\get_gravatar_checkbox() );
	}

	/**
	 * Tests run method.
	 *
	 * @covers \Avatar_Privacy\get_gravatar_checkbox
	 */
	public function test_get_gravatar_checkbox_user_is_logged_in() {
		Functions\expect( 'is_user_logged_in' )->once()->andReturn( true );

		$this->factory->shouldReceive( 'create' )->never();
		$this->comments->shouldReceive( 'get_gravatar_checkbox_markup' )->never();

		$this->assertSame( '', \Avatar_Privacy\get_gravatar_checkbox() );
	}

	/**
	 * Tests run method.
	 *
	 * @covers \Avatar_Privacy\gravatar_checkbox
	 *
	 * @uses Avatar_Privacy\get_gravatar_checkbox
	 * @uses Avatar_Privacy\Factory::get
	 * @uses Avatar_Privacy\Components\Comments::get_gravatar_checkbox
	 */
	public function test_gravatar_checkbox() {
		$result = 'USE_GRAVATAR_MARKUP';

		Functions\expect( 'is_user_logged_in' )->once()->andReturn( false );
		$this->factory->shouldReceive( 'create' )->once()->with( Comments::class )->andReturn( $this->comments );
		$this->comments->shouldReceive( 'get_gravatar_checkbox_markup' )->once()->andReturn( $result );

		$this->expectOutputString( $result );
		$this->assertNull( \Avatar_Privacy\gravatar_checkbox() );
	}
}
