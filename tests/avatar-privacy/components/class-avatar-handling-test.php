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

use Avatar_Privacy\Components\Avatar_Handling;

use Avatar_Privacy\Core\Comment_Author_Fields;
use Avatar_Privacy\Core\User_Fields;
use Avatar_Privacy\Core\Settings;
use Avatar_Privacy\Exceptions\Avatar_Comment_Type_Exception;
use Avatar_Privacy\Tools\Network\Gravatar_Service;
use Avatar_Privacy\Tools\Network\Remote_Image_Service;

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
	 * @var Avatar_Handling&m\MockInterface
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Settings&m\MockInterface
	 */
	private $settings;


	/**
	 * The user data helper.
	 *
	 * @var User_Fields&m\MockInterface
	 */
	private $registered_user;

	/**
	 * The comment author data helper.
	 *
	 * @var Comment_Author_Fields&m\MockInterface
	 */
	private $comment_author;

	/**
	 * Required helper object.
	 *
	 * @var Gravatar_Service&m\MockInterface
	 */
	private $gravatar;

	/**
	 * Required helper object.
	 *
	 * @var Remote_Image_Service&m\MockInterface
	 */
	private $remote_images;

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
			function ( $maybeint ) {
				return \abs( \intval( $maybeint ) );
			}
		);

		// Mock required helpers.
		$this->settings        = m::mock( Settings::class );
		$this->registered_user = m::mock( User_Fields::class );
		$this->comment_author  = m::mock( Comment_Author_Fields::class );
		$this->gravatar        = m::mock( Gravatar_Service::class );
		$this->remote_images   = m::mock( Remote_Image_Service::class );

		$this->sut = m::mock( Avatar_Handling::class, [ $this->settings, $this->registered_user, $this->comment_author, $this->gravatar, $this->remote_images ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Avatar_Handling::class )->makePartial();

		$mock->__construct( $this->settings, $this->registered_user, $this->comment_author, $this->gravatar, $this->remote_images );

		$this->assert_attribute_same( $this->settings, 'settings', $mock );
		$this->assert_attribute_same( $this->registered_user, 'registered_user', $mock );
		$this->assert_attribute_same( $this->comment_author, 'comment_author', $mock );
		$this->assert_attribute_same( $this->gravatar, 'gravatar', $mock );
		$this->assert_attribute_same( $this->remote_images, 'remote_images', $mock );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Filters\expectAdded( 'avatar_privacy_allow_remote_avatar_url' )->once()->with( '__return_true', 9, 0 );

		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'setup_avatar_filters' ] );
		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'enable_presets' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::setup_avatar_filters.
	 *
	 * @covers ::setup_avatar_filters
	 */
	public function test_setup_avatar_filters() {
		$priority = 666;

		Filters\expectApplied( 'avatar_privacy_pre_get_avatar_data_filter_priority' )->once()->with( 9999 )->andReturn( $priority );
		Filters\expectAdded( 'pre_get_avatar_data' )->once()->with( [ $this->sut, 'get_avatar_data' ], $priority, 2 );

		$this->assertNull( $this->sut->setup_avatar_filters() );
	}

	/**
	 * Tests ::enable_presets.
	 *
	 * @covers ::enable_presets
	 */
	public function test_enable_presets() {
		// Gravatar use default true.
		$this->settings->shouldReceive( 'get' )->once()->with( Settings::GRAVATAR_USE_DEFAULT )->andReturn( true );

		Filters\expectAdded( 'avatar_privacy_gravatar_use_default' )->once()->with( '__return_true', 9, 0 );

		$this->assertNull( $this->sut->enable_presets() );

		// Gravatar use default false/empty.
		$this->settings->shouldReceive( 'get' )->once()->with( Settings::GRAVATAR_USE_DEFAULT )->andReturn( false );

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
			[ 5, 'foo@bar.org', false, false, '', '', null, self::DEFAULT_ICON ],
			[ 5, 'foo@bar.org', false, false, 'some/local/avatar.jpg', '', null, 'some/local/avatar.jpg' ],
			[ 5, 'foo@bar.org', true, false, 'some/local/avatar.jpg', '', null, self::DEFAULT_ICON ],
			[ 5, 'foo@bar.org', false, true, '', 'url/gravatar.png', null, 'url/gravatar.png' ],
			[ 5, 'foo@bar.org', true, true, '', 'url/gravatar.png', null, self::DEFAULT_ICON ],
			[ false, 'foo@bar.org', false, false, '', 'url/gravatar.png', null, self::DEFAULT_ICON ],
			[ false, 'foo@bar.org', true, false, '', '', null, self::DEFAULT_ICON ],
			[ false, 'foo@bar.org', false, true, '', 'url/gravatar.png', null, 'url/gravatar.png' ],
			[ false, 'foo@bar.org', true, true, '', 'url/gravatar.png', null, self::DEFAULT_ICON ],
		];
	}

	/**
	 * Tests ::get_avatar_data.
	 *
	 * @covers ::get_avatar_data
	 *
	 * @dataProvider provide_get_avatar_data_data
	 *
	 * @param  int|false   $user_id              The user ID or false.
	 * @param  string      $email                The email address.
	 * @param  bool        $force_default        Whether to force a default icon.
	 * @param  bool        $should_show_gravatar If gravatars should be shown.
	 * @param  string      $local_url            The local URL (or '').
	 * @param  string      $gravatar_url         The gravatar URL (if $should_show_gravatar is true).
	 * @param  string|null $legacy_url           A legacy URL set by another plugin.
	 * @param  string      $result               The result URL.
	 */
	public function test_get_avatar_data( $user_id, $email, $force_default, $should_show_gravatar, $local_url, $gravatar_url, $legacy_url, $result ) {
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

		if ( ! empty( $legacy_url ) ) {
			$args['url'] = $legacy_url;
		}

		$this->sut->shouldReceive( 'parse_id_or_email' )->once()->with( $id_or_email )->andReturn( [ $user_id, $email, $age ] );

		if ( ! empty( $user_id ) ) { // Registered user.
			$this->registered_user->shouldReceive( 'get_hash' )->once()->with( $user_id )->andReturn( $hash );
			$this->comment_author->shouldReceive( 'get_hash' )->never();
		} elseif ( ! empty( $email ) ) { // Anonymous comments.
			$this->registered_user->shouldReceive( 'get_hash' )->never();
			$this->comment_author->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );
		} else { // No valid data.
			$this->registered_user->shouldReceive( 'get_hash' )->never();
			$this->comment_author->shouldReceive( 'get_hash' )->never();
		}

		if ( $force_default || ( empty( $local_url ) && empty( $should_show_gravatar ) && empty( $legacy_url ) ) ) {
			$this->sut->shouldReceive( 'get_default_icon_url' )->once()->with( $hash, $default, $size )->andReturn( self::DEFAULT_ICON );
		} else {
			$this->sut->shouldReceive( 'get_default_icon_url' )->never();
		}

		if ( ! $force_default ) {
			if ( ! empty( $user_id ) ) {
				$this->sut->shouldReceive( 'get_local_avatar_url' )->once()->with( $user_id, $hash, $size, m::type( 'bool' ) )->andReturn( $local_url );
			} else {
				$this->sut->shouldReceive( 'get_local_avatar_url' )->never();
			}

			if ( empty( $local_url ) ) {
				$this->sut->shouldReceive( 'should_show_gravatar' )->once()->with( $user_id, $email, $id_or_email, $age, m::type( 'string' ) )->andReturn( $should_show_gravatar );
			} else {
				$this->sut->shouldReceive( 'should_show_gravatar' )->never();
			}

			if ( $should_show_gravatar ) {
				$this->sut->shouldReceive( 'get_gravatar_url' )->once()->with( $user_id, $email, $hash, $size, $args['rating'], m::type( 'string' ) )->andReturn( $gravatar_url );
			} else {
				$this->sut->shouldReceive( 'get_gravatar_url' )->never();

				if ( ! empty( $legacy_url ) ) {
					$this->remote_images->shouldReceive( 'validate_image_url' )->once()->with( $legacy_url, 'avatar' )->andReturn( true );
					$this->sut->shouldReceive( 'get_legacy_icon_url' )->once()->with( $legacy_url, $size )->andReturn( $legacy_url );
				}
			}
		}

		// Go for it!
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

		$this->sut->shouldReceive( 'parse_id_or_email' )->once()->with( $id_or_email )->andThrow( m::mock( Avatar_Comment_Type_Exception::class ) );

		$this->registered_user->shouldReceive( 'get_hash' )->never();
		$this->comment_author->shouldReceive( 'get_hash' )->never();

		$this->sut->shouldReceive( 'should_show_gravatar' )->never();

		$this->sut->shouldReceive( 'get_default_icon_url' )->never();
		$this->sut->shouldReceive( 'get_gravatar_url' )->never();
		$this->remote_images->shouldReceive( 'validate_image_url' )->never();
		$this->sut->shouldReceive( 'get_legacy_icon_url' )->never();

		$avatar_response = $this->sut->get_avatar_data( $args, $id_or_email );

		$this->assertArrayHasKey( 'url', $avatar_response );
		$this->assertFalse( $avatar_response['url'] );
		$this->assertFalse( $avatar_response['found_avatar'] );
	}
	/**
	 * Tests ::get_avatar_data.
	 *
	 * @covers ::get_avatar_data
	 */
	public function test_get_avatar_data_remote_icon() {
		// Input parameters.
		$id_or_email = (object) [ 'foo' => 'bar' ];
		$size        = 90;
		$default     = 'mm';
		$remote_url  = 'https://some.remote/url';
		$args        = [
			'default'       => $default,
			'size'          => $size,
			'force_default' => false,
			'rating'        => 'g',
			'found_avatar'  => false,
			'url'           => $remote_url,
		];
		$user_id     = false;
		$email       = 'some@email';
		$hash        = 'fake hash';
		$age         = 999;

		$this->sut->shouldReceive( 'parse_id_or_email' )->once()->with( $id_or_email )->andReturn( [ $user_id, $email, $age ] );

		$this->registered_user->shouldReceive( 'get_hash' )->never();
		$this->comment_author->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );

		$this->sut->shouldReceive( 'should_show_gravatar' )->once()->with( $user_id, $email, $id_or_email, $age, m::type( 'string' ) )->andReturn( false );
		$this->remote_images->shouldReceive( 'validate_image_url' )->once()->with( $remote_url, 'avatar' )->andReturn( true );
		$this->sut->shouldReceive( 'get_legacy_icon_url' )->once()->with( $remote_url, $size )->andReturn( $remote_url );
		$this->sut->shouldReceive( 'get_default_icon_url' )->never();

		// Call method.
		$avatar_response = $this->sut->get_avatar_data( $args, $id_or_email );

		// Assert result.
		$this->assertArrayHasKey( 'url', $avatar_response );
		$this->assertTrue( $avatar_response['found_avatar'] );
		$this->assertSame( $remote_url, $avatar_response['url'] );
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
			[ true, true, 'image/png', true ], // Successful validaton.
		];
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

				Functions\expect( 'get_user_by' )->once()->with( 'ID', $query )->andReturn( $user );

			} elseif ( 'email' === $get_user_by ) {
				$query = $email;

				if ( ! empty( $user_id ) ) {
					$user->ID = $user_id;
					$this->registered_user->shouldReceive( 'allows_anonymous_commenting' )->once()->with( $user_id )->andReturn( 'true' );
				} else {
					$user = false;
				}

				$this->registered_user->shouldReceive( 'get_user_by_email' )->once()->with( $query )->andReturn( $user );
			}
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

		$this->registered_user->shouldReceive( 'get_local_avatar' )->once()->with( $user_id )->andReturn( $local );
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

		$this->registered_user->shouldReceive( 'get_local_avatar' )->once()->with( $user_id )->andReturn( [] );
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

		$this->registered_user->shouldReceive( 'get_local_avatar' )->never();
		Filters\expectApplied( 'avatar_privacy_user_avatar_icon_url' )->never();

		$this->assertSame( '', $this->sut->get_local_avatar_url( $user_id, $hash, $size ) );
	}

	/**
	 * Tests ::get_default_icon_url.
	 *
	 * @covers ::get_default_icon_url
	 */
	public function test_get_default_icon_url() {
		$hash        = 'some hash';
		$type        = 'foobar';
		$size        = 100;
		$blank_url   = 'https://example.org/images/blank.gif';
		$result      = 'https://example.org/images/default_image.png';
		$filter_args = [
			'default' => $type,
			'type'    => $type,
		];

		Functions\expect( 'includes_url' )->once()->with( 'images/blank.gif' )->andReturn( $blank_url );
		Filters\expectApplied( 'avatar_privacy_default_icon_url' )->once()->with( $blank_url, $hash, $size, $filter_args )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_default_icon_url( $hash, $type, $size ) );
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

		$this->registered_user->shouldReceive( 'allows_gravatar_use' )->once()->with( $user_id )->andReturn( true );
		$this->registered_user->shouldReceive( 'has_gravatar_policy' )->once()->with( $user_id )->andReturn( true );
		Filters\expectApplied( 'avatar_privacy_gravatar_use_default' )->never();

		$this->comment_author->shouldReceive( 'allows_gravatar_use' )->never();
		$this->comment_author->shouldReceive( 'has_gravatar_policy' )->never();
		$this->comment_author->shouldReceive( 'update_hash' )->never();

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

		$this->registered_user->shouldReceive( 'allows_gravatar_use' )->once()->with( $user_id )->andReturn( false );
		$this->registered_user->shouldReceive( 'has_gravatar_policy' )->once()->with( $user_id )->andReturn( true );
		Filters\expectApplied( 'avatar_privacy_gravatar_use_default' )->never();

		$this->comment_author->shouldReceive( 'allows_gravatar_use' )->never();
		$this->comment_author->shouldReceive( 'has_gravatar_policy' )->never();
		$this->comment_author->shouldReceive( 'update_hash' )->never();

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

		$this->registered_user->shouldReceive( 'allows_gravatar_use' )->once()->with( $user_id )->andReturn( false );
		$this->registered_user->shouldReceive( 'has_gravatar_policy' )->once()->with( $user_id )->andReturn( false );
		Filters\expectApplied( 'avatar_privacy_gravatar_use_default' )->once()->with( false, $id_or_email )->andReturn( true );

		$this->comment_author->shouldReceive( 'allows_gravatar_use' )->never();
		$this->comment_author->shouldReceive( 'has_gravatar_policy' )->never();
		$this->comment_author->shouldReceive( 'update_hash' )->never();

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

		$this->registered_user->shouldReceive( 'allows_gravatar_use' )->never();
		$this->registered_user->shouldReceive( 'has_gravatar_policy' )->never();

		$this->comment_author->shouldReceive( 'allows_gravatar_use' )->once()->with( $email )->andReturn( true );
		$this->comment_author->shouldReceive( 'has_gravatar_policy' )->never();
		Filters\expectApplied( 'avatar_privacy_gravatar_use_default' )->never();
		$this->comment_author->shouldReceive( 'update_hash' )->never();

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

		$this->registered_user->shouldReceive( 'allows_gravatar_use' )->never();
		$this->registered_user->shouldReceive( 'has_gravatar_policy' )->never();

		$this->comment_author->shouldReceive( 'allows_gravatar_use' )->once()->with( $email )->andReturn( false );
		$this->comment_author->shouldReceive( 'has_gravatar_policy' )->once()->with( $email )->andReturn( false );
		Filters\expectApplied( 'avatar_privacy_gravatar_use_default' )->once()->with( false, $id_or_email )->andReturn( true );
		$this->comment_author->shouldReceive( 'update_hash' )->once()->with( $email );

		$this->assertTrue( $this->sut->determine_gravatar_policy( $user_id, $email, $id_or_email ) );
	}

	/**
	 * Tests ::get_gravatar_url.
	 *
	 * @covers ::get_gravatar_url
	 */
	public function test_get_gravatar_url() {
		$user_id  = 666;
		$email    = 'some email';
		$rating   = 'pg';
		$mimetype = 'image/gif';
		$hash     = 'some hash';
		$size     = 100;
		$result   = 'https://example.org/images/cached_gravatar-100.gif';

		Filters\expectApplied( 'avatar_privacy_gravatar_icon_url' )->once()->with( '', $hash, $size, m::type( 'array' ) )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_gravatar_url( $user_id, $email, $hash, $size, $rating, $mimetype ) );
	}

	/**
	 * Provides data for testing ::is_valid_image_url.
	 *
	 * @return array
	 */
	public function provide_is_valid_image_url_data() {
		return [
			[ 'https://example.org/images/blank.gif', true ],
			[ 'https://example.org/images/blank.gif', false ],
			[ 'https://gravatar.com/some-gravatar.gif', false ],
		];
	}

	/**
	 * Tests ::is_valid_image_url.
	 *
	 * @covers ::is_valid_image_url
	 *
	 * @dataProvider provide_is_valid_image_url_data
	 *
	 * @param  string $url    The image URL.
	 * @param  bool   $result The expected result.
	 */
	public function test_is_valid_image_url( $url, $result ) {
		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), '2.7.0', m::type( 'string' ) );

		$this->remote_images->shouldReceive( 'validate_image_url' )->atMost()->once()->with( $url, 'avatar' )->andReturn( $result );

		$this->assertSame( $result, $this->sut->is_valid_image_url( $url ) );
	}

	/**
	 * Tests ::get_legacy_icon_url.
	 *
	 * @covers ::get_legacy_icon_url
	 */
	public function test_get_legacy_icon_url() {
		$url    = 'https://example.org/images/some-image.png';
		$hash   = 'some hash';
		$size   = 100;
		$result = 'https://example.org/images/cached-image-100.png';

		$this->remote_images->shouldReceive( 'get_hash' )->once()->with( $url )->andReturn( $hash );

		Filters\expectApplied( 'avatar_privacy_legacy_icon_url' )->once()->with( $url, $hash, $size, [] )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_legacy_icon_url( $url, $size ) );
	}
}
