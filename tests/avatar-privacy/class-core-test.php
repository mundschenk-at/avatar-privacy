<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2022 Peter Putzer.
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

use Avatar_Privacy\Core;

use Avatar_Privacy\Core\Comment_Author_Fields;
use Avatar_Privacy\Core\Default_Avatars;
use Avatar_Privacy\Core\User_Fields;
use Avatar_Privacy\Core\Settings;

use Avatar_Privacy\Data_Storage\Options;

use Avatar_Privacy\Tools\Hasher;

/**
 * Avatar_Privacy\Core unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Core
 * @usesDefaultClass \Avatar_Privacy\Core
 *
 * @uses ::__construct
 */
class Core_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var \Avatar_Privacy\Core
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Required helper object.
	 *
	 * @var Hasher
	 */
	private $hasher;

	/**
	 * Required helper object.
	 *
	 * @var Comment_Author_Fields
	 */
	private $comment_author_fields;

	/**
	 * Required helper object.
	 *
	 * @var User_Fields
	 */
	private $user_fields;

	/**
	 * Required helper object.
	 *
	 * @var Default_Avatars
	 */
	private $default_avatars;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() { // @codingStandardsIgnoreLine
		parent::set_up();

		// Mock required helpers.
		$this->options               = m::mock( Options::class );
		$this->settings              = m::mock( Settings::class );
		$this->hasher                = m::mock( Hasher::class );
		$this->user_fields           = m::mock( User_Fields::class );
		$this->comment_author_fields = m::mock( Comment_Author_Fields::class );
		$this->default_avatars       = m::mock( Default_Avatars::class );

		// Partially mock system under test.
		$this->sut = m::mock(
			Core::class,
			[
				$this->settings,
				$this->hasher,
				$this->user_fields,
				$this->comment_author_fields,
				$this->default_avatars,
			]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Necesssary clean-up work.
	 *
	 * @since 2.3.3 Renamed to `tear_down`.
	 */
	protected function tear_down() { // @codingStandardsIgnoreLine
		// Reset singleton.
		$this->set_static_value( Core::class, 'instance', null );

		parent::tear_down();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses ::get_version
	 */
	public function test_constructor() {
		// Mock required helpers.
		$settings              = m::mock( Settings::class );
		$hasher                = m::mock( Hasher::class );
		$user_fields           = m::mock( User_Fields::class );
		$comment_author_fields = m::mock( Comment_Author_Fields::class );
		$default_avatars       = m::mock( Default_Avatars::class );

		$core = m::mock( Core::class )->makePartial();
		$core->__construct( $settings, $hasher, $user_fields, $comment_author_fields, $default_avatars );

		$this->assert_attribute_same( $settings, 'settings', $core );
		$this->assert_attribute_same( $hasher, 'hasher', $core );
		$this->assert_attribute_same( $user_fields, 'user_fields', $core );
		$this->assert_attribute_same( $comment_author_fields, 'comment_author_fields', $core );
		$this->assert_attribute_same( $default_avatars, 'default_avatars', $core );
	}

	/**
	 * Tests singleton methods.
	 *
	 * @covers ::get_instance
	 * @covers ::set_instance
	 */
	public function test_singleton() {

		$core = m::mock( Core::class );
		\Avatar_Privacy\Core::set_instance( $core );

		$core2 = \Avatar_Privacy\Core::get_instance();
		$this->assertSame( $core, $core2 );

		// Check ::get_instance (no underscore).
		$core3 = \Avatar_Privacy\Core::get_instance();
		$this->assertSame( $core, $core3 );
	}

	/**
	 * Tests ::get_instance without a previous call to ::_get_instance (i.e. _doing_it_wrong).
	 *
	 * @covers ::get_instance
	 */
	public function test_get_instance_failing() {
		$this->expectException( \BadMethodCallException::class );
		$this->expectExceptionMessage( 'Avatar_Privacy\Core::get_instance called without prior plugin intialization.' );

		$core = \Avatar_Privacy\Core::get_instance(); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	}

	/**
	 * Tests ::get_instance without a previous call to ::_get_instance (i.e. _doing_it_wrong).
	 *
	 * @covers ::set_instance
	 */
	public function test_set_instance_failing() {
		$core = m::mock( Core::class );

		// The first call is OK.
		\Avatar_Privacy\Core::set_instance( $core );

		// The second call fails with an exception.
		$this->expectException( \BadMethodCallException::class );
		$this->expectExceptionMessage( 'Avatar_Privacy\Core::set_instance called more than once.' );
		\Avatar_Privacy\Core::set_instance( $core );
	}

	/**
	 * Tests ::get_version.
	 *
	 * @covers ::get_version
	 */
	public function test_get_version() {
		$version = '47.11';

		$this->settings->shouldReceive( 'get_version' )->once()->andReturn( $version );

		$this->assertSame( $version, $this->sut->get_version() );
	}

	/**
	 * Tests ::get_plugin_file.
	 *
	 * @covers ::get_plugin_file
	 */
	public function test_get_plugin_file() {
		Functions\expect( '_deprecated_function' )->once()->with( Core::class . '::get_plugin_file', '2.3.0' );
		$this->assertSame( \AVATAR_PRIVACY_PLUGIN_FILE, $this->sut->get_plugin_file() );
	}

	/**
	 * Tests ::get_settings.
	 *
	 * @covers ::get_settings
	 */
	public function test_get_settings() {
		$settings = [
			'foo' => 'bar',
		];
		$force    = true;

		$this->settings->shouldReceive( 'get_all_settings' )
			->once()->with( $force )->andReturn( $settings );

		$this->assertSame( $settings, $this->sut->get_settings( $force ) );
	}

	/**
	 * Tests ::comment_author_allows_gravatar_use.
	 *
	 * @covers ::comment_author_allows_gravatar_use
	 */
	public function test_comment_author_allows_gravatar_use() {
		$result      = true;
		$id_or_email = 'some@email';

		$this->comment_author_fields->shouldReceive( 'allows_gravatar_use' )->once()->with( $id_or_email )->andReturn( $result );

		$this->assertSame( $result, $this->sut->comment_author_allows_gravatar_use( $id_or_email ) );
	}

	/**
	 * Tests ::comment_author_has_gravatar_policy.
	 *
	 * @covers ::comment_author_has_gravatar_policy
	 */
	public function test_comment_author_has_gravatar_policy() {
		$result      = true;
		$id_or_email = 'some@email';

		$this->comment_author_fields->shouldReceive( 'has_gravatar_policy' )->once()->with( $id_or_email )->andReturn( $result );

		$this->assertSame( $result, $this->sut->comment_author_has_gravatar_policy( $id_or_email ) );
	}

	/**
	 * Tests ::get_comment_author_key.
	 *
	 * @covers ::get_comment_author_key
	 */
	public function test_get_comment_author_key() {
		$result      = 5;
		$id_or_email = 'some@email';

		$this->comment_author_fields->shouldReceive( 'get_key' )->once()->with( $id_or_email )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_comment_author_key( $id_or_email ) );
	}

	/**
	 * Tests ::get_comment_author_email.
	 *
	 * @covers ::get_comment_author_email
	 */
	public function test_get_comment_author_email() {
		$result = 'some@email';
		$hash   = 'some hash';

		$this->comment_author_fields->shouldReceive( 'get_email' )->once()->with( $hash )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_comment_author_email( $hash ) );
	}

	/**
	 * Tests ::get_user_hash.
	 *
	 * @covers ::get_user_hash
	 */
	public function test_get_user_hash() {
		$user_id = '666';
		$hash    = 'hashed_email';

		$this->user_fields->shouldReceive( 'get_hash' )->once()->with( $user_id )->andReturn( $hash );

		$this->assertSame( $hash, $this->sut->get_user_hash( $user_id ) );
	}

	/**
	 * Tests ::update_comment_author_gravatar_use.
	 *
	 * @covers ::update_comment_author_gravatar_use
	 */
	public function test_update_comment_author_gravatar_use() {
		$email        = 'foo@bar.org';
		$comment_id   = 66;
		$use_gravatar = true;

		$this->comment_author_fields->shouldReceive( 'update_gravatar_use' )->once()->with( $email, $comment_id, $use_gravatar );

		$this->assertNull( $this->sut->update_comment_author_gravatar_use( $email, $comment_id, $use_gravatar ) );
	}

	/**
	 * Tests ::update_comment_author_hash.
	 *
	 * @covers ::update_comment_author_hash
	 */
	public function test_update_comment_author_hash() {
		$id    = 666;
		$email = 'foo@bar.com';

		Functions\expect( '_deprecated_argument' )->once();

		$this->comment_author_fields->shouldReceive( 'update_hash' )->once()->with( $email );

		$this->assertNull( $this->sut->update_comment_author_hash( $id, $email ) );
	}

	/**
	 * Tests ::update_comment_author_hash.
	 *
	 * @covers ::update_comment_author_hash
	 */
	public function test_update_comment_author_hash_no_deprecation_warning() {
		$id    = null;
		$email = 'foo@bar.com';

		Functions\expect( '_deprecated_argument' )->never();

		$this->comment_author_fields->shouldReceive( 'update_hash' )->once()->with( $email );

		$this->assertNull( $this->sut->update_comment_author_hash( $id, $email ) );
	}

	/**
	 * Tests ::get_salt.
	 *
	 * @covers ::get_salt
	 */
	public function test_get_salt() {
		$expected_salt = 'random salt';

		Functions\expect( '_deprecated_function' )->once()->with( Core::class . '::get_salt', '2.4.0' );

		$this->hasher->shouldReceive( 'get_salt' )->once()->andReturn( $expected_salt );

		$this->assertSame( $expected_salt, $this->sut->get_salt() );
	}

	/**
	 * Tests ::get_hash.
	 *
	 * @covers ::get_hash
	 */
	public function test_get_hash() {
		$email = 'example@example.org';
		$hash  = 'fake hash';

		$this->hasher->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );

		$this->assertSame( $hash, $this->sut->get_hash( $email ) );
	}

	/**
	 * Tests ::get_user_by_hash.
	 *
	 * @covers ::get_user_by_hash
	 */
	public function test_get_user_by_hash() {
		$hash     = 'some hashed email';
		$expected = m::mock( 'WP_User' );

		$this->user_fields->shouldReceive( 'get_user_by_hash' )->once()->with( $hash )->andReturn( $expected );

		$this->assertSame( $expected, $this->sut->get_user_by_hash( $hash ) );
	}

	/**
	 * Tests ::get_user_avatar.
	 *
	 * @covers ::get_user_avatar
	 */
	public function test_get_user_avatar() {
		$user_id = 42;
		$avatar  = [
			'type' => 'image/png',
			'file' => '/some/fake/file.png',
		];

		$this->user_fields->shouldReceive( 'get_local_avatar' )->once()->with( $user_id )->andReturn( $avatar );

		$this->assertSame( $avatar, $this->sut->get_user_avatar( $user_id ) );
	}

	/**
	 * Tests ::set_user_avatar.
	 *
	 * @covers ::set_user_avatar
	 */
	public function test_set_user_avatar() {
		$user_id = 42;
		$image   = 'fake image data';

		$this->user_fields->shouldReceive( 'set_local_avatar' )->once()->with( $user_id, $image );

		$this->assertNull( $this->sut->set_user_avatar( $user_id, $image ) );
	}

	/**
	 * Tests ::user_allows_gravatar_use.
	 *
	 * @covers ::user_allows_gravatar_use
	 */
	public function test_user_allows_gravatar_use() {
		$user_id = 42;
		$result  = true;

		$this->user_fields->shouldReceive( 'allows_gravatar_use' )->once()->with( $user_id )->andReturn( $result );

		$this->assertSame( $result, $this->sut->user_allows_gravatar_use( $user_id ) );
	}

	/**
	 * Tests ::user_has_gravatar_policy.
	 *
	 * @covers ::user_has_gravatar_policy
	 */
	public function test_user_has_gravatar_policy() {
		$user_id = 42;
		$result  = true;

		$this->user_fields->shouldReceive( 'has_gravatar_policy' )->once()->with( $user_id )->andReturn( $result );

		$this->assertSame( $result, $this->sut->user_has_gravatar_policy( $user_id ) );
	}

	/**
	 * Tests ::update_user_gravatar_use.
	 *
	 * @covers ::update_user_gravatar_use
	 */
	public function test_update_user_gravatar_use() {
		$user_id = 42;
		$value   = true;

		$this->user_fields->shouldReceive( 'update_gravatar_use' )->once()->with( $user_id, $value );

		$this->assertNull( $this->sut->update_user_gravatar_use( $user_id, $value ) );
	}

	/**
	 * Tests ::user_allows_anonymous_commenting.
	 *
	 * @covers ::user_allows_anonymous_commenting
	 */
	public function test_user_allows_anonymous_commenting() {
		$user_id = 42;
		$result  = true;

		$this->user_fields->shouldReceive( 'allows_anonymous_commenting' )->once()->with( $user_id )->andReturn( $result );

		$this->assertSame( $result, $this->sut->user_allows_anonymous_commenting( $user_id ) );
	}

	/**
	 * Tests ::update_user_anonymous_commenting.
	 *
	 * @covers ::update_user_anonymous_commenting
	 */
	public function test_update_user_anonymous_commenting() {
		$user_id = 42;
		$value   = true;

		$this->user_fields->shouldReceive( 'update_anonymous_commenting' )->once()->with( $user_id, $value );

		$this->assertNull( $this->sut->update_user_anonymous_commenting( $user_id, $value ) );
	}

	/**
	 * Tests ::get_custom_default_avatar.
	 *
	 * @covers ::get_custom_default_avatar
	 */
	public function test_get_custom_default_avatar() {
		$avatar = [
			'type' => 'image/png',
			'file' => '/some/fake/file.png',
		];

		$this->default_avatars->shouldReceive( 'get_custom_default_avatar' )->once()->andReturn( $avatar );

		$this->assertSame( $avatar, $this->sut->get_custom_default_avatar() );
	}

	/**
	 * Tests ::set_custom_default_avatar.
	 *
	 * @covers ::set_custom_default_avatar
	 */
	public function test_set_custom_default_avatar() {
		$image_url = 'https://example.org/path/image.png';

		$this->default_avatars->shouldReceive( 'set_custom_default_avatar' )->once()->with( $image_url );

		$this->assertNull( $this->sut->set_custom_default_avatar( $image_url ) );
	}
}
