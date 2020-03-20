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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Components\Avatar_Handling;

use Avatar_Privacy\Core;
use Avatar_Privacy\Settings;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Exceptions\Avatar_Comment_Type_Exception;
use Avatar_Privacy\Tools\Network\Gravatar_Service;

/**
 * Avatar_Privacy\Components\Avatar_Handling unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Components\Avatar_Handling
 * @usesDefaultClass \Avatar_Privacy\Components\Avatar_Handling
 *
 * @uses ::__construct
 */
class Avatar_Handling_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The default icon URL.
	 *
	 * @var string
	 */
	const DEFAULT_ICON = 'url/to/default-icon.png';

	/**
	 * The blank icon URL.
	 *
	 * @var string
	 */
	const BLANK_ICON = 'url/to/blank.gif';

	/**
	 * The system-under-test.
	 *
	 * @var Avatar_Handling
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Required helper object.
	 *
	 * @var Gravatar_Service
	 */
	private $gravatar;

	/**
	 * Required helper object.
	 *
	 * @var Options
	 */
	private $options;

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
		Functions\when( 'absint' )->alias(
			function( $maybeint ) {
				return \abs( \intval( $maybeint ) );
			}
		);

		// Mock required helpers.
		$this->core     = m::mock( Core::class );
		$this->options  = m::mock( Options::class );
		$this->gravatar = m::mock( Gravatar_Service::class );

		$this->sut = m::mock( Avatar_Handling::class, [ $this->core, $this->options, $this->gravatar ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Avatar_Handling::class )->makePartial();

		$mock->__construct( $this->core, $this->options, $this->gravatar );

		$this->assert_attribute_same( $this->core, 'core', $mock );
		$this->assert_attribute_same( $this->options, 'options', $mock );
		$this->assert_attribute_same( $this->gravatar, 'gravatar', $mock );
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
		Filters\expectAdded( 'pre_get_avatar_data' )->once()->with( [ $this->sut, 'get_avatar_data' ], 10, 2 );

		$this->sut->shouldReceive( 'enable_presets' )->once();

		$this->assertNull( $this->sut->init() );
	}

	/**
	 * Tests ::enable_presets.
	 *
	 * @covers ::enable_presets
	 */
	public function test_enable_presets() {
		// Gravatar use default true.
		$this->core->shouldReceive( 'get_settings' )->once()->andReturn( [ Settings::GRAVATAR_USE_DEFAULT => true ] );

		Filters\expectAdded( 'avatar_privacy_gravatar_use_default' )->once()->with( '__return_true', 9, 0 );

		$this->assertNull( $this->sut->enable_presets() );

		// Gravatar use default false/empty.
		$this->core->shouldReceive( 'get_settings' )->once()->andReturn( [ Settings::GRAVATAR_USE_DEFAULT => false ] );

		Filters\expectAdded( 'avatar_privacy_gravatar_use_default' )->never();

		$this->assertNull( $this->sut->enable_presets() );
	}

	/**
	 * Provides data for testing get_avatar_data.
	 *
	 * @return array
	 */
	public function provide_get_avatar_data_data() {
		return [
			[ 5, 'foo@bar.org', false, false, '', '', self::DEFAULT_ICON ],
			[ 5, 'foo@bar.org', false, false, 'some/local/avatar.jpg', '', 'some/local/avatar.jpg' ],
			[ 5, 'foo@bar.org', true, false, 'some/local/avatar.jpg', '', self::DEFAULT_ICON ],
			[ 5, 'foo@bar.org', false, true, '', 'url/gravatar.png', 'url/gravatar.png' ],
			[ 5, 'foo@bar.org', true, true, '', 'url/gravatar.png', self::DEFAULT_ICON ],
			[ false, 'foo@bar.org', false, false, '', 'url/gravatar.png', self::DEFAULT_ICON ],
			[ false, 'foo@bar.org', true, false, '', '', self::DEFAULT_ICON ],
			[ false, 'foo@bar.org', false, true, '', 'url/gravatar.png', 'url/gravatar.png' ],
			[ false, 'foo@bar.org', true, true, '', 'url/gravatar.png', self::DEFAULT_ICON ],
		];
	}

	/**
	 * Tests ::get_avatar_data.
	 *
	 * @covers ::get_avatar_data
	 *
	 * @dataProvider provide_get_avatar_data_data
	 *
	 * @param  int|false $user_id              The user ID or false.
	 * @param  string    $email                The email address.
	 * @param  bool      $force_default        Whether to force a default icon.
	 * @param  bool      $should_show_gravatar If gravatars should be shown.
	 * @param  string    $local_url            The local URL (or '').
	 * @param  string    $gravatar_url         The gravatar URL (if $should_show_gravatar is true).
	 * @param  string    $result               The result URL.
	 */
	public function test_get_avatar_data( $user_id, $email, $force_default, $should_show_gravatar, $local_url, $gravatar_url, $result ) {
		// Input parameters.
		$id_or_email = (object) [ 'foo' => 'bar' ];
		$size        = 90;
		$default     = 'mm';
		$args        = [
			'default'       => $default,
			'size'          => $size,
			'force_default' => $force_default,
			'rating'        => 'g',
			'found_avatar'  => false,
		];
		$age         = 999;

		// Calculated values.
		$hash = 'a hash';

		$this->sut->shouldReceive( 'parse_id_or_email' )->once()->with( $id_or_email )->andReturn( [ $user_id, $email, $age ] );

		if ( ! empty( $user_id ) ) { // Registered user.
			$this->core->shouldReceive( 'get_user_hash' )->once()->with( $user_id )->andReturn( $hash );
			$this->core->shouldReceive( 'get_hash' )->never();

			if ( ! $force_default ) {
				$this->sut->shouldReceive( 'get_local_avatar_url' )->once()->with( $user_id, $hash, $size )->andReturn( $local_url );
			}
		} elseif ( ! empty( $email ) ) { // Anonymous comments.
			$this->core->shouldReceive( 'get_user_hash' )->never();
			$this->core->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );
		} else { // No valid data.
			$this->core->shouldReceive( 'get_user_hash' )->never();
			$this->core->shouldReceive( 'get_hash' )->never();
		}

		if ( empty( $user_id ) && empty( $email ) ) {
			$this->sut->shouldReceive( 'should_show_gravatar' )->never();
		} else {
			// Only if not short-circuiting.
			if ( $force_default || empty( $local_url ) ) {
				Functions\expect( 'includes_url' )->once()->with( 'images/blank.gif' )->andReturn( self::BLANK_ICON );
				Filters\expectApplied( 'avatar_privacy_default_icon_url' )->once()->with( self::BLANK_ICON, $hash, $size, [ 'default' => $default ] )->andReturn( self::DEFAULT_ICON );
			}
			if ( ! $force_default && empty( $local_url ) ) {
				$this->sut->shouldReceive( 'should_show_gravatar' )->once()->with( $user_id, $email, $id_or_email, $age, m::type( 'string' ) )->andReturn( $should_show_gravatar );

				if ( $should_show_gravatar ) {
					Filters\expectApplied( 'avatar_privacy_gravatar_icon_url' )->once()->with( self::DEFAULT_ICON, $hash, $size, m::type( 'array' ) )->andReturn( $gravatar_url );
				}
			}
		}

		$avatar_response = $this->sut->get_avatar_data( $args, $id_or_email );

		if ( isset( $result ) ) {
			$this->assertArrayHasKey( 'url', $avatar_response );
			$this->assertTrue( $avatar_response['found_avatar'] );
			$this->assertSame( $result, $avatar_response['url'] );
		} else {
			$this->assertArrayNotHasKey( 'url', $avatar_response );
		}
	}

	/**
	 * Provide data for testing should_show_gravatar.
	 *
	 * @return array
	 */
	public function provide_should_show_gravatar_data() {
		return [
			[ false, false, '', false ], // No validation.
			[ true, false, '', true ], // No validation.
			[ true, true, false, false ], // Failed validation.
			[ true, true, 'image/png', true ], // Succesful validaton.
		];
	}


	/**
	 * Tests ::get_avatar_data when $id_or_email is a comment with an non-avatar
	 * comment type..
	 *
	 * @since 2.3.4
	 *
	 * @covers ::get_avatar_data
	 *
	 * @dataProvider provide_get_avatar_data_data
	 *
	 * @param  int|false $user_id              The user ID or false.
	 * @param  string    $email                The email address.
	 * @param  bool      $force_default        Whether to force a default icon.
	 * @param  bool      $should_show_gravatar If gravatars should be shown.
	 * @param  string    $local_url            The local URL (or '').
	 * @param  string    $gravatar_url         The gravatar URL (if $should_show_gravatar is true).
	 * @param  string    $result               The result URL.
	 */
	public function test_get_avatar_data_invalid_comment_type( $user_id, $email, $force_default, $should_show_gravatar, $local_url, $gravatar_url, $result ) {
		// Input parameters.
		$id_or_email = m::mock( \WP_Comment::class );
		$size        = 90;
		$default     = 'mm';
		$args        = [
			'default'       => $default,
			'size'          => $size,
			'force_default' => $force_default,
			'rating'        => 'g',
			'found_avatar'  => false,
		];
		$age         = 999;

		// Calculated values.
		$hash = 'a hash';

		$this->sut->shouldReceive( 'parse_id_or_email' )->once()->with( $id_or_email )->andThrow( m::mock( Avatar_Comment_Type_Exception::class ) );

		$this->core->shouldReceive( 'get_user_hash' )->never();
		$this->core->shouldReceive( 'get_hash' )->never();

		$this->sut->shouldReceive( 'should_show_gravatar' )->never();

		Filters\expectApplied( 'avatar_privacy_default_icon_url' )->never();
		Filters\expectApplied( 'avatar_privacy_gravatar_icon_url' )->never();

		$avatar_response = $this->sut->get_avatar_data( $args, $id_or_email );

		$this->assertArrayHasKey( 'url', $avatar_response );
		$this->assertFalse( $avatar_response['url'] );
		$this->assertFalse( $avatar_response['found_avatar'] );
	}

	/**
	 * Tests ::should_show_gravatar.
	 *
	 * @covers ::should_show_gravatar
	 *
	 * @dataProvider provide_should_show_gravatar_data
	 *
	 * @param  bool   $show_gravatar The gravatar policy result.
	 * @param  bool   $check_enabled Whether gravatar checks are enabled.
	 * @param  string $mimetype      Actual MIME type.
	 * @param  bool   $expected      Expected result.
	 */
	public function test_should_show_gravatar( $show_gravatar, $check_enabled, $mimetype, $expected ) {
		$user_id     = 55;
		$email       = 'foobar@example.org';
		$id_or_email = 'original_id_or_email';
		$age         = 1000;
		$mime        = '';

		$this->sut->shouldReceive( 'determine_gravatar_policy' )->once()->with( $user_id, $email, $id_or_email )->andReturn( $show_gravatar );

		if ( $show_gravatar ) {
			Filters\expectApplied( 'avatar_privacy_enable_gravatar_check' )->once()->with( true, $email, $user_id )->andReturn( $check_enabled );

			if ( $check_enabled ) {
				$this->gravatar->shouldReceive( 'validate' )->once()->with( $email, $age )->andReturn( $mimetype );
			} else {
				$this->gravatar->shouldReceive( 'validate' )->never();
			}
		} else {
			Filters\expectApplied( 'avatar_privacy_enable_gravatar_check' )->never();
		}

		$this->assertSame( $expected, $this->invoke_method( $this->sut, 'should_show_gravatar', [ $user_id, $email, $id_or_email, $age, &$mime ] ) );
		if ( $show_gravatar && $check_enabled ) {
			$this->assertSame( $mimetype, $mime );
		}
	}

	/**
	 * Provide data for testing parse_id_or_email.
	 *
	 * @return array
	 */
	public function provide_parse_id_or_email_data() {
		// Mocked user.
		$user             = m::mock( 'WP_User' );
		$user->ID         = 99;
		$user->user_email = 'newbie@example.org';

		// Mocked post.
		$post                = m::mock( 'WP_Post' );
		$post->post_author   = 100;
		$post->post_date_gmt = '2018-01-01 01:01:01';

		// Mocked comment.
		$comment = m::mock( 'WP_Comment' );

		return [
			[ 55, 55, 'foo@bar.org', 0, 'ID' ], // Valid user ID.
			[ 66, false, '', 0, 'ID' ], // Invalid user ID.
			[ 'anon@foobar.org', false, 'anon@foobar.org', 0, 'email' ], // No user.
			[ 'anon@foobar.org', 10, 'anon@foobar.org', 0, 'email' ], // Mail belongs to user.
			[ $user, 99, 'newbie@example.org', 0, '' ],
			[ $post, 100, 'newbie@example.org', 666, 'ID' ],
			[ $comment, 101, 'newbie@example.org', 666, '' ],
		];
	}

	/**
	 * Tests ::parse_id_or_email.
	 *
	 * @covers ::parse_id_or_email
	 *
	 * @uses ::parse_id_or_email_unfiltered
	 *
	 * @dataProvider provide_parse_id_or_email_data
	 *
	 * @param  int|string|object $id_or_email Input object.
	 * @param  int|false         $user_id     Expected user ID.
	 * @param  string            $email       Expected email.
	 * @param  int               $age         Expected age.
	 * @param  string            $get_user_by 'email' or 'ID' or ''.
	 */
	public function test_parse_id_or_email( $id_or_email, $user_id, $email, $age, $get_user_by = '' ) {

		// Special provisions for posts and comments.
		$this->sut->shouldReceive( 'get_age' )->atMost()->once()->with( m::type( 'string' ) )->andReturn( $age );
		$this->sut->shouldReceive( 'parse_comment' )->atMost()->once()->with( $id_or_email )->andReturn( [ $user_id, $email, $age ] );

		// Check for user ID/email if empty.
		if ( ! empty( $get_user_by ) ) {
			$user = m::mock( 'WP_User' );

			if ( 'ID' === $get_user_by ) {
				$query = m::type( 'int' );

				if ( ! empty( $email ) ) {
					$user->user_email = $email;
				} else {
					$user = false;
				}
			} elseif ( 'email' === $get_user_by ) {
				$query = $email;

				if ( ! empty( $user_id ) ) {
					$user->ID = $user_id;
					$user->shouldReceive( 'get' )->once()->with( Core::ALLOW_ANONYMOUS_META_KEY )->andReturn( 'true' );
				} else {
					$user = false;
				}
			}

			Functions\expect( 'get_user_by' )->once()->with( $get_user_by, $query )->andReturn( $user );
		}

		// The filter should always run.
		Filters\expectApplied( 'avatar_privacy_parse_id_or_email' )->once()->with( m::type( 'array' ), $id_or_email )->andReturnFirstArg();

		$this->assertSame( [ $user_id, $email, $age ], $this->sut->parse_id_or_email( $id_or_email ) );
	}

	/**
	 * Provide data for testing parse_id_or_email_unfiltered.
	 *
	 * @return array
	 */
	public function provide_parse_id_or_email_unfiltered_data() {
		// Mocked user.
		$user             = m::mock( 'WP_User' );
		$user->ID         = 99;
		$user->user_email = 'newbie@example.org';

		// Mocked post.
		$post                = m::mock( 'WP_Post' );
		$post->post_author   = 100;
		$post->post_date_gmt = '2018-01-01 01:01:01';

		// Mocked comment.
		$comment = m::mock( 'WP_Comment' );

		return [
			[ 55, 55, '', 0 ], // Valid user ID.
			[ 'anon@foobar.org', false, 'anon@foobar.org', 0 ], // Anonymous comment.
			[ $user, $user->ID, $user->user_email, 0 ],
			[ $post, $post->post_author, '', 666 ],
			[ $comment, 101, 'newbie@example.org', 666 ], // ::parse_comment has a separate test.
		];
	}

	/**
	 * Tests ::parse_id_or_email_unfiltered.
	 *
	 * @covers ::parse_id_or_email_unfiltered
	 *
	 * @dataProvider provide_parse_id_or_email_unfiltered_data
	 *
	 * @param  int|string|object $id_or_email Input object.
	 * @param  int|false         $user_id     Expected user ID.
	 * @param  string            $email       Expected email.
	 * @param  int               $age         Expected age.
	 */
	public function test_parse_id_or_email_unfiltered( $id_or_email, $user_id, $email, $age ) {

		// Special provisions for posts and comments.
		$this->sut->shouldReceive( 'get_age' )->atMost()->once()->with( m::type( 'string' ) )->andReturn( $age );
		$this->sut->shouldReceive( 'parse_comment' )->atMost()->once()->with( $id_or_email )->andReturn( [ $user_id, $email, $age ] );

		$this->assertSame( [ $user_id, $email, $age ], $this->sut->parse_id_or_email_unfiltered( $id_or_email ) );
	}

	/**
	 * Provide data for testing parse_comment.
	 *
	 * @return array
	 */
	public function provide_parse_comment_data() {
		$now = \time();

		return [
			[
				[ 666, '', 100 ],
				$now,
				\gmdate( 'Y-m-d H:i:s', $now - 100 ),
				'comment',
				666,
				'foo@bar.org',
				[ 'comment' ],
			],
			[
				[ false, 'foo@bar.org', 999 ],
				$now,
				\gmdate( 'Y-m-d H:i:s', $now - 999 ),
				'comment',
				null,
				'foo@bar.org',
				[ 'comment' ],
			],
			[
				null,
				$now,
				\gmdate( 'Y-m-d H:i:s', $now - 999 ),
				'foobar',
				null,
				'foo@bar.org',
				[ 'comment' ],
			],
			[
				[ false, 'foo@bar.org', 999 ],
				$now,
				\gmdate( 'Y-m-d H:i:s', $now - 999 ),
				'foobar',
				null,
				'foo@bar.org',
				[ 'comment', 'foobar' ],
			],
			[
				[ false, '', 666 ],
				$now,
				\gmdate( 'Y-m-d H:i:s', $now - 666 ),
				'comment',
				null,
				null,
				[ 'comment' ],
			],
		];
	}

	/**
	 * Tests ::parse_comment.
	 *
	 * @covers ::parse_comment
	 *
	 * @dataProvider provide_parse_comment_data
	 *
	 * @param  array    $result               Required.
	 * @param  int      $now                  The current timestamp.
	 * @param  string   $comment_date_gmt     A date/timestamp string.
	 * @param  string   $comment_type         The comment_type property of the comment object. Default 'comment'.
	 * @param  string   $comment_user_id      The user_id property of the comment object.
	 * @param  string   $comment_author_email The comment_author_email property of the comment object.
	 * @param  string[] $avatar_comment_types Comment types we should display avatars for.
	 */
	public function test_parse_comment( $result, $now, $comment_date_gmt, $comment_type, $comment_user_id, $comment_author_email, array $avatar_comment_types ) {
			// Set up comment object.
		$comment                   = m::mock( 'WP_Comment' );
		$comment->comment_date_gmt = $comment_date_gmt;
		$comment->comment_type     = $comment_type;
		if ( isset( $comment_user_id ) ) {
			$comment->user_id = $comment_user_id;
		}
		if ( isset( $comment_author_email ) ) {
			$comment->comment_author_email = $comment_author_email;
		}

		Functions\expect( 'get_comment_type' )->once()->with( $comment )->andReturn( $comment_type );

		Filters\expectApplied( 'get_avatar_comment_types' )->once()->with( [ 'comment' ] )->andReturn( $avatar_comment_types );

		if ( ! empty( $result ) ) {
			$this->sut->shouldReceive( 'get_age' )->once()->with( $comment_date_gmt )->andReturn( $now - \strtotime( $comment_date_gmt ) );
		} else {
			$this->expectException( Avatar_Comment_Type_Exception::class );
		}

		$this->assertSame( $result, $this->invoke_method( $this->sut, 'parse_comment', [ $comment ] ) );
	}

	/**
	 * Tests ::get_age.
	 *
	 * @covers ::get_age
	 */
	public function test_get_age() {
		$age  = 555;
		$now  = \time();
		$date = \gmdate( 'Y-m-d H:i:s', $now - $age );

		Functions\expect( 'mysql2date' )->once()->with( 'U', $date )->andReturn( $now - $age );

		$result = $this->invoke_method( $this->sut, 'get_age', [ $date ] );
		$this->assertGreaterThanOrEqual( 550, $result );
		$this->assertLessThanOrEqual( 560, $result );
	}

	/**
	 * Tests ::get_local_avatar_url.
	 *
	 * @covers ::get_local_avatar_url
	 */
	public function test_get_local_avatar_url() {
		$user_id = 55;
		$hash    = 'some hash';
		$size    = 100;
		$result  = 'some URL';
		$local   = [
			'file' => 'a/file',
			'type' => 'image/png',
		];

		$this->core->shouldReceive( 'get_user_avatar' )->once()->with( $user_id )->andReturn( $local );
		Filters\expectApplied( 'avatar_privacy_user_avatar_icon_url' )->once()->with( '', $hash, $size, m::type( 'array' ) )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_local_avatar_url( $user_id, $hash, $size ) );
	}

	/**
	 * Tests ::get_local_avatar_url.
	 *
	 * @covers ::get_local_avatar_url
	 */
	public function test_get_local_avatar_url_no_avatar() {
		$user_id = 55;
		$hash    = 'some hash';
		$size    = 100;

		$this->core->shouldReceive( 'get_user_avatar' )->once()->with( $user_id )->andReturn( [] );
		Filters\expectApplied( 'avatar_privacy_user_avatar_icon_url' )->never();

		$this->assertSame( '', $this->sut->get_local_avatar_url( $user_id, $hash, $size ) );
	}

	/**
	 * Tests ::get_local_avatar_url.
	 *
	 * @covers ::get_local_avatar_url
	 */
	public function test_get_local_avatar_url_invalid_user_id() {
		$user_id = false;
		$hash    = 'some hash';
		$size    = 100;

		$this->core->shouldReceive( 'get_user_avatar' )->never();
		Filters\expectApplied( 'avatar_privacy_user_avatar_icon_url' )->never();

		$this->assertSame( '', $this->sut->get_local_avatar_url( $user_id, $hash, $size ) );
	}

	/**
	 * Tests ::determine_gravatar_policy.
	 *
	 * @covers ::determine_gravatar_policy
	 */
	public function test_determine_gravatar_policy_for_user_id() {
		// Input parameters.
		$user_id     = 10;
		$email       = 'user@example.org';
		$id_or_email = m::mock( 'WP_User' );

		// Query results.
		$meta_value = 'true';

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, Core::GRAVATAR_USE_META_KEY, true )->andReturn( $meta_value );
		Filters\expectApplied( 'avatar_privacy_gravatar_use_default' )->never();

		$this->assertTrue( $this->sut->determine_gravatar_policy( $user_id, $email, $id_or_email ) );
	}

	/**
	 * Tests ::determine_gravatar_policy.
	 *
	 * @covers ::determine_gravatar_policy
	 */
	public function test_determine_gravatar_policy_for_user_id_false() {
		// Input parameters.
		$user_id     = 10;
		$email       = 'user@example.org';
		$id_or_email = m::mock( 'WP_User' );

		// Query results.
		$meta_value = 'false';

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, Core::GRAVATAR_USE_META_KEY, true )->andReturn( $meta_value );
		Filters\expectApplied( 'avatar_privacy_gravatar_use_default' )->never();

		$this->assertFalse( $this->sut->determine_gravatar_policy( $user_id, $email, $id_or_email ) );
	}

	/**
	 * Tests ::determine_gravatar_policy.
	 *
	 * @covers ::determine_gravatar_policy
	 */
	public function test_determine_gravatar_policy_for_user_id_with_default() {
		// Input parameters.
		$user_id     = 10;
		$email       = 'user@example.org';
		$id_or_email = m::mock( 'WP_User' );

		// Query results.
		$meta_value = '';

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, Core::GRAVATAR_USE_META_KEY, true )->andReturn( $meta_value );
		Filters\expectApplied( 'avatar_privacy_gravatar_use_default' )->once()->with( false, $id_or_email )->andReturn( true );

		$this->assertTrue( $this->sut->determine_gravatar_policy( $user_id, $email, $id_or_email ) );
	}

	/**
	 * Tests ::determine_gravatar_policy.
	 *
	 * @covers ::determine_gravatar_policy
	 */
	public function test_determine_gravatar_policy_for_email() {
		// Input parameters.
		$user_id     = false;
		$email       = 'anonymous@example.org';
		$id_or_email = $email;

		$this->core->shouldReceive( 'comment_author_allows_gravatar_use' )->once()->with( $email )->andReturn( true );
		$this->core->shouldReceive( 'comment_author_has_gravatar_policy' )->never();
		Filters\expectApplied( 'avatar_privacy_gravatar_use_default' )->never();

		$this->assertTrue( $this->sut->determine_gravatar_policy( $user_id, $email, $id_or_email ) );
	}

	/**
	 * Tests ::determine_gravatar_policy.
	 *
	 * @covers ::determine_gravatar_policy
	 */
	public function test_determine_gravatar_policy_for_email_default() {
		// Input parameters.
		$user_id     = false;
		$email       = 'anonymous@example.org';
		$id_or_email = $email;

		$this->core->shouldReceive( 'comment_author_allows_gravatar_use' )->once()->with( $email )->andReturn( false );
		$this->core->shouldReceive( 'comment_author_has_gravatar_policy' )->once()->with( $email )->andReturn( false );
		Filters\expectApplied( 'avatar_privacy_gravatar_use_default' )->once()->with( false, $id_or_email )->andReturn( true );

		$this->assertTrue( $this->sut->determine_gravatar_policy( $user_id, $email, $id_or_email ) );
	}
}
