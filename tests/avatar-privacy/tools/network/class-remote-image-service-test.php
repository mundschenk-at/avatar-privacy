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

use Avatar_Privacy\Tools\Hasher;

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
	 * @var Hasher
	 */
	private $hasher;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$this->hasher = m::mock( Hasher::class );

		$this->sut = m::mock( Remote_Image_Service::class, [ $this->hasher ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$hasher = m::mock( Hasher::class );
		$mock   = m::mock( Remote_Image_Service::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$mock->__construct( $hasher );

		$this->assert_attribute_same( $hasher, 'hasher', $mock );
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

		$this->hasher->shouldReceive( 'get_hash' )->once()->with( $url, true )->andReturn( $hash );

		$this->assertSame( $hash, $this->sut->get_hash( $url ) );
	}
}
