<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Tools\Network;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Tools\Network\Remote_Image_Service;

use Avatar_Privacy\Data_Storage\Cache;
use Avatar_Privacy\Data_Storage\Database\Hashes_Table;
use Avatar_Privacy\Tools\Hasher;
use Avatar_Privacy\Tools\Images\Editor;

/**
 * Avatar_Privacy\Tools\Network\Remote_Image_Service unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Tools\Network\Remote_Image_Service
 * @usesDefaultClass \Avatar_Privacy\Tools\Network\Remote_Image_Service
 *
 * @uses ::__construct
 */
class Remote_Image_Service_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Remote_Image_Service
	 */
	private $sut;

	/**
	 * Test fixture.
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * Test fixture.
	 *
	 * @var Hasher
	 */
	private $hasher;

	/**
	 * Test fixture.
	 *
	 * @var Editor
	 */
	private $editor;

	/**
	 * Test fixture.
	 *
	 * @var Hashes_Table
	 */
	private $table;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$this->cache  = m::mock( Cache::class );
		$this->hasher = m::mock( Hasher::class );
		$this->editor = m::mock( Editor::class );
		$this->table  = m::mock( Hashes_Table::class );

		$this->sut = m::mock(
			Remote_Image_Service::class,
			[
				$this->cache,
				$this->hasher,
				$this->editor,
				$this->table,
			]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$cache  = m::mock( Cache::class );
		$hasher = m::mock( Hasher::class );
		$editor = m::mock( Editor::class );
		$table  = m::mock( Hashes_Table::class );
		$mock   = m::mock( Remote_Image_Service::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$mock->__construct( $cache, $hasher, $editor, $table );

		$this->assert_attribute_same( $cache, 'cache', $mock );
		$this->assert_attribute_same( $hasher, 'hasher', $mock );
		$this->assert_attribute_same( $editor, 'editor', $mock );
		$this->assert_attribute_same( $table, 'table', $mock );
	}

	/**
	 * Tests  ::get_image.
	 *
	 * @covers ::get_image
	 */
	public function test_get_image() {
		$url      = 'https://some/fake/image.png';
		$size     = 150;
		$mimetype = 'image/jpeg';

		$editor_mock        = m::mock( Editor::class );
		$remote_data        = [ 'not', 'really', 'a', 'remote', 'result' ];
		$image_data         = 'not really a PNG image';
		$resized_image_data = 'not really resized, either';

		Functions\expect( 'wp_remote_get' )->once()->with( $url )->andReturn( $remote_data );
		Functions\expect( 'wp_remote_retrieve_body' )->once()->with( $remote_data )->andReturn( $image_data );

		$this->editor->shouldReceive( 'get_mime_type' )->once()->with( $image_data )->andReturn( 'image/png' );
		$this->editor->shouldReceive( 'create_from_string' )->once()->with( $image_data )->andReturn( $editor_mock );
		$this->editor->shouldReceive( 'get_resized_image_data' )->once()->with( $editor_mock, $size, $size, $mimetype )->andReturn( $resized_image_data );

		$this->assertSame( $resized_image_data, $this->sut->get_image( $url, $size, $mimetype ) );
	}

	/**
	 * Tests  ::get_image.
	 *
	 * @covers ::get_image
	 */
	public function test_get_image_error() {
		$url      = 'https://some/fake/image.png';
		$size     = 150;
		$mimetype = 'image/jpeg';

		$remote_data = [ 'not', 'really', 'a', 'remote', 'result' ];
		$image_data  = 'not really a PNG image';

		Functions\expect( 'wp_remote_get' )->once()->with( $url )->andReturn( $remote_data );
		Functions\expect( 'wp_remote_retrieve_body' )->once()->with( $remote_data )->andReturn( $image_data );

		$this->editor->shouldReceive( 'get_mime_type' )->once()->with( $image_data )->andReturn( false );
		$this->editor->shouldReceive( 'get_image_editor' )->never();
		$this->editor->shouldReceive( 'get_resized_image_data' )->never();

		$this->assertSame( '', $this->sut->get_image( $url, $size, $mimetype ) );
	}

	/**
	 * Tests ::validate_image_url.
	 *
	 * @covers ::validate_image_url
	 **/
	public function test_validate_image_url() {
		// Parameters.
		$maybe_url = 'https://my.domain/path/image.gif';
		$context   = 'my_context';

		// Expected result.
		$result = true;

		// Intermediate values.
		$site_url     = 'site://url';
		$domain       = 'my.domain';
		$allow_remote = false;

		Filters\expectApplied( "avatar_privacy_allow_remote_{$context}_url" )->once()->with( false )->andReturn( $allow_remote );

		Functions\expect( 'get_site_url' )->once()->andReturn( $site_url );
		Functions\expect( 'wp_parse_url' )->once()->with( $site_url, \PHP_URL_HOST )->andReturn( $domain );

		Functions\expect( 'wp_parse_url' )->once()->with( $maybe_url, \PHP_URL_HOST )->andReturn( $domain );

		Filters\expectApplied( "avatar_privacy_validate_{$context}_url" )->once()->with( $result, $maybe_url, $allow_remote )->andReturn( $result );

		$this->assertSame( $result, $this->sut->validate_image_url( $maybe_url, $context ) );
	}

	/**
	 * Tests ::validate_image_url.
	 *
	 * @covers ::validate_image_url
	 **/
	public function test_validate_image_url_invalid() {
		// Parameters.
		$maybe_url = 'my.domain/path/image.gif';
		$context   = 'my_context';

		// Expected result.
		$result = false;

		// Intermediate values.
		$site_url     = 'site://url';
		$domain       = 'my.domain';
		$allow_remote = false;

		Filters\expectApplied( "avatar_privacy_allow_remote_{$context}_url" )->once()->with( false )->andReturn( $allow_remote );

		Functions\expect( 'get_site_url' )->once()->andReturn( $site_url );
		Functions\expect( 'wp_parse_url' )->once()->with( $site_url, \PHP_URL_HOST )->andReturn( $domain );

		Functions\expect( 'wp_parse_url' )->never();

		Filters\expectApplied( "avatar_privacy_validate_{$context}_url" )->once()->with( $result, $maybe_url, $allow_remote )->andReturn( $result );

		$this->assertSame( $result, $this->sut->validate_image_url( $maybe_url, $context ) );
	}

	/**
	 * Tests ::validate_image_url.
	 *
	 * @covers ::validate_image_url
	 **/
	public function test_validate_image_url_remote_url_not_allowed() {
		// Parameters.
		$maybe_url = 'https://other.domain/path/image.gif';
		$context   = 'my_context';

		// Expected result.
		$result = false;

		// Intermediate values.
		$site_url     = 'site://url';
		$domain       = 'my.domain';
		$allow_remote = false;

		Filters\expectApplied( "avatar_privacy_allow_remote_{$context}_url" )->once()->with( false )->andReturn( $allow_remote );

		Functions\expect( 'get_site_url' )->once()->andReturn( $site_url );
		Functions\expect( 'wp_parse_url' )->once()->with( $site_url, \PHP_URL_HOST )->andReturn( $domain );

		Functions\expect( 'wp_parse_url' )->once()->with( $maybe_url, \PHP_URL_HOST )->andReturn( 'other.domain' );

		Filters\expectApplied( "avatar_privacy_validate_{$context}_url" )->once()->with( $result, $maybe_url, $allow_remote )->andReturn( $result );

		$this->assertSame( $result, $this->sut->validate_image_url( $maybe_url, $context ) );
	}

	/**
	 * Tests ::validate_image_url.
	 *
	 * @covers ::validate_image_url
	 **/
	public function test_validate_image_url_remote_url_allowed() {
		// Parameters.
		$maybe_url = 'https://other.domain/path/image.gif';
		$context   = 'my_context';

		// Expected result.
		$result = true;

		// Intermediate values.
		$site_url     = 'site://url';
		$domain       = 'my.domain';
		$allow_remote = true;

		Filters\expectApplied( "avatar_privacy_allow_remote_{$context}_url" )->once()->with( false )->andReturn( $allow_remote );

		Functions\expect( 'get_site_url' )->once()->andReturn( $site_url );
		Functions\expect( 'wp_parse_url' )->once()->with( $site_url, \PHP_URL_HOST )->andReturn( $domain );

		Functions\expect( 'wp_parse_url' )->never();

		Filters\expectApplied( "avatar_privacy_validate_{$context}_url" )->once()->with( $result, $maybe_url, $allow_remote )->andReturn( $result );

		$this->assertSame( $result, $this->sut->validate_image_url( $maybe_url, $context ) );
	}

	/**
	 * Test ::get_hash.
	 *
	 * @covers ::get_hash
	 */
	public function test_get_hash() {
		$url  = 'https://example.org/some-image.png';
		$hash = 'fake hash';
		$key  = "image-url_{$hash}";

		$this->hasher->shouldReceive( 'get_hash' )->once()->with( $url )->andReturn( $hash );
		$this->cache->shouldReceive( 'get' )->once()->with( $key )->andReturn( false );

		$this->table->shouldReceive( 'replace' )->once()->with(
			[
				'identifier' => $url,
				'hash'       => $hash,
				'type'       => Remote_Image_Service::IDENTIFIER_TYPE,
			]
		)->andReturn( 1 );

		$this->cache->shouldReceive( 'set' )->once()->with( $key, $url, m::type( 'int' ) );

		$this->assertSame( $hash, $this->sut->get_hash( $url ) );
	}

	/**
	 * Test ::get_hash.
	 *
	 * @covers ::get_hash
	 */
	public function test_get_hash_cached() {
		$url  = 'https://example.org/some-image.png';
		$hash = 'fake hash';
		$key  = "image-url_{$hash}";

		$this->hasher->shouldReceive( 'get_hash' )->once()->with( $url )->andReturn( $hash );
		$this->cache->shouldReceive( 'get' )->once()->with( $key )->andReturn( $url );

		$this->table->shouldReceive( 'replace' )->never();
		$this->cache->shouldReceive( 'set' )->never();

		$this->assertSame( $hash, $this->sut->get_hash( $url ) );
	}

	/**
	 * Test ::get_image_url.
	 *
	 * @covers ::get_image_url
	 */
	public function test_get_image_url() {
		$hash = 'fake hash';
		$url  = 'https://example.org/some-image.png';

		global $wpdb;
		$wpdb  = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$key   = "image-url_{$hash}";
		$table = 'my_table';
		$query = 'SQL query';

		$this->cache->shouldReceive( 'get' )->once()->with( $key )->andReturn( false );

		$this->table->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$wpdb->shouldReceive( 'prepare' )->once()->with( m::type( 'string' ), $hash, Remote_Image_Service::IDENTIFIER_TYPE )->andReturn( $query );
		$wpdb->shouldReceive( 'get_var' )->once()->with( $query )->andReturn( $url );

		$this->cache->shouldReceive( 'set' )->once()->with( $key, $url, m::type( 'int' ) );

		$this->assertSame( $url, $this->sut->get_image_url( $hash ) );
	}
}
