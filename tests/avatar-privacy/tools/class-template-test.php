<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Tools;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Tools\Template;

/**
 * Avatar_Privacy\Tools\Template unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Tools\Template
 * @usesDefaultClass \Avatar_Privacy\Tools\Template
 */
class Template_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * Tests ::get_gravatar_link_rel.
	 *
	 * @covers ::get_gravatar_link_rel
	 */
	public function test_get_gravatar_link_rel() {
		Functions\expect( 'esc_attr' )->once()->with( m::type( 'string' ) )->andReturn( 'foo' );
		Filters\expectApplied( 'avatar_privacy_gravatar_link_rel' )->once()->with( 'noopener nofollow' )->andReturn( 'bar' );

		$this->assertSame( 'foo', Template::get_gravatar_link_rel() );
	}

	/**
	 * Tests ::get_gravatar_link_target.
	 *
	 * @covers ::get_gravatar_link_target
	 */
	public function test_get_gravatar_link_target() {
		Functions\expect( 'esc_attr' )->once()->with( m::type( 'string' ) )->andReturn( 'foo' );
		Filters\expectApplied( 'avatar_privacy_gravatar_link_target' )->once()->with( '_self' )->andReturn( 'bar' );

		$this->assertSame( 'foo', Template::get_gravatar_link_target() );
	}
}
