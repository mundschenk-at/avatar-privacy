<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2024 Peter Putzer.
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

use Avatar_Privacy\Components\Privacy_Tools;

use Avatar_Privacy\Core\Comment_Author_Fields;
use Avatar_Privacy\Core\User_Fields;

/**
 * Avatar_Privacy\Components\Privacy_Tools unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Components\Privacy_Tools
 * @usesDefaultClass \Avatar_Privacy\Components\Privacy_Tools
 *
 * @uses ::__construct
 */
class Privacy_Tools_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Privacy_Tools&m\MockInterface
	 */
	private $sut;

	/**
	 * The user fields API.
	 *
	 * @var User_Fields&m\MockInterface
	 */
	private $registered_user;

	/**
	 * Required helper object.
	 *
	 * @var Comment_Author_Fields&m\MockInterface
	 */
	private $comment_author;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		// Ubiquitous functions.
		Functions\when( '__' )->returnArg();

		// Mock required helpers.
		$this->registered_user = m::mock( User_Fields::class );
		$this->comment_author  = m::mock( Comment_Author_Fields::class );

		$this->sut = m::mock( Privacy_Tools::class, [ $this->registered_user, $this->comment_author ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Privacy_Tools::class )->makePartial();

		$mock->__construct( $this->registered_user, $this->comment_author );

		$this->assert_attribute_same( $this->registered_user, 'registered_user', $mock );
		$this->assert_attribute_same( $this->comment_author, 'comment_author', $mock );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Actions\expectAdded( 'admin_init' )->once()->with( [ $this->sut, 'admin_init' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::admin_init.
	 *
	 * @covers ::admin_init
	 */
	public function test_admin_init() {
		$this->sut->shouldReceive( 'add_privacy_notice_content' )->once();

		Filters\expectAdded( 'wp_privacy_personal_data_exporters' )->once()->with( [ $this->sut, 'register_personal_data_exporter' ], 0 );
		Filters\expectAdded( 'wp_privacy_personal_data_erasers' )->once()->with( [ $this->sut, 'register_personal_data_eraser' ] );

		$this->assertNull( $this->sut->admin_init() );
	}

	/**
	 * Tests ::add_privacy_notice_content.
	 *
	 * @covers ::add_privacy_notice_content
	 */
	public function test_add_privacy_notice_content_old_wordpress() {
		// No expectation so that the function does not exist.
		$this->assertNull( $this->sut->add_privacy_notice_content() );
	}

	/**
	 * Tests ::add_privacy_notice_content.
	 *
	 * @covers ::add_privacy_notice_content
	 */
	public function test_add_privacy_notice_content() {
		Functions\expect( 'wp_add_privacy_policy_content' )->once();

		$this->assertNull( $this->sut->add_privacy_notice_content() );
	}

	/**
	 * Tests ::register_personal_data_exporter.
	 *
	 * @covers ::register_personal_data_exporter
	 */
	public function test_register_personal_data_exporter() {
		$exporters = $this->sut->register_personal_data_exporter( [] );

		$this->assertArrayHasKey( 'avatar-privacy-user', $exporters );
		$this->assertArrayHasKey( 'avatar-privacy-comment-author', $exporters );

		$this->assertArrayHasKey( 'callback', $exporters['avatar-privacy-user'] );
		$this->assertArrayHasKey( 'callback', $exporters['avatar-privacy-comment-author'] );

		$this->assertSame( [ $this->sut, 'export_user_data' ], $exporters['avatar-privacy-user']['callback'] );
		$this->assertSame( [ $this->sut, 'export_comment_author_data' ], $exporters['avatar-privacy-comment-author']['callback'] );
	}

	/**
	 * Tests ::register_personal_data_eraser.
	 *
	 * @covers ::register_personal_data_eraser
	 */
	public function test_register_personal_data_eraser() {
		$erasers = $this->sut->register_personal_data_eraser( [] );

		$this->assertArrayHasKey( 'avatar-privacy', $erasers );
		$this->assertArrayHasKey( 'callback', $erasers['avatar-privacy'] );
		$this->assertSame( [ $this->sut, 'erase_data' ], $erasers['avatar-privacy']['callback'] );
	}

	/**
	 * Tests ::export_user_data.
	 *
	 * @covers ::export_user_data
	 */
	public function test_export_user_data() {
		// Input data.
		$email = 'foo@bar.org';
		$page  = 1;

		// User mock.
		$user     = m::mock( 'WP_User' );
		$user->ID = 66;
		$hash     = 'hashed_email';
		$gravatar = 'true';
		$anon     = 'false';
		$local    = [
			'file' => 'wordpress/path/user/avatar.png',
		];

		// Function results.
		$site_url = 'https://some.blog';

		Functions\expect( 'get_user_by' )->once()->with( 'email', $email )->andReturn( $user );
		$this->registered_user->shouldReceive( 'get_hash' )->once()->with( $user->ID )->andReturn( $hash );
		$this->registered_user->shouldReceive( 'has_gravatar_policy' )->once()->with( $user->ID )->andReturn( true );
		$this->registered_user->shouldReceive( 'allows_gravatar_use' )->once()->with( $user->ID )->andReturn( $gravatar );
		$this->registered_user->shouldReceive( 'has_anonymous_commenting_policy' )->once()->with( $user->ID )->andReturn( true );
		$this->registered_user->shouldReceive( 'allows_anonymous_commenting' )->once()->with( $user->ID )->andReturn( $anon );
		$this->registered_user->shouldReceive( 'get_local_avatar' )->once()->with( $user->ID )->andReturn( $local );
		Functions\expect( 'site_url' )->once()->andReturn( $site_url );

		$result = $this->sut->export_user_data( $email, $page );

		$this->assertTrue( $result['done'] );
		$this->assertSame( 'user', $result['data'][0]['group_id'] );
		$this->assertSame( "user-{$user->ID}", $result['data'][0]['item_id'] );
		$this->assertCount( 4, $result['data'][0]['data'] );
	}

	/**
	 * Tests ::export_user_data.
	 *
	 * @covers ::export_user_data
	 */
	public function test_export_user_data_invalid_user() {
		// Input data.
		$email = 'foo@bar.org';
		$page  = 1;

		Functions\expect( 'get_user_by' )->once()->with( 'email', $email )->andReturnFalse();
		$this->registered_user->shouldReceive( 'get_hash' )->never();
		$this->registered_user->shouldReceive( 'allows_gravatar_use' )->never();
		$this->registered_user->shouldReceive( 'allows_anonymous_commenting' )->never();
		$this->registered_user->shouldReceive( 'get_local_avatar' )->never();
		Functions\expect( 'site_url' )->never();

		$result = $this->sut->export_user_data( $email, $page );

		$this->assertTrue( $result['done'] );
		$this->assertEmpty( $result['data'] );
	}

	/**
	 * Tests ::export_comment_author_data.
	 *
	 * @covers ::export_comment_author_data
	 */
	public function test_export_comment_author_data() {
		// Input data.
		$email = 'foo@bar.org';
		$page  = 1;

		// Comment author data mock.
		$raw_data = (object) [
			'id'           => 5,
			'email'        => $email,
			'hash'         => 'hashed_email',
			'use_gravatar' => true,
			'last_updated' => 'a date',
			'log_message'  => 'we done something',
		];

		$this->comment_author->shouldReceive( 'load' )->once()->with( $email )->andReturn( $raw_data );

		$result = $this->sut->export_comment_author_data( $email, $page );

		$this->assertTrue( $result['done'] );
		$this->assertSame( 'avatar-privacy', $result['data'][0]['group_id'] );
		$this->assertSame( "avatar-privacy-{$raw_data->id}", $result['data'][0]['item_id'] );
		$this->assertCount( 6, $result['data'][0]['data'] );
	}

	/**
	 * Tests ::export_comment_author_data.
	 *
	 * @covers ::export_comment_author_data
	 */
	public function test_export_comment_author_data_unknown_email() {
		// Input data.
		$email = 'foo@bar.org';
		$page  = 1;

		$this->comment_author->shouldReceive( 'load' )->once()->with( $email )->andReturnFalse();

		$result = $this->sut->export_comment_author_data( $email, $page );

		$this->assertTrue( $result['done'] );
		$this->assertEmpty( $result['data'] );
	}

	/**
	 * Tests ::erase_data.
	 *
	 * @covers ::erase_data
	 */
	public function test_erase_data() {
		// Input data.
		$email = 'foo@bar.org';
		$page  = 1;

		// User mock.
		$user_id  = 66;
		$user     = m::mock( 'WP_User' );
		$user->ID = $user_id;

		Functions\expect( 'get_user_by' )->once()->with( 'email', $email )->andReturn( $user );

		$this->registered_user->shouldReceive( 'delete' )->once()->with( $user_id )->andReturn( 4 );
		$this->comment_author->shouldReceive( 'delete' )->once()->with( $email )->andReturn( 1 );

		$result = $this->sut->erase_data( $email, $page );

		$this->assertTrue( $result['done'] );
		$this->assertSame( 5, $result['items_removed'] );
		$this->assertSame( 0, $result['items_retained'] );
		$this->assertEmpty( $result['messages'] );
	}
}
