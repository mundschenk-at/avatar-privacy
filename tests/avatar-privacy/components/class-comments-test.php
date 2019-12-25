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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Components\Comments;

use Avatar_Privacy\Core;

/**
 * Avatar_Privacy\Components\Comments unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Components\Comments
 * @usesDefaultClass \Avatar_Privacy\Components\Comments
 *
 * @uses ::__construct
 */
class Comments_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Comments
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$filesystem = [
			'plugin'    => [
				'public' => [
					'partials' => [
						'comments' => [
							'use-gravatar.php' => 'USE_GRAVATAR',
						],
					],
				],
			],
			'uploads'   => [
				'some.png'   => '',
				'some_1.png' => '',
				'some_2.png' => '',
				'some.gif'   => '',
			],
		];

		// Set up virtual filesystem.
		$root = vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Mock required helpers.
		$this->core = m::mock( Core::class );

		$this->sut = m::mock( Comments::class, [ $this->core ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Comments::class )->makePartial();

		$mock->__construct( $this->core );

		$this->assert_attribute_same( $this->core, 'core', $mock );
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
		Filters\expectAdded( 'comment_form_fields' )->once()->with( [ $this->sut, 'comment_form_fields' ] );
		Actions\expectAdded( 'comment_post' )->once()->with( [ $this->sut, 'comment_post' ], 10, 2 );

		Functions\expect( 'has_action' )->once()->with( 'set_comment_cookies', 'wp_set_comment_cookies' )->andReturn( true );
		Actions\expectAdded( 'set_comment_cookies' )->once()->with( [ $this->sut, 'set_comment_cookies' ], 10, 3 );

		$this->assertNull( $this->sut->init() );
	}

	/**
	 * Tests ::comment_form_fields.
	 *
	 * @covers ::comment_form_fields
	 *
	 * @uses ::get_gravatar_checkbox
	 */
	public function test_comment_form_fields() {
		// Input parameters.
		$fields = [
			'name'  => '<name markup>',
			'email' => '<email markup>',
		];

		// Expected results.
		$expected_order1 = [ 'name', 'use_gravatar', 'email' ];
		$expected_order3 = [ 'use_gravatar', 'name', 'email' ];

		Functions\expect( 'is_user_logged_in' )->times( 3 )->andReturn( false );

		$this->sut->shouldReceive( 'get_position' )->times( 3 )->with( $fields )->andReturn( [ 'before', 'email' ], [ 'after', 'name' ], [ 'before', 'name' ] );

		Filters\expectApplied( 'avatar_privacy_use_gravatar_position' )->times( 3 )->with( m::type( 'array' ) );

		$result1 = $this->sut->comment_form_fields( $fields );
		$result2 = $this->sut->comment_form_fields( $fields );
		$result3 = $this->sut->comment_form_fields( $fields );

		$this->assertCount( 3, $result1 );
		$this->assertCount( 3, $result3 );
		$this->assertSame( $result1, $result2 );
		$this->assertNotSame( $result1, $result3 );

		$this->assertSame( $expected_order1, \array_keys( $result1 ) );
		$this->assertSame( $expected_order3, \array_keys( $result3 ) );
	}

	/**
	 * Tests ::comment_form_fields.
	 *
	 * @covers ::comment_form_fields
	 *
	 * @uses ::get_gravatar_checkbox
	 */
	public function test_comment_form_fields_logged_in() {
		// Input parameters.
		$fields = [
			'name'  => '<name markup>',
			'email' => '<email markup>',
		];

		Functions\expect( 'is_user_logged_in' )->once()->andReturn( true );

		$this->sut->shouldReceive( 'get_position' )->never();

		Filters\expectApplied( 'avatar_privacy_use_gravatar_position' )->never();

		$this->assertSame( $fields, $this->sut->comment_form_fields( $fields ) );
	}

	/**
	 * Tests ::comment_form_fields.
	 *
	 * @covers ::comment_form_fields
	 *
	 * @uses ::get_gravatar_checkbox
	 */
	public function test_comment_form_fields_use_gravatar_field_exists() {
		// Input parameters.
		$fields = [
			'name'         => '<name markup>',
			'email'        => '<email markup>',
			'use_gravatar' => '<gravatar markup>',
		];

		Functions\expect( 'is_user_logged_in' )->once()->andReturn( false );

		$this->sut->shouldReceive( 'get_position' )->never();

		Filters\expectApplied( 'avatar_privacy_use_gravatar_position' )->never();

		$this->assertSame( $fields, $this->sut->comment_form_fields( $fields ) );
	}

	/**
	 * Tests ::comment_form_fields.
	 *
	 * @covers ::comment_form_fields
	 *
	 * @uses ::get_gravatar_checkbox
	 */
	public function test_comment_form_fields_invalid_field() {
		// Input parameters.
		$fields = [
			'name'  => '<name markup>',
			'email' => '<email markup>',
		];

		// Expected results.
		$expected_order = [ 'name', 'email', 'use_gravatar' ];

		Functions\expect( 'is_user_logged_in' )->once()->andReturn( false );

		$this->sut->shouldReceive( 'get_position' )->once()->with( $fields )->andReturn( [ 'before', 'foobar' ] );

		Filters\expectApplied( 'avatar_privacy_use_gravatar_position' )->once()->with( m::type( 'array' ) );

		$this->assertSame( $expected_order, \array_keys( $this->sut->comment_form_fields( $fields ) ) );
	}

	/**
	 * Provides data for testing get_position.
	 *
	 * @return array
	 */
	public function provide_get_position_data() {
		return [
			[
				[
					'name'    => '<name markup>',
					'email'   => '<email markup>',
					'url'     => '<url markup>',
					'cookies' => '<cookie checkbox markup>',
				],
				'before',
				'cookies',
			],
			[
				[
					'name'    => '<name markup>',
					'email'   => '<email markup>',
					'url'     => '<url markup>',
				],
				'after',
				'url',
			],
			[
				[
					'name'    => '<name markup>',
					'email'   => '<email markup>',
				],
				'after',
				'email',
			],
			[
				[
					'name' => '<name markup>',
					'foo'  => '<foo markup>',
					'bar'  => '<bar markup>',
				],
				'after',
				'bar',
			],
		];
	}

	/**
	 * Tests ::get_position.
	 *
	 * @covers ::get_position
	 *
	 * @dataProvider provide_get_position_data
	 *
	 * @param  string[] $fields          The comment fields markup.
	 * @param  string   $before_or_after Expected result (either 'before' or 'after').
	 * @param  string   $insert_position Expected result ($fields index).
	 */
	public function test_get_position( $fields, $before_or_after, $insert_position ) {
		$this->assertSame( [ $before_or_after, $insert_position ], $this->sut->get_position( $fields ) );
	}

	/**
	 * Tests ::get_gravatar_checkbox.
	 *
	 * @covers ::get_gravatar_checkbox
	 */
	public function test_get_gravatar_checkbox() {
		$this->assertSame( 'USE_GRAVATAR', Comments::get_gravatar_checkbox() );
	}

	/**
	 * Tests ::comment_post.
	 *
	 * @covers ::comment_post
	 */
	public function test_comment_post() {
		// Input parameters.
		$comment_id       = 777;
		$comment_approved = 1;

		// Calculated.
		$email                         = 'foo@bar.org';
		$comment                       = m::mock( 'WP_Comment' );
		$comment->comment_type         = '';
		$comment->comment_author_email = $email;

		// Set up request data.
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_POST = [ Comments::CHECKBOX_FIELD_NAME => 'true' ];

		Functions\expect( 'get_comment' )->once()->with( $comment_id )->andReturn( $comment );
		Functions\expect( 'get_user_by' )->once()->with( 'email', $email )->andReturn( false );

		$this->core->shouldReceive( 'update_comment_author_gravatar_use' )->once()->with( $email, $comment_id, true );

		$this->assertNull( $this->sut->comment_post( $comment_id, $comment_approved ) );
	}

	/**
	 * Tests ::comment_post.
	 *
	 * @covers ::comment_post
	 */
	public function test_comment_post_spam() {
		// Input parameters.
		$comment_id       = 777;
		$comment_approved = 'spam';

		// Set up request data.
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_POST = [ Comments::CHECKBOX_FIELD_NAME => 'true' ];

		Functions\expect( 'get_comment' )->never();
		Functions\expect( 'get_user_by' )->never();

		$this->core->shouldReceive( 'update_comment_author_gravatar_use' )->never();

		$this->assertNull( $this->sut->comment_post( $comment_id, $comment_approved ) );
	}

	/**
	 * Tests ::comment_post.
	 *
	 * @covers ::comment_post
	 */
	public function test_comment_post_no_email() {
		// Input parameters.
		$comment_id       = 777;
		$comment_approved = 1;

		// Calculated.
		$email                         = '';
		$comment                       = m::mock( 'WP_Comment' );
		$comment->comment_type         = '';
		$comment->comment_author_email = $email;

		// Set up request data.
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_POST = [ Comments::CHECKBOX_FIELD_NAME => 'true' ];

		Functions\expect( 'get_comment' )->once()->with( $comment_id )->andReturn( $comment );
		Functions\expect( 'get_user_by' )->never();

		$this->core->shouldReceive( 'update_comment_author_gravatar_use' )->never();

		$this->assertNull( $this->sut->comment_post( $comment_id, $comment_approved ) );
	}

	/**
	 * Tests ::comment_post.
	 *
	 * @covers ::comment_post
	 */
	public function test_comment_post_pingback() {
		// Input parameters.
		$comment_id       = 777;
		$comment_approved = 1;

		// Calculated.
		$email                         = 'foo@bar.org';
		$comment                       = m::mock( 'WP_Comment' );
		$comment->comment_type         = 'pingback';
		$comment->comment_author_email = $email;

		// Set up request data.
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_POST = [ Comments::CHECKBOX_FIELD_NAME => 'true' ];

		Functions\expect( 'get_comment' )->once()->with( $comment_id )->andReturn( $comment );
		Functions\expect( 'get_user_by' )->never();

		$this->core->shouldReceive( 'update_comment_author_gravatar_use' )->never();

		$this->assertNull( $this->sut->comment_post( $comment_id, $comment_approved ) );
	}

	/**
	 * Tests ::comment_post.
	 *
	 * @covers ::comment_post
	 */
	public function test_comment_post_registered_user() {
		// Input parameters.
		$comment_id       = 777;
		$comment_approved = 1;

		// Calculated.
		$email                         = 'foo@bar.org';
		$comment                       = m::mock( 'WP_Comment' );
		$comment->comment_type         = '';
		$comment->comment_author_email = $email;

		// Set up request data.
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_POST = [ Comments::CHECKBOX_FIELD_NAME => 'true' ];

		Functions\expect( 'get_comment' )->once()->with( $comment_id )->andReturn( $comment );
		Functions\expect( 'get_user_by' )->once()->with( 'email', $email )->andReturn( m::mock( 'WP_User' ) );

		$this->core->shouldReceive( 'update_comment_author_gravatar_use' )->never();

		$this->assertNull( $this->sut->comment_post( $comment_id, $comment_approved ) );
	}

	/**
	 * Tests ::set_comment_cookies.
	 *
	 * @covers ::set_comment_cookies
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @requires extension xdebug
	 */
	public function test_set_comment_cookies() {
		$comment                       = m::mock( 'WP_Comment' );
		$comment->comment_author_email = 'foo@bar.org';
		$user                          = m::mock( 'WP_User' );
		$consent                       = true;

		// Calculated.
		$use_gravatar    = true;
		$filter_lifetime = 1000;
		$home_url        = 'https://some.blog';

		$user->shouldReceive( 'exists' )->once()->andReturn( false );

		$this->core->shouldReceive( 'comment_author_allows_gravatar_use' )->once()->with( $comment->comment_author_email )->andReturn( $use_gravatar );

		Filters\expectApplied( 'comment_cookie_lifetime' )->once()->with( 30000000 )->andReturn( $filter_lifetime );
		Functions\expect( 'home_url' )->once()->andReturn( $home_url );
		Functions\expect( 'wp_parse_url' )->once()->with( $home_url, PHP_URL_SCHEME )->andReturnUsing( 'parse_url' );

		$this->assertNull( $this->sut->set_comment_cookies( $comment, $user, $consent ) );

		$headers = \xdebug_get_headers();
		$regex   = '/Set\-Cookie: comment_use_gravatar_somehash\=' . (int) $use_gravatar . '; expires\=[^;]+; Max\-Age\=' . $filter_lifetime . '; path\=' . \preg_quote( COOKIEPATH, '/' ) . '; domain=' . \preg_quote( COOKIE_DOMAIN, '/' ) . '/';

		$this->assertRegexp( $regex, $headers[0] );
	}

	/**
	 * Tests ::set_comment_cookies.
	 *
	 * @covers ::set_comment_cookies
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @requires extension xdebug
	 */
	public function test_set_comment_cookies_no_consent() {
		$comment = m::mock( 'WP_Comment' );
		$user    = m::mock( 'WP_User' );
		$consent = false;

		$user->shouldReceive( 'exists' )->once()->andReturn( false );

		Filters\expectApplied( 'comment_cookie_lifetime' )->never();

		$this->assertNull( $this->sut->set_comment_cookies( $comment, $user, $consent ) );

		$headers = \xdebug_get_headers();
		$regex   = '/Set\-Cookie: comment_use_gravatar_somehash\=0; expires\=[^;]+; Max\-Age\=0; path\=' . \preg_quote( COOKIEPATH, '/' ) . '; domain=' . \preg_quote( COOKIE_DOMAIN, '/' ) . '/';

		if ( \version_compare( PHP_VERSION, '7.0.0', '<' ) ) {
			// Workaround PHP bug #72071:
			// A bug in setcookie allows negative Max-Age values.
			$regex = \str_replace( 'Max\-Age\=0;', 'Max\-Age\=\-31536000;', $regex );
		}

		// Cookie is "unset".
		$this->assertRegexp( $regex, $headers[0] );
	}

	/**
	 * Tests ::set_comment_cookies.
	 *
	 * @covers ::set_comment_cookies
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @requires extension xdebug
	 */
	public function test_set_comment_cookies_user_exists() {
		$comment = m::mock( 'WP_Comment' );
		$user    = m::mock( 'WP_User' );
		$consent = false;

		$user->shouldReceive( 'exists' )->once()->andReturn( true );

		Filters\expectApplied( 'comment_cookie_lifetime' )->never();

		$this->assertNull( $this->sut->set_comment_cookies( $comment, $user, $consent ) );

		// No cookies set.
		$this->assertEmpty( \xdebug_get_headers() );
	}
}
