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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Tools;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Data_Storage\Network_Options;

/**
 * Avatar_Privacy_Factory unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Tools\Hasher
 * @usesDefaultClass \Avatar_Privacy\Tools\Hasher
 *
 * @uses ::__construct
 */
class Hasher_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var \Avatar_Privacy\Tools\Hasher
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Network_Options
	 */
	private $network_options;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() { // @codingStandardsIgnoreLine
		parent::set_up();

		// Mock required helpers.
		$this->network_options = m::mock( Network_Options::class );

		// Partially mock system under test.
		$this->sut = m::mock(
			\Avatar_Privacy\Tools\Hasher::class,
			[
				$this->network_options,
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
		$network_options = m::mock( Network_Options::class )->makePartial();

		$hasher = m::mock( \Avatar_Privacy\Tools\Hasher::class )->makePartial();
		$hasher->__construct( $network_options );

		$this->assert_attribute_same( $network_options, 'network_options', $hasher );
	}

	/**
	 * Tests ::get_salt.
	 *
	 * @covers ::get_salt
	 */
	public function test_get_salt() {
		$expected_salt = 'random salt';

		Filters\expectApplied( 'avatar_privacy_salt' )->once()->with( '' );

		$this->network_options->shouldReceive( 'get' )->once()->with( Network_Options::SALT )->andReturn( '' );

		Functions\expect( 'wp_rand' )->once()->andReturn( $expected_salt );

		$this->network_options->shouldReceive( 'set' )->once()->with( Network_Options::SALT, $expected_salt );

		$this->assertSame( $expected_salt, $this->sut->get_salt() );
	}

	/**
	 * Tests ::get_salt.
	 *
	 * @covers ::get_salt
	 */
	public function test_get_salt_filtered() {
		$expected_salt = 'random salt';

		Filters\expectApplied( 'avatar_privacy_salt' )->once()->with( '' )->andReturn( $expected_salt );

		$this->network_options->shouldReceive( 'get' )->never();
		Functions\expect( 'wp_rand' )->never();
		$this->network_options->shouldReceive( 'set' )->never();

		$this->assertSame( $expected_salt, $this->sut->get_salt() );
	}

	/**
	 * Tests ::get_salt.
	 *
	 * @covers ::get_salt
	 */
	public function test_get_salt_from_options() {
		$expected_salt = 'random salt';

		Filters\expectApplied( 'avatar_privacy_salt' )->once()->with( '' );

		$this->network_options->shouldReceive( 'get' )->once()->with( Network_Options::SALT )->andReturn( $expected_salt );

		Functions\expect( 'wp_rand' )->never();
		$this->network_options->shouldReceive( 'set' )->never();

		$this->assertSame( $expected_salt, $this->sut->get_salt() );
	}

	/**
	 * Provides data for testing ::get_hash.
	 *
	 * @return array
	 */
	public function provide_get_hash_data() {
		return [
			[ 'example@example.org', false, '874ccc6634195fdf4a1e5391a623fddb8a347d26cad4d9bbda683923afca3132' ],
			[ '   example@example.org ', false, '874ccc6634195fdf4a1e5391a623fddb8a347d26cad4d9bbda683923afca3132' ],
			[ 'example@EXAMPLE.org', false, '874ccc6634195fdf4a1e5391a623fddb8a347d26cad4d9bbda683923afca3132' ],
			[ 'https://example.org/Some-Image.png', true, '9391b68b955364d41b306a7cb562095547437fa407bc3a26cbfacec777ec8ec8' ],
			[ 'https://example.org/some-image.png', true, 'a96e279f47de085882ef102b1fe335f51c118087d8c3ea9a9bf8ab86b1d0fef4' ],
		];
	}

	/**
	 * Tests ::get_hash.
	 *
	 * @covers ::get_hash
	 *
	 * @dataProvider provide_get_hash_data
	 *
	 * @param  string $identifier     The identifier.
	 * @param  bool   $case_sensitive Whether the identifier should treated as case-sensitive.
	 * @param  string $result         The expected result.
	 */
	public function test_get_hash( $identifier, $case_sensitive, $result ) {
		$this->sut->shouldReceive( 'get_salt' )->once()->andReturn( 'foobar57' );

		$this->assertSame( $result, $this->sut->get_hash( $identifier, $case_sensitive ) );
	}
}
