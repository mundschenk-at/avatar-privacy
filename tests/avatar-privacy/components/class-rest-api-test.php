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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Components\REST_API;

use Avatar_Privacy\Core;

/**
 * Avatar_Privacy\Components\REST_API unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Components\REST_API
 * @usesDefaultClass \Avatar_Privacy\Components\REST_API
 */
class REST_API_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var REST_API
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->sut = m::mock( REST_API::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Filters\expectAdded( 'rest_prepare_user' )->once()->with( [ $this->sut, 'fix_rest_user_avatars' ], 10, 2 );
		Filters\expectAdded( 'rest_prepare_comment' )->once()->with( [ $this->sut, 'fix_rest_comment_author_avatars' ], 10, 2 );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::fix_rest_user_avatars.
	 *
	 * @covers ::fix_rest_user_avatars
	 * @covers ::rest_get_avatar_urls
	 */
	public function test_fix_rest_user_avatars() {
		$response = m::mock( 'WP_REST_Response' );
		$user     = m::mock( 'WP_User' );
		$expected = [
			60 => 'another/url1',
			90 => 'another/url2',
		];

		// Input data.
		$sizes = [ 60, 90 ];

		// Prepare response.
		$response->data = [
			'avatar_urls' => [ 'some/url' ],
		];

		Functions\expect( 'rest_get_avatar_sizes' )->once()->andReturn( $sizes );
		Functions\expect( 'get_avatar_url' )->times( \count( $sizes ) )->with( $user, m::type( 'array' ) )->andReturn( 'another/url1', 'another/url2' );

		$this->assertSame( $response, $this->sut->fix_rest_user_avatars( $response, $user ) );
		$this->assertArraySubset( [ 'avatar_urls' => [] ], $response->data );
	}

	/**
	 * Tests ::fix_rest_comment_author_avatars.
	 *
	 * @covers ::fix_rest_comment_author_avatars
	 * @covers ::rest_get_avatar_urls
	 */
	public function test_fix_rest_comment_author_avatars() {
		$response = m::mock( 'WP_REST_Response' );
		$comment  = m::mock( 'WP_Comment' );
		$expected = [
			60 => 'another/url1',
			90 => 'another/url2',
		];

		// Input data.
		$sizes = [ 60, 90 ];

		// Prepare response.
		$response->data = [
			'author_avatar_urls' => [ 'some/url' ],
		];

		Functions\expect( 'rest_get_avatar_sizes' )->once()->andReturn( $sizes );
		Functions\expect( 'get_avatar_url' )->times( \count( $sizes ) )->with( $comment, m::type( 'array' ) )->andReturn( 'another/url1', 'another/url2' );

		$this->assertSame( $response, $this->sut->fix_rest_comment_author_avatars( $response, $comment ) );
		$this->assertArraySubset( [ 'author_avatar_urls' => $expected ], $response->data );
	}
}
