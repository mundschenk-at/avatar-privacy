<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2024 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Parts_Generator;

use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Exceptions\Part_Files_Not_Found_Exception;
use Avatar_Privacy\Tools\Number_Generator;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Parts_Generator unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Parts_Generator
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Parts_Generator
 *
 * @uses ::__construct
 */
class Parts_Generator_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Parts_Generator
	 */
	private $sut;

	/**
	 * The Number_Generator mock.
	 *
	 * @var Number_Generator
	 */
	private $number_generator;

	/**
	 * The site transients handler mock.
	 *
	 * @var Site_Transients
	 */
	private $site_transients;

	/**
	 * The parts_dir property.
	 *
	 * @var string
	 */
	private $parts_dir;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		// Properties.
		$this->parts_dir = '/some/fake/parts/dir';

		// Helper mocks.
		$this->number_generator = m::mock( Number_Generator::class );
		$this->site_transients  = m::mock( Site_Transients::class );

		// Partially mock system under test.
		$this->sut = m::mock( Parts_Generator::class, [
			$this->parts_dir,
			[ 'foo', 'bar' ],
			$this->number_generator,
			$this->site_transients,
		] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$fake_path  = 'some/fake/path';
		$part_types = [ 'foo', 'bar', 'baz' ];
		$rng        = m::mock( Number_Generator::class );
		$transients = m::mock( Site_Transients::class );
		$mock       = m::mock( Parts_Generator::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invoke_method( $mock, '__construct', [ $fake_path, $part_types, $rng, $transients ] );

		$this->assert_attribute_same( $fake_path, 'parts_dir', $mock );
		$this->assert_attribute_same( $part_types, 'part_types', $mock );
		$this->assert_attribute_same( $rng, 'number_generator', $mock );
		$this->assert_attribute_same( $transients, 'site_transients', $mock );
	}

	/**
	 * Tests ::build.
	 *
	 * @covers ::build
	 */
	public function test_build() {
		// Input data.
		$seed = 'fake email hash';
		$size = 42;

		// Intermediate results.
		$data  = 'fake image';
		$parts = [
			'body'  => 'body_2.png',
			'arms'  => 'arms_S8.png',
			'legs'  => 'legs_1.png',
			'mouth' => 'mouth_6.png',
		];
		$args  = [
			'fake' => 'args',
			'we'   => 'do not actually care for',
		];

		$this->number_generator->shouldReceive( 'seed' )->once()->with( $seed );
		$this->number_generator->shouldReceive( 'reset' )->once();

		$this->sut->shouldReceive( 'get_randomized_parts' )->once()->andReturn( $parts );
		$this->sut->shouldReceive( 'get_additional_arguments' )->once()->with( $seed, $size, $parts )->andReturn( $args );
		$this->sut->shouldReceive( 'get_avatar' )->once()->with( $size, $parts, $args )->andReturn( $data );

		$this->assertSame( $data, $this->sut->build( $seed, $size ) );
	}

	/**
	 * Tests ::build.
	 *
	 * @covers ::build
	 */
	public function test_build_error_during_parts_randomization() {
		// Input data.
		$seed = 'fake email hash';
		$size = 42;

		$this->number_generator->shouldReceive( 'seed' )->once()->with( $seed );
		$this->number_generator->shouldReceive( 'reset' )->once();

		$this->sut->shouldReceive( 'get_randomized_parts' )->once()->andThrow( Part_Files_Not_Found_Exception::class );
		$this->sut->shouldReceive( 'get_additional_arguments' )->never();
		$this->sut->shouldReceive( 'get_avatar' )->never();

		$this->assertFalse( $this->sut->build( $seed, $size ) );
	}

	/**
	 * Tests ::build.
	 *
	 * @covers ::build
	 */
	public function test_build_error_during_argument_preparation() {
		// Input data.
		$seed = 'fake email hash';
		$size = 42;

		// Intermediate results.
		$parts = [
			'body'  => 'body_2.png',
			'arms'  => 'arms_S8.png',
			'legs'  => 'legs_1.png',
			'mouth' => 'mouth_6.png',
		];

		$this->number_generator->shouldReceive( 'seed' )->once()->with( $seed );
		$this->number_generator->shouldReceive( 'reset' )->once();

		$this->sut->shouldReceive( 'get_randomized_parts' )->once()->andReturn( $parts );
		$this->sut->shouldReceive( 'get_additional_arguments' )->once()->with( $seed, $size, $parts )->andThrow( \RuntimeException::class );
		$this->sut->shouldReceive( 'get_avatar' )->never();

		$this->assertFalse( $this->sut->build( $seed, $size ) );
	}

	/**
	 * Tests ::build.
	 *
	 * @covers ::build
	 */
	public function test_build_error_during_avatar_generation() {
		$seed = 'fake email hash';
		$size = 42;

		// Intermediate results.
		$parts = [
			'body'  => 'body_2.png',
			'arms'  => 'arms_S8.png',
			'legs'  => 'legs_1.png',
			'mouth' => 'mouth_6.png',
		];
		$args  = [
			'fake' => 'args',
			'we'   => 'do not actually care for',
		];

		$this->number_generator->shouldReceive( 'seed' )->once()->with( $seed );
		$this->number_generator->shouldReceive( 'reset' )->once();

		$this->sut->shouldReceive( 'get_randomized_parts' )->once()->andReturn( $parts );
		$this->sut->shouldReceive( 'get_additional_arguments' )->once()->with( $seed, $size, $parts )->andReturn( $args );
		$this->sut->shouldReceive( 'get_avatar' )->once()->with( $size, $parts, $args )->andThrow( \RuntimeException::class );

		$this->assertFalse( $this->sut->build( $seed, $size ) );
	}

	/**
	 * Tests ::get_randomized_parts.
	 *
	 * @covers ::get_randomized_parts
	 */
	public function test_get_randomized_parts() {
		$found_parts = [
			'body'  => [ 'foo', 'bar' ],
			'arms'  => [],
			'legs'  => [],
			'mouth' => [ 'baz', 'foobar', 'barfoo' ],
		];

		// Expected result.
		$randomized_parts = [
			'body'  => [ 'foo' ],
			'arms'  => [],
			'legs'  => [],
			'mouth' => [ 'baz' ],
		];

		$this->sut->shouldReceive( 'get_parts' )->once()->andReturn( $found_parts );
		$this->sut->shouldReceive( 'randomize_parts' )->once()->with( $found_parts )->andReturn( $randomized_parts );

		// Run test.
		$this->assertSame( $randomized_parts, $this->sut->get_randomized_parts() );
	}

	/**
	 * Tests ::get_additional_arguments.
	 *
	 * @covers ::get_additional_arguments
	 */
	public function test_get_additional_arguments() {
		$seed  = 'fake email hash';
		$size  = 42;
		$parts = [
			'body'  => 'body_2.png',
			'arms'  => 'arms_S8.png',
			'legs'  => 'legs_1.png',
			'mouth' => 'mouth_6.png',
		];

		$this->assert_is_array( $this->sut->get_additional_arguments( $seed, $size, $parts ) );
	}

	/**
	 * Tests ::randomize_parts.
	 *
	 * @covers ::randomize_parts
	 */
	public function test_randomize_parts() {
		// Input data.
		$basename = 'monster-id';
		$path     = "/some/fake/path/$basename";
		$parts    = [
			'body'  => [
				'body_1.png',
				'body_2.png',
			],
			'arms'  => [
				'arms_S8.png',
			],
			'legs'  => [
				'legs_1.png',
			],
			'mouth' => [
				'mouth_6.png',
			],
		];

		$this->sut->shouldReceive( 'get_random_part_index' )->times( \count( $parts ) )->with( m::type( 'string' ), m::type( 'int' ) )->andReturn( 0 );

		// Run test.
		$result = $this->sut->randomize_parts( $parts );

		$this->assert_is_array( $result );
		$this->assertSame( \array_keys( $parts ), \array_keys( $result ) );
	}

	/**
	 * Tests ::get_random_part_index.
	 *
	 * @covers ::get_random_part_index
	 */
	public function test_get_random_part_index() {
		$count = 731;
		$type  = 'foobar';

		$result = 666;

		$this->number_generator->shouldReceive( 'get' )->once()->with( 0, $count - 1 )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_random_part_index( $type, $count ) );
	}

	/**
	 * Tests ::get_parts.
	 *
	 * @covers ::get_parts
	 */
	public function test_get_parts() {
		// Input data.
		$basename = \basename( $this->parts_dir );
		$parts    = [
			'body'  => [
				'body_1.png',
				'body_2.png',
			],
			'arms'  => [
				'arms_S8.png',
			],
			'legs'  => [
				'legs_1.png',
			],
			'mouth' => [
				'mouth_6.png',
			],
		];

		$this->site_transients->shouldReceive( 'get' )->once()->with( "avatar_privacy_{$basename}_parts" )->andReturn( false );
		$this->sut->shouldReceive( 'build_parts_array' )->once()->andReturn( $parts );
		$this->site_transients->shouldReceive( 'set' )->once()->with( "avatar_privacy_{$basename}_parts", $parts, m::type( 'int' ) );

		// Run test.
		$this->assertSame( $parts, $this->sut->get_parts() );
	}

	/**
	 * Tests ::get_parts.
	 *
	 * @covers ::get_parts
	 */
	public function test_get_parts_cached() {
		// Input data.
		$basename = \basename( $this->parts_dir );
		$parts    = [
			'body'  => [
				'body_1.png',
				'body_2.png',
			],
			'arms'  => [
				'arms_S8.png',
			],
			'legs'  => [
				'legs_1.png',
			],
			'mouth' => [
				'mouth_6.png',
			],
		];

		$this->site_transients->shouldReceive( 'get' )->once()->with( "avatar_privacy_{$basename}_parts" )->andReturn( $parts );
		$this->sut->shouldReceive( 'build_parts_array' )->never();
		$this->site_transients->shouldReceive( 'set' )->never();

		// Run test.
		$this->assertSame( $parts, $this->sut->get_parts() );
	}

	/**
	 * Tests ::get_parts.
	 *
	 * @covers ::get_parts
	 */
	public function test_get_parts_nothing_found() {
		// Input data.
		$basename = \basename( $this->parts_dir );
		$parts    = [];

		$this->site_transients->shouldReceive( 'get' )->once()->with( "avatar_privacy_{$basename}_parts" )->andReturn( false );
		$this->sut->shouldReceive( 'build_parts_array' )->once()->andReturn( $parts );
		$this->site_transients->shouldReceive( 'set' )->never();

		// Run test.
		$this->assertSame( $parts, $this->sut->get_parts() );
	}

	/**
	 * Tests ::build_parts_array.
	 *
	 * @covers ::build_parts_array
	 */
	public function test_build_parts_array() {
		$empty_parts  = \array_fill_keys( $this->get_value( $this->sut, 'part_types' ), [] );
		$parts        = [ 'unsorted' => 'parts' ];
		$sorted_parts = [ 'sorted' => 'parts' ];

		$this->sut->shouldReceive( 'read_parts_from_filesystem' )->once()->with( $empty_parts )->andReturn( $parts );
		$this->sut->shouldReceive( 'sort_parts' )->once()->with( $parts )->andReturn( $sorted_parts );

		// Run test.
		$this->assertSame( $sorted_parts, $this->sut->build_parts_array() );
	}

	/**
	 * Tests ::build_parts_array.
	 *
	 * @covers ::build_parts_array
	 */
	public function test_build_parts_array_error() {
		$empty_parts  = \array_fill_keys( $this->get_value( $this->sut, 'part_types' ), [] );
		$parts        = [ 'unsorted' => 'parts' ];
		$sorted_parts = [ 'sorted' => 'parts' ];

		$this->sut->shouldReceive( 'read_parts_from_filesystem' )->once()->with( $empty_parts )->andReturn( $empty_parts );

		Functions\expect( 'esc_html' )->once()->andReturnFirstArg();
		$this->expectException( Part_Files_Not_Found_Exception::class );

		$this->sut->shouldReceive( 'sort_parts' )->never();

		// Run test.
		$this->assertSame( $sorted_parts, $this->sut->build_parts_array() );
	}
}
