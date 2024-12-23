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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Tools\Network;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Tools\Network\Gravatar_Service;

use Avatar_Privacy\Data_Storage\Transients;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Tools\Images\Editor;


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
	 * @var Gravatar_Service&m\MockInterface
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Transients&m\MockInterface
	 */
	private $transients;

	/**
	 * Required helper object.
	 *
	 * @var Site_Transients&m\MockInterface
	 */
	private $site_transients;

	/**
	 * Required helper object.
	 *
	 * @var Editor&m\MockInterface
	 */
	private $editor;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		// Mock required helpers.
		$this->transients      = m::mock( Transients::class );
		$this->site_transients = m::mock( Site_Transients::class );
		$this->editor          = m::mock( Editor::class );

		$this->sut = m::mock( Gravatar_Service::class, [ $this->transients, $this->site_transients, $this->editor ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Gravatar_Service::class )->makePartial();

		$mock->__construct( $this->transients, $this->site_transients, $this->editor );

		$this->assert_attribute_same( $this->transients, 'transients', $mock );
		$this->assert_attribute_same( $this->site_transients, 'site_transients', $mock );
		$this->assert_attribute_same( $this->editor, 'editor', $mock );
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

		$this->editor->shouldReceive( 'get_mime_type' )->once()->andReturn( 'image/png' );

		$this->assertSame( 'IMAGEDATA', $this->sut->get_image( $email, $size, $rating ) );
	}

	/**
	 * Tests ::get_image.
	 *
	 * @covers ::get_image
	 */
	public function test_get_image_wrong_mime_type() {
		$email    = 'foo@bar.org';
		$size     = 99;
		$rating   = 'r';
		$response = (object) [ 'the_response' ];

		$this->sut->shouldReceive( 'get_url' )->once()->with( $email, $size, $rating )->andReturn( 'URL' );

		Functions\expect( 'wp_remote_get' )->once()->with( 'URL' )->andReturn( $response );
		Functions\expect( 'wp_remote_retrieve_body' )->once()->with( $response )->andReturn( 'IMAGEDATA' );

		$this->editor->shouldReceive( 'get_mime_type' )->once()->andReturn( false );

		$this->assertSame( '', $this->sut->get_image( $email, $size, $rating ) );
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
	public function test_validate( $email, $age, $result ) {
		$hash = 'FAKEHASH';

		if ( ! empty( $email ) ) {
			$this->sut->shouldReceive( 'get_hash' )->twice()->with( $email )->andReturn( $hash );

			Functions\expect( 'is_multisite' )->once()->andReturn( false );

			$this->sut->shouldReceive( 'validate_and_cache' )->once()->with( $this->transients, $email, $hash, $age )->andReturn( $result );
		}

		// Validate once.
		$this->assertSame( $result, $this->sut->validate( $email, $age ) );

		// Simulate cache warming.
		$validation_cache          = $this->get_value( $this->sut, 'validation_cache' );
		$validation_cache[ $hash ] = $result;
		$this->set_value( $this->sut, 'validation_cache', $validation_cache );

		// Validate twice.
		$this->assertSame( $result, $this->sut->validate( $email, $age ) );
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
		$hash = 'OTHERHASH';

		if ( ! empty( $email ) ) {
			$this->sut->shouldReceive( 'get_hash' )->twice()->with( $email )->andReturn( $hash );

			Functions\expect( 'is_multisite' )->once()->andReturn( true );

			$this->sut->shouldReceive( 'validate_and_cache' )->once()->with( $this->site_transients, $email, $hash, $age )->andReturn( $result );
		}

		// Validate once.
		$this->assertSame( $result, $this->sut->validate( $email, $age ) );

		// Simulate cache warming.
		$validation_cache          = $this->get_value( $this->sut, 'validation_cache' );
		$validation_cache[ $hash ] = $result;
		$this->set_value( $this->sut, 'validation_cache', $validation_cache );

		// Validate twice.
		$this->assertSame( $result, $this->sut->validate( $email, $age ) );
	}

	/**
	 * Tests ::validate_and_cache with successful caching.
	 *
	 * @covers ::validate_and_cache
	 */
	public function test_validate_and_cache_success() {
		// Parameters.
		$transients = m::mock( Transients::class );
		$email      = 'foobar@example.org';
		$hash       = '<HASH>';
		$age        = 77;

		// Intermediary data.
		$duration = 666;
		$result   = 'image/jpeg';

		$transients->shouldReceive( 'get' )->twice()->with( m::type( 'string' ) )->andReturn( false, $result );
		$this->sut->shouldReceive( 'ping_gravatar' )->once()->with( $email )->andReturn( $result );
		$this->sut->shouldReceive( 'calculate_caching_duration' )->once()->with( $result, $age )->andReturn( $duration );
		$transients->shouldReceive( 'set' )->once()->with( m::type( 'string' ), $result, $duration );

		// Validate once.
		$this->assertSame( $result, $this->sut->validate_and_cache( $transients, $email, $hash, $age ) );

		// Validate twice.
		$this->assertSame( $result, $this->sut->validate_and_cache( $transients, $email, $hash, $age ) );
	}

	/**
	 * Tests ::validate_and_cache without caching (due to a ping error).
	 *
	 * @covers ::validate_and_cache
	 */
	public function test_validate_and_cache_with_empty_result() {
		$transients = m::mock( Transients::class );
		$email      = 'foobar@example.org';
		$hash       = '<HASH>';
		$age        = 77;

		$transients->shouldReceive( 'get' )->twice()->with( m::type( 'string' ) )->andReturn( false );
		$transients->shouldReceive( 'set' )->never();
		$this->sut->shouldReceive( 'ping_gravatar' )->twice()->with( $email )->andReturn( false );

		// Validate once.
		$this->assertSame( '', $this->sut->validate_and_cache( $transients, $email, $hash, $age ) );

		// Validate twice.
		$this->assertSame( '', $this->sut->validate_and_cache( $transients, $email, $hash, $age ) );
	}

	/**
	 * Tests ::validate_and_cache with previous caching.
	 *
	 * @covers ::validate_and_cache
	 */
	public function test_validate_and_cache_with_cached_result() {
		$transients = m::mock( Transients::class );
		$email      = 'foobar@example.org';
		$hash       = '<HASH>';
		$age        = 77;
		$result     = 'image/png';

		$transients->shouldReceive( 'get' )->once()->with( m::type( 'string' ) )->andReturn( $result );
		$transients->shouldReceive( 'set' )->never();
		$this->sut->shouldReceive( 'ping_gravatar' )->never();

		$this->assertSame( $result, $this->sut->validate_and_cache( $transients, $email, $hash, $age ) );
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
	public function test_ping_gravatar_multiple_content_type_headers() {
		$email     = 'foobar@example.org';
		$response  = (object) [ 'the_response' ];
		$http_code = 200;

		$content_type = [
			'CONTENT_TYPE_1',
			'CONTENT_TYPE_2',
		];

		$this->sut->shouldReceive( 'get_url' )->once()->with( $email )->andReturn( 'URL' );

		Functions\expect( 'wp_remote_head' )->once()->with( 'URL' )->andReturn( $response );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->with( $response )->andReturn( $http_code );
		Functions\expect( 'wp_remote_retrieve_header' )->once()->with( $response, 'content-type' )->andReturn( $content_type );

		$this->assertSame( $content_type[0], $this->sut->ping_gravatar( $email ) );
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

		$this->assertSame( '', $this->sut->ping_gravatar( $email ) );
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
			[ 'image/png', 0, \WEEK_IN_SECONDS ],
			[ 'image/png', 99, \WEEK_IN_SECONDS ],
			[ 'image/png', 5 * \WEEK_IN_SECONDS, \WEEK_IN_SECONDS ],
			[ false, \DAY_IN_SECONDS + 1, \DAY_IN_SECONDS ],
			[ false, \DAY_IN_SECONDS, \HOUR_IN_SECONDS ],
			[ '', 5 * \WEEK_IN_SECONDS, \WEEK_IN_SECONDS ],
			[ '', 0, 10 * \MINUTE_IN_SECONDS ],
			[ '', \HOUR_IN_SECONDS, 10 * \MINUTE_IN_SECONDS ],
			[ '', \HOUR_IN_SECONDS + 1, \HOUR_IN_SECONDS ],
			[ '', 0, 10 * \MINUTE_IN_SECONDS ],
		];
	}

	/**
	 * Tests ::calculate_caching_duration
	 *
	 * @covers ::calculate_caching_duration
	 *
	 * @dataProvider provide_calculate_caching_duration_data
	 *
	 * @param  string $ping_result     The input result string.
	 * @param  int    $age             The input age.
	 * @param  int    $expected_result The expected duration.
	 */
	public function test_calculate_caching_duration( $ping_result, $age, $expected_result ) {
		Filters\expectApplied( 'avatar_privacy_validate_gravatar_interval' )->once()->with( m::type( 'int' ), ! empty( $ping_result ), $age )->andReturnFirstArg();

		$this->assertSame( $expected_result, $this->sut->calculate_caching_duration( $ping_result, $age ) );
	}
}
