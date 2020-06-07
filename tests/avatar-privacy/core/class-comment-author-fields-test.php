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

use Avatar_Privacy\Core\Comment_Author_Fields;
use Avatar_Privacy\Core\Hasher;

use Avatar_Privacy\Data_Storage\Cache;

/**
 * Avatar_Privacy_Factory unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Core\Comment_Author_Fields
 * @usesDefaultClass \Avatar_Privacy\Core\Comment_Author_Fields
 *
 * @uses ::__construct
 */
class Comment_Author_Fields_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Comment_Author_Fields
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * Required helper object.
	 *
	 * @var Hasher
	 */
	private $hasher;

	// Mock version.
	const VERSION = '1.0.0';

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() { // @codingStandardsIgnoreLine
		parent::set_up();

		// Mock required helpers.
		$this->cache  = m::mock( Cache::class );
		$this->hasher = m::mock( Hasher::class );

		// Partially mock system under test.
		$this->sut = m::mock(
			Comment_Author_Fields::class ,
			[
				$this->cache,
				$this->hasher,
			]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		// Mock required helpers.
		$cache  = m::mock( Cache::class )->makePartial();
		$hasher = m::mock( Hasher::class );

		$comment_author_fields = m::mock( Comment_Author_Fields::class )->makePartial();
		$comment_author_fields->__construct( $cache, $hasher );

		$this->assert_attribute_same( $cache, 'cache', $comment_author_fields );
		$this->assert_attribute_same( $hasher, 'hasher', $comment_author_fields );
	}

	/**
	 * Test ::get_hash.
	 *
	 * @covers ::get_hash
	 */
	public function test_get_hash() {
		$email_or_hash = 'some@mail';
		$hash          = 'fake hash';

		$this->hasher->shouldReceive( 'get_hash' )->once()->with( $email_or_hash )->andReturn( $hash );

		$this->assertSame( $hash, $this->sut->get_hash( $email_or_hash ) );
	}

	/**
	 * Data: id_or_email, returned object, use_gravatar result, has_gravatar_policy result.
	 *
	 * @return array
	 */
	public function provide_comment_author_data() {
		return [
			[ 'some_id_or_email', (object) [ 'use_gravatar' => true ], true, true ],
			[ 'some_id_or_email', (object) [ 'use_gravatar' => false ], false, true ],
			[ 'some_id_or_email', null, false, false ],
		];
	}

	/**
	 * Tests ::allows_gravatar_use.
	 *
	 * @covers ::allows_gravatar_use
	 *
	 * @dataProvider provide_comment_author_data
	 *
	 * @param mixed  $id_or_email         An ID or email (not really important here).
	 * @param object $object              The result object.
	 * @param bool   $use_gravatar        An expected result.
	 * @param bool   $has_gravatar_policy An expected result.
	 */
	public function test_allows_gravatar_use( $id_or_email, $object, $use_gravatar, $has_gravatar_policy ) {
		$this->sut->shouldReceive( 'load' )->once()->with( $id_or_email )->andReturn( $object );

		$this->assertSame( $use_gravatar, $this->sut->allows_gravatar_use( $id_or_email ) );
	}

	/**
	 * Tests ::has_gravatar_policy.
	 *
	 * @covers ::has_gravatar_policy
	 *
	 * @dataProvider provide_comment_author_data
	 *
	 * @param mixed  $id_or_email         An ID or email (not really important here).
	 * @param object $object              The result object.
	 * @param bool   $use_gravatar        An expected result.
	 * @param bool   $has_gravatar_policy An expected result.
	 */
	public function test_has_gravatar_policy( $id_or_email, $object, $use_gravatar, $has_gravatar_policy ) {
		$this->sut->shouldReceive( 'load' )->once()->with( $id_or_email )->andReturn( $object );

		$this->assertSame( $has_gravatar_policy, $this->sut->has_gravatar_policy( $id_or_email ) );
	}

	/**
	 * Data: id_or_email, returned object, database ID result.
	 *
	 * @return array
	 */
	public function provide_get_key_data() {
		return [
			[ 'some_id_or_email', (object) [ 'id' => 5 ], 5 ],
			[ 'some_id_or_email', null, 0 ],
		];
	}

	/**
	 * Tests ::get_key.
	 *
	 * @covers ::get_key
	 *
	 * @dataProvider provide_get_key_data
	 *
	 * @param mixed  $id_or_email         An ID or email (not really important here).
	 * @param object $object              The result object.
	 * @param int    $id                  The result.
	 */
	public function test_get_key( $id_or_email, $object, $id ) {
		$this->sut->shouldReceive( 'load' )->once()->with( $id_or_email )->andReturn( $object );

		$this->assertSame( $id, $this->sut->get_key( $id_or_email ) );
	}

	/**
	 * Data: id_or_email, returned object, email.
	 *
	 * @return array
	 */
	public function provide_get_email_data() {
		return [
			[ 'should_be_a_hash', (object) [ 'email' => 5 ], 5 ],
			[ 'should_be_another_hash', null, '' ],
		];
	}

	/**
	 * Tests ::get_email.
	 *
	 * @covers ::get_email
	 *
	 * @dataProvider provide_get_email_data
	 *
	 * @param mixed  $hash   A hashed email.
	 * @param object $object The result object.
	 * @param int    $email  The retrieved email.
	 */
	public function test_get_email( $hash, $object, $email ) {
		$this->sut->shouldReceive( 'load' )->once()->with( $hash )->andReturn( $object );

		$this->assertSame( $email, $this->sut->get_email( $hash ) );
	}

	/**
	 * Provides data for testing load.
	 *
	 * @return array
	 */
	public function provide_load_data() {
		$object = (object) [ 'foo' => 'bar' ];

		return [
			[ 'something other than an email address', false, 'hash', $object ],
			[ 'something other than an email address', $object, 'hash', $object ],
			[ 'foo@bar.com', false, 'email', $object ],
			[ 'foo@BAR.com', false, 'email', $object, 'foo@bar.com' ],
			[ ' foo@bar.com   ', $object, 'email', $object, 'foo@bar.com' ],
			[ '', null ],
			[ '    ', null ],
		];
	}

	/**
	 * Tests ::load.
	 *
	 * @covers ::load
	 *
	 * @dataProvider provide_load_data
	 *
	 * @param  string            $email_or_hash The input value.
	 * @param  object|false|null $cached        The cached result (or false, or null if bailing early).
	 * @param  string            $column        The relevant column (`email` or `hash`).
	 * @param  object|null       $result        The expected result.
	 * @param  string|null       $clean         Optional. The "clean" input value. Default is `$email_or_hash`.
	 */
	public function test_load( $email_or_hash, $cached = null, $column = null, $result = null, $clean = null ) {
		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		// Set default value for clean email or hash.
		$clean = null === $clean ? $email_or_hash : $clean;

		if ( null === $cached ) {
			$this->sut->shouldReceive( 'get_cache_key' )->never();
			$this->cache->shouldReceive( 'get' )->never();
			$wpdb->shouldReceive( 'prepare' )->never();
			$wpdb->shouldReceive( 'get_row' )->never();
			$this->cache->shouldReceive( 'set' )->never();
		} else {
			$key = 'fake cache key';

			$this->sut->shouldReceive( 'get_cache_key' )->with( $clean, $column )->andReturn( $key );
			$this->cache->shouldReceive( 'get' )->with( $key )->andReturn( $cached );

			if ( empty( $cached ) ) {
				$wpdb->shouldReceive( 'prepare' )->with( m::type( 'string' ), $wpdb->avatar_privacy, $column, $clean )->andReturn( 'sql_string' );
				$wpdb->shouldReceive( 'get_row' )->with( 'sql_string', \OBJECT )->andReturn( $result );
				$this->cache->shouldReceive( 'set' )->with( $key, $result, m::type( 'int' ) )->andReturn( false );
			} else {
				$wpdb->shouldReceive( 'prepare' )->never();
				$wpdb->shouldReceive( 'get_row' )->never();
				$this->cache->shouldReceive( 'set' )->never();
			}
		}

		$this->assertSame( $result, $this->sut->load( $email_or_hash ) );
	}

	/**
	 * Tests ::update.
	 *
	 * @covers ::update
	 */
	public function test_update() {
		$columns        = [ 'foo' => 'bar' ];
		$id             = 13;
		$email          = 'foo@bar.org';
		$hash           = 'hashed $email';
		$format_strings = 'format strings for columns array';
		$rows_updated   = 5;

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		$this->sut->shouldReceive( 'get_format_strings' )->once()->with( $columns )->andReturn( $format_strings );
		$wpdb->shouldReceive( 'update' )->once()->with( $wpdb->avatar_privacy, $columns, [ 'id' => $id ], $format_strings, [ '%d' ] )->andReturn( $rows_updated );

		$this->sut->shouldReceive( 'clear_cache' )->once()->with( $email );

		$this->assertSame( $rows_updated, $this->sut->update( $id, $email, $columns ) );
	}

	/**
	 * Tests ::update.
	 *
	 * @covers ::update
	 */
	public function test_update_error() {
		$columns        = [ 'foo' => 'bar' ];
		$id             = 13;
		$email          = 'foo@bar.org';
		$hash           = 'hashed $email';
		$format_strings = 'format strings for columns array';

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		$this->sut->shouldReceive( 'get_format_strings' )->once()->with( $columns )->andReturn( $format_strings );
		$wpdb->shouldReceive( 'update' )->once()->with( $wpdb->avatar_privacy, $columns, [ 'id' => $id ], $format_strings, [ '%d' ] )->andReturn( false );

		$this->sut->shouldReceive( 'clear_cache' )->never();

		$this->assertFalse( $this->sut->update( $id, $email, $columns ) );
	}

	/**
	 * Tests ::update.
	 *
	 * @covers ::update
	 */
	public function test_update_no_rows_updated() {
		$columns        = [ 'foo' => 'bar' ];
		$id             = 13;
		$email          = 'foo@bar.org';
		$hash           = 'hashed $email';
		$format_strings = 'format strings for columns array';
		$rows_updated   = 0;

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		$this->sut->shouldReceive( 'get_format_strings' )->once()->with( $columns )->andReturn( $format_strings );
		$wpdb->shouldReceive( 'update' )->once()->with( $wpdb->avatar_privacy, $columns, [ 'id' => $id ], $format_strings, [ '%d' ] )->andReturn( $rows_updated );

		$this->sut->shouldReceive( 'clear_cache' )->never();

		$this->assertSame( 0, $this->sut->update( $id, $email, $columns ) );
	}

	/**
	 * Tests ::insert.
	 *
	 * @covers ::insert
	 */
	public function test_insert() {
		$email          = 'foo@bar.org';
		$hash           = 'hashed $email';
		$use_gravatar   = true;
		$last_updated   = 'a timestamp';
		$log_message    = 'a log message';
		$format_strings = 'format strings for columns array';
		$rows_updated   = 1;

		$expected_columns = [
			'email'        => $email,
			'hash'         => $hash,
			'use_gravatar' => $use_gravatar,
			'last_updated' => $last_updated,
			'log_message'  => $log_message,
		];

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		$this->sut->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );
		$this->sut->shouldReceive( 'clear_cache' )->once()->with( $hash, 'hash' );

		$this->sut->shouldReceive( 'get_format_strings' )->once()->with( $expected_columns )->andReturn( $format_strings );
		$wpdb->shouldReceive( 'insert' )->once()->with( $wpdb->avatar_privacy, $expected_columns, $format_strings )->andReturn( $rows_updated );

		$this->assertSame( $rows_updated, $this->sut->insert( $email, $use_gravatar, $last_updated, $log_message ) );
	}

	/**
	 * Provides data for testing update_gravatar_use
	 *
	 * @return array
	 */
	public function provide_update_gravatar_use_data() {
		return [
			[
				'foo@bar.org',
				5,
				true,
				(object) [
					'id'           => 77,
					'email'        => 'foo@bar.org',
					'use_gravatar' => true,
				],
			],
			[
				'foo@bar.org',
				5,
				true,
				(object) [
					'id'           => 77,
					'email'        => 'foo@bar.org',
					'use_gravatar' => false,
				],
			],
			[ 'foo@bar.org', 5, false, false ],
		];
	}

	/**
	 * Tests ::update_gravatar_use.
	 *
	 * @covers ::update_gravatar_use
	 *
	 * @dataProvider provide_update_gravatar_use_data
	 *
	 * @param  string $email        An email address.
	 * @param  int    $comment_id   A comment ID.
	 * @param  bool   $use_gravatar The gravatar use flag.
	 * @param  object $data         The retrieved data.
	 */
	public function test_update_gravatar_use( $email, $comment_id, $use_gravatar, $data ) {
		$hash = 'hashed $email';

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';
		$wpdb->site_id        = 1;
		$wpdb->blog_id        = 2;

		$this->sut->shouldReceive( 'load' )->once()->with( $email )->andReturn( $data );

		if ( empty( $data ) ) {
			Functions\expect( 'current_time' )->once()->with( 'mysql' )->andReturn( 'a timestamp' );
			$this->sut->shouldReceive( 'get_log_message' )->once()->with( $comment_id )->andReturn( 'my log message' );
			$this->sut->shouldReceive( 'insert' )->once()->with( $email, $use_gravatar, m::type( 'string' ), m::type( 'string' ) );
		} else {
			if ( $data->use_gravatar !== $use_gravatar ) {
				Functions\expect( 'current_time' )->once()->with( 'mysql' )->andReturn( 'a timestamp' );
				$this->sut->shouldReceive( 'get_log_message' )->once()->with( $comment_id )->andReturn( 'my log message' );
				$this->sut->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );
				$this->sut->shouldReceive( 'update' )->once()->with( $data->id, $data->email, m::type( 'array' ) );
			} elseif ( empty( $data->hash ) ) {
				$this->sut->shouldReceive( 'update_hash' )->once()->with( $data->id, $data->email );
			}
		}

		$this->assertNull( $this->sut->update_gravatar_use( $email, $comment_id, $use_gravatar ) );
	}

	/**
	 * Tests ::update_hash.
	 *
	 * @covers ::update_hash
	 */
	public function test_update_hash() {
		$id    = 666;
		$email = 'foo@bar.com';
		$hash  = 'hashedemail123';

		$this->sut->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );
		$this->sut->shouldReceive( 'update' )->once()->with( $id, $email, [ 'hash' => $hash ] );

		$this->assertNull( $this->sut->update_hash( $id, $email ) );
	}

	/**
	 * Tests ::get_format_strings.
	 *
	 * @covers ::get_format_strings
	 */
	public function test_get_format_strings() {
		$columns  = [
			'log_message'  => 'foo',
			'use_gravatar' => 1,
			'hash'         => 'bar',
		];
		$expected = [ '%s', '%d', '%s' ];

		$this->assertSame( $expected, $this->sut->get_format_strings( $columns ) );
	}

	/**
	 * Tests ::get_format_strings.
	 *
	 * @covers ::get_format_strings
	 */
	public function test_get_format_strings_invalid_column() {
		$columns  = [
			'log_message'  => 'foo',
			'use_gravatar' => 1,
			'hash'         => 'bar',
			'foo'          => 'bar',
		];
		$expected = [ '%s', '%d', '%s' ];

		$this->expectException( \RuntimeException::class );

		$this->assertSame( $expected, $this->sut->get_format_strings( $columns ) );
	}

	/**
	 * Tests ::get_log_message.
	 *
	 * @covers ::get_log_message
	 */
	public function test_get_log_message() {
		$comment_id = 42;
		$expected   = "set with comment {$comment_id}";

		Functions\expect( 'is_multisite' )->once()->andReturn( false );

		$this->assertSame( $expected, $this->sut->get_log_message( $comment_id ) );
	}

	/**
	 * Tests ::get_log_message in a multisite environment.
	 *
	 * @covers ::get_log_message
	 */
	public function test_get_log_message_multisite() {
		$comment_id = 42;

		global $wpdb;
		$wpdb         = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->siteid = 3;
		$wpdb->blogid = 12;

		$expected = "set with comment {$comment_id} (site: {$wpdb->siteid}, blog: {$wpdb->blogid})";

		Functions\expect( 'is_multisite' )->once()->andReturn( true );

		$this->assertSame( $expected, $this->sut->get_log_message( $comment_id ) );
	}

	/**
	 * Tests ::clear_cache.
	 *
	 * @covers ::clear_cache
	 */
	public function test_clear_cache() {
		$email_or_hash = 'foo@bar.com';
		$type          = 'email';
		$key           = 'fake cache key';

		$this->sut->shouldReceive( 'get_cache_key' )->once()->with( $email_or_hash, $type )->andReturn( $key );
		$this->cache->shouldReceive( 'delete' )->once()->with( $key );

		$this->assertNull( $this->sut->clear_cache( $email_or_hash, $type ) );
	}

	/**
	 * Tests ::get_cache_key.
	 *
	 * @covers ::get_cache_key
	 */
	public function test_get_cache_key() {
		$email = 'foo@bar.com';
		$hash  = 'hashedemail123';
		$type  = 'email';
		$key   = Comment_Author_Fields::EMAIL_CACHE_PREFIX . $hash;

		$this->sut->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );

		$this->assertSame( $key, $this->sut->get_cache_key( $email, $type ) );
	}

	/**
	 * Tests ::get_cache_key.
	 *
	 * @covers ::get_cache_key
	 */
	public function test_get_cache_key_with_hash() {
		$hash = 'hashedemail123';
		$type = 'hash';
		$key  = Comment_Author_Fields::EMAIL_CACHE_PREFIX . $hash;

		$this->sut->shouldReceive( 'get_hash' )->never();

		$this->assertSame( $key, $this->sut->get_cache_key( $hash, $type ) );
	}
}
