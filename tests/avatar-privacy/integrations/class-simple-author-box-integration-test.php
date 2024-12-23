<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2021-2024 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Integrations;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Integrations\Simple_Author_Box_Integration;
use Avatar_Privacy\Core\User_Fields;

use Simple_Author_Box;

/**
 * Avatar_Privacy\Integrations\Simple_Author_Box_Integration unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Integrations\Simple_Author_Box_Integration
 * @usesDefaultClass \Avatar_Privacy\Integrations\Simple_Author_Box_Integration
 *
 * @uses ::__construct
 */
class Simple_Author_Box_Integration_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Simple_Author_Box_Integration&m\MockInterface
	 */
	private $sut;

	/**
	 * Mocked helper object.
	 *
	 * @var User_Fields&m\MockInterface
	 */
	private $user_fields;

	/**
	 * Mocked helper object.
	 *
	 * @var Simple_Author_Box&m\MockInterface
	 */
	private $simple_author_box;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$this->user_fields       = m::mock( User_Fields::class );
		$this->simple_author_box = m::mock( 'alias:' . Simple_Author_Box::class );

		$this->sut = m::mock( Simple_Author_Box_Integration::class, [ $this->user_fields ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Simple_Author_Box_Integration::class )->makePartial();

		$mock->__construct( $this->user_fields );

		$this->assert_attribute_same( $this->user_fields, 'user_fields', $mock );
	}

	/**
	 * Tests ::check.
	 *
	 * @covers ::check
	 */
	public function test_check() {
		$this->assertTrue( $this->sut->check() );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'init' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::init.
	 *
	 * @covers ::init
	 */
	public function test_init() {
		$this->simple_author_box->shouldReceive( 'get_instance' )->once()->andReturn( $this->simple_author_box );

		Filters\expectAdded( 'avatar_privacy_profile_picture_upload_disabled' )->once()->with( '__return_true', 10, 0 );
		Filters\expectRemoved( 'get_avatar' )->once()->with( [ $this->simple_author_box, 'replace_gravatar_image' ], 10 );
		Filters\expectAdded( 'avatar_privacy_pre_get_user_avatar' )->once()->with( [ $this->sut, 'enable_user_avatars' ], 10, 2 );
		Actions\expectAdded( 'added_user_meta' )->once()->with( [ $this->sut, 'invalidate_cache_after_avatar_change' ], 10, 3 );
		Actions\expectAdded( 'updated_user_meta' )->once()->with( [ $this->sut, 'invalidate_cache_after_avatar_change' ], 10, 3 );
		Actions\expectAdded( 'deleted_user_meta' )->once()->with( [ $this->sut, 'invalidate_cache_after_avatar_change' ], 10, 3 );

		$this->assertNull( $this->sut->init() );
	}

	/**
	 * Tests ::enable_user_avatars.
	 *
	 * @covers ::enable_user_avatars
	 */
	public function test_enable_user_avatars() {
		// Input.
		$user_id = 42;

		// Intermediary.
		$file    = 'some/file';
		$url     = "https://foobar/{$file}";
		$type    = 'mime/type';
		$absfile = \ABSPATH . $file;

		// Expected result.
		$result = [
			'file' => $absfile,
			'type' => $type,
		];

		$this->sut->shouldReceive( 'get_simple_author_box_avatar' )->once()->with( $user_id )->andReturn( $url );

		Functions\expect( 'wp_make_link_relative' )->once()->with( $url )->andReturn( $file );
		Functions\expect( 'wp_check_filetype' )->once()->with( $absfile )->andReturn( [ 'type' => $type ] );

		$this->assertSame( $result, $this->sut->enable_user_avatars( null, $user_id ) );
	}

	/**
	 * Tests ::enable_user_avatars.
	 *
	 * @covers ::enable_user_avatars
	 */
	public function test_enable_user_avatars_no_avatar() {
		// Input.
		$user_id = 42;

		$this->sut->shouldReceive( 'get_simple_author_box_avatar' )->once()->with( $user_id )->andReturn( '' );

		Functions\expect( 'wp_make_link_relative' )->never();
		Functions\expect( 'wp_check_filetype' )->never();

		$this->assertNull( $this->sut->enable_user_avatars( null, $user_id ) );
	}

	/**
	 * Tests ::get_simple_author_box_avatar.
	 *
	 * @covers ::get_simple_author_box_avatar
	 */
	public function test_get_simple_author_box_avatar() {
		// Input.
		$user_id = 42;

		// Result.
		$url = 'https://foobar/some/path/image.png';

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, Simple_Author_Box_Integration::USER_META_KEY, true )->andReturn( $url );

		$this->assertSame( $url, $this->sut->get_simple_author_box_avatar( $user_id ) );
	}

	/**
	 * Tests ::get_simple_author_box_avatar.
	 *
	 * @covers ::get_simple_author_box_avatar
	 */
	public function test_get_simple_author_box_avatar_no_user_meta() {
		// Input.
		$user_id = 42;

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, Simple_Author_Box_Integration::USER_META_KEY, true )->andReturn( false );

		$this->assertSame( '', $this->sut->get_simple_author_box_avatar( $user_id ) );
	}

	/**
	 * Tests ::invalidate_cache_after_avatar_change.
	 *
	 * @covers ::invalidate_cache_after_avatar_change
	 */
	public function test_invalidate_cache_after_avatar_change() {
		// Input.
		$meta_id  = 4711;
		$user_id  = 42;
		$meta_key = 'foobar';

		$this->user_fields->shouldReceive( 'invalidate_local_avatar_cache' )->never();

		$this->assertNull( $this->sut->invalidate_cache_after_avatar_change( $meta_id, $user_id, $meta_key ) );
	}

	/**
	 * Tests ::invalidate_cache_after_avatar_change.
	 *
	 * @covers ::invalidate_cache_after_avatar_change
	 */
	public function test_invalidate_cache_after_avatar_change_other_metadata() {
		// Input.
		$meta_id  = 4711;
		$user_id  = 42;
		$meta_key = Simple_Author_Box_Integration::USER_META_KEY;

		$this->user_fields->shouldReceive( 'invalidate_local_avatar_cache' )->once()->with( $user_id );

		$this->assertNull( $this->sut->invalidate_cache_after_avatar_change( $meta_id, $user_id, $meta_key ) );
	}
}
