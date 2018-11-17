<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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

use Avatar_Privacy\Tools\Network\Gravatar_Service;

use Avatar_Privacy\Data_Storage\Transients;
use Avatar_Privacy\Data_Storage\Site_Transients;


/**
 * Avatar_Privacy\Tools\Network\Gravatar_Service unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Tools\Network\Gravatar_Service
 * @usesDefaultClass \Avatar_Privacy\Tools\Network\Gravatar_Service
 *
 * @uses ::__construct
 */
class Gravatar_Service_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Gravatar_Service
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Transients
	 */
	private $transients;

	/**
	 * Required helper object.
	 *
	 * @var Site_Transients
	 */
	private $site_transients;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		// Mock required helpers.
		$this->transients      = m::mock( Transients::class );
		$this->site_transients = m::mock( Site_Transients::class );

		$this->sut = m::mock( Gravatar_Service::class, [ $this->transients, $this->site_transients ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Gravatar_Service::class )->makePartial();

		$mock->__construct( $this->transients, $this->site_transients );

		$this->assertAttributeSame( $this->transients, 'transients', $mock );
		$this->assertAttributeSame( $this->site_transients, 'site_transients', $mock );
	}

	/**
	 * Tests ::get_image.
	 *
	 * @covers ::get_image
	 */
	public function test_get_image() {
		$email    = 'foo@bar.org';
		$size     = 99;
		$rating   = 'r';
		$response = (object) [ 'the_response' ];

		$this->sut->shouldReceive( 'get_url' )->once()->with( $email, $size, $rating )->andReturn( 'URL' );

		Functions\expect( 'wp_remote_get' )->once()->with( 'URL' )->andReturn( $response );
		Functions\expect( 'wp_remote_retrieve_body' )->once()->with( $response )->andReturn( 'IMAGEDATA' );

		$this->assertSame( 'IMAGEDATA', $this->sut->get_image( $email, $size, $rating ) );
	}

	/**
	 * Tests ::get_url.
	 *
	 * @covers ::get_url
	 */
	public function test_get_url() {
		$email  = 'foo@bar.org';
		$size   = 99;
		$rating = 'r';

		$this->sut->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( 'HASH' );

		Functions\expect( 'add_query_arg' )->once()->with( m::type( 'array' ), 'https://secure.gravatar.com/avatar/HASH' )->andReturn( 'URL' );
		Functions\expect( 'esc_url_raw' )->once()->with( 'URL' )->andReturn( 'ESCAPED_URL' );

		$this->assertSame( 'ESCAPED_URL', $this->sut->get_url( $email, $size, $rating ) );
	}

	/**
	 * Tests ::get_hash.
	 *
	 * @covers ::get_hash
	 */
	public function test_get_hash() {
		$email1 = 'foo@bar.org';
		$email2 = '   foo@bar.org ';
		$email3 = ' foo@BAR.org  ';
		$hash   = '24191827e60cdb49a3d17fb1befe951b';

		$this->assertSame( $hash, $this->sut->get_hash( $email1 ) );
		$this->assertSame( $hash, $this->sut->get_hash( $email2 ) );
		$this->assertSame( $hash, $this->sut->get_hash( $email3 ) );
	}

	/**
	 * Provides data for testing validate.
	 *
	 * @return array
	 */
	public function provide_validate_data() {
		return [
			[ 'foo@bar.org', 0, 'image/png' ],
			[ 'foo@bar.org', 77, '' ],
			[ '', 0, '' ],
		];
	}

	/**
	 * Tests ::validate in single site, no previous caching.
	 *
	 * @covers ::validate
	 *
	 * @dataProvider provide_validate_data
	 *
	 * @param  string $email  The email.
	 * @param  int    $age    The object age in seconds.
	 * @param  string $result The expected result.
	 */
	public function test_validate( $email, $age, $result ) {
		$final_result = empty( $result ) ? '' : $result;
		$duration     = 666;

		if ( ! empty( $email ) ) {
			$this->sut->shouldReceive( 'get_hash' )->twice()->with( $email )->andReturn( 'HASH' );

			Functions\expect( 'is_multisite' )->once()->andReturn( false );
			$this->transients->shouldReceive( 'get' )->once()->with( 'check_HASH' )->andReturn( false );

			$this->sut->shouldReceive( 'ping_gravatar' )->once()->with( $email )->andReturn( $result );

			if ( false !== $result ) {
				$this->sut->shouldReceive( 'calculate_caching_duration' )->once()->with( $result, $age )->andReturn( $duration );
				$this->transients->shouldReceive( 'set' )->once()->with( 'check_HASH', $result, $duration )->andReturn( false );
			}
		}

		// Validate once.
		$this->assertSame( $final_result, $this->sut->validate( $email, $age ) );

		// Validate twice.
		$this->assertSame( $final_result, $this->sut->validate( $email, $age ) );
	}

	/**
	 * Tests ::validate in multisite site, no previous caching.
	 *
	 * @covers ::validate
	 *
	 * @dataProvider provide_validate_data
	 *
	 * @param  string $email  The email.
	 * @param  int    $age    The object age in seconds.
	 * @param  string $result The expected result.
	 */
	public function test_validate_multisite( $email, $age, $result ) {
		$final_result = empty( $result ) ? '' : $result;
		$duration     = 666;

		if ( ! empty( $email ) ) {
			$this->sut->shouldReceive( 'get_hash' )->twice()->with( $email )->andReturn( 'HASH' );

			Functions\expect( 'is_multisite' )->once()->andReturn( true );
			$this->site_transients->shouldReceive( 'get' )->once()->with( 'check_HASH' )->andReturn( false );

			$this->sut->shouldReceive( 'ping_gravatar' )->once()->with( $email )->andReturn( $result );

			if ( false !== $result ) {
				$this->sut->shouldReceive( 'calculate_caching_duration' )->once()->with( $result, $age )->andReturn( $duration );
				$this->site_transients->shouldReceive( 'set' )->once()->with( 'check_HASH', $result, $duration )->andReturn( false );
			}
		}

		// Validate once.
		$this->assertSame( $final_result, $this->sut->validate( $email, $age ) );

		// Validate twice.
		$this->assertSame( $final_result, $this->sut->validate( $email, $age ) );
	}

	/**
	 * Tests ::validate in single site, with previous caching.
	 *
	 * @covers ::validate
	 */
	public function test_validate_cached_empty() {
		$email        = 'foobar@example.org';
		$age          = 77;
		$result       = false;
		$final_result = '';
		$duration     = 666;

		if ( ! empty( $email ) ) {
			$this->sut->shouldReceive( 'get_hash' )->twice()->with( $email )->andReturn( 'HASH' );

			Functions\expect( 'is_multisite' )->twice()->andReturn( false );
			$this->transients->shouldReceive( 'get' )->twice()->with( 'check_HASH' )->andReturn( $result );
			$this->sut->shouldReceive( 'ping_gravatar' )->twice()->with( $email )->andReturn( $result );
		}

		// Validate once.
		$this->assertSame( $final_result, $this->sut->validate( $email, $age ) );

		// Validate twice.
		$this->assertSame( $final_result, $this->sut->validate( $email, $age ) );
	}

	/**
	 * Tests ::validate in single site, with previous caching.
	 *
	 * @covers ::validate
	 */
	public function test_validate_cached() {
		$email    = 'foobar@example.org';
		$age      = 77;
		$result   = 'image/png';
		$duration = 666;

		if ( ! empty( $email ) ) {
			$this->sut->shouldReceive( 'get_hash' )->twice()->with( $email )->andReturn( 'HASH' );

			Functions\expect( 'is_multisite' )->once()->andReturn( false );
			$this->transients->shouldReceive( 'get' )->once()->with( 'check_HASH' )->andReturn( $result );
		}

		// Validate once.
		$this->assertSame( $result, $this->sut->validate( $email, $age ) );

		// Validate twice.
		$this->assertSame( $result, $this->sut->validate( $email, $age ) );
	}

	/**
	 * Tests ::ping_gravatar
	 *
	 * @covers ::ping_gravatar
	 */
	public function test_ping_gravatar_valid_account() {
		$email     = 'foobar@example.org';
		$response  = (object) [ 'the_response' ];
		$http_code = 200;

		$this->sut->shouldReceive( 'get_url' )->once()->with( $email )->andReturn( 'URL' );

		Functions\expect( 'wp_remote_head' )->once()->with( 'URL' )->andReturn( $response );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->with( $response )->andReturn( $http_code );
		Functions\expect( 'wp_remote_retrieve_header' )->once()->with( $response, 'content-type' )->andReturn( 'CONTENT_TYPE' );

		$this->assertSame( 'CONTENT_TYPE', $this->sut->ping_gravatar( $email ) );
	}

	/**
	 * Tests ::ping_gravatar
	 *
	 * @covers ::ping_gravatar
	 */
	public function test_ping_gravatar_no_account() {
		$email     = 'foobar@example.org';
		$response  = (object) [ 'the_response' ];
		$http_code = 404;

		$this->sut->shouldReceive( 'get_url' )->once()->with( $email )->andReturn( 'URL' );

		Functions\expect( 'wp_remote_head' )->once()->with( 'URL' )->andReturn( $response );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->with( $response )->andReturn( $http_code );

		$this->assertSame( 0, $this->sut->ping_gravatar( $email ) );
	}

	/**
	 * Tests ::ping_gravatar
	 *
	 * @covers ::ping_gravatar
	 */
	public function test_ping_gravatar_other_code() {
		$email     = 'foobar@example.org';
		$response  = (object) [ 'the_response' ];
		$http_code = 500;

		$this->sut->shouldReceive( 'get_url' )->once()->with( $email )->andReturn( 'URL' );

		Functions\expect( 'wp_remote_head' )->once()->with( 'URL' )->andReturn( $response );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->with( $response )->andReturn( $http_code );

		$this->assertFalse( $this->sut->ping_gravatar( $email ) );
	}

	/**
	 * Tests ::ping_gravatar
	 *
	 * @covers ::ping_gravatar
	 */
	public function test_ping_gravatar_error() {
		$email    = 'foobar@example.org';
		$response = m::mock( 'WP_Error' );

		$this->sut->shouldReceive( 'get_url' )->once()->with( $email )->andReturn( 'URL' );

		Functions\expect( 'wp_remote_head' )->once()->with( 'URL' )->andReturn( $response );

		$this->assertFalse( $this->sut->ping_gravatar( $email ) );
	}

	/**
	 * Provides data for testing calculate_caching_duration.
	 *
	 * @return array
	 */
	public function provide_calculate_caching_duration_data() {
		return [
			[ 'image/png', 0, 100 ],
			[ 'image/png', 99, 100 ],
			[ '', 0, 80 ],
			[ '', 100, 80 ],
		];
	}

	/**
	 * Tests ::calculate_caching_duration
	 *
	 * @covers ::calculate_caching_duration
	 *
	 * @dataProvider provide_calculate_caching_duration_data
	 *
	 * @param  string $result       The input result string.
	 * @param  int    $age          The input age.
	 * @param  int    $filter_value The filtered validation interval.
	 */
	public function test_calculate_caching_duration( $result, $age, $filter_value ) {
		Filters\expectApplied( 'avatar_privacy_validate_gravatar_interval' )->once()->with( m::type( 'int' ), ! empty( $result ), $age )->andReturn( $filter_value );

		$this->assertSame( $filter_value, $this->sut->calculate_caching_duration( $result, $age ) );
	}
}
