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

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Robohash;

use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Tools\Number_Generator;
use Avatar_Privacy\Tools\Template;


/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Robohash unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Robohash
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Robohash
 */
class Robohash_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Robohash&m\MockInterface
	 */
	private $sut;

	/**
	 * The Number_Generator mock.
	 *
	 * @var Number_Generator&m\MockInterface
	 */
	private $number_generator;

	/**
	 * The Template alias mock.
	 *
	 * @var Template&m\MockInterface
	 */
	private $template;

	/**
	 * The full path of the folder containing the real images.
	 *
	 * @var string
	 */
	private $real_image_path;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$filesystem = [
			'plugin' => [
				'public' => [
					'images'   => [
						'robohash'       => [
							'body'        => [
								'body-01.svg' => '<svg />',
								'body-02.svg' => '<svg />',
							],
							'accessory'   => [
								'accessory-01.svg' => '<svg />',
								'accessory-02.svg' => '<svg />',
								'accessory-03.svg' => '<svg />',
							],
							'eyes'        => [
								'eyes-01.svg' => '<svg />',
							],
						],
						'robohash-empty' => [],
					],
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Provide access to the real images.
		$this->real_image_path = \dirname( \dirname( \dirname( \dirname( \dirname( __DIR__ ) ) ) ) ) . '/public/images/birds';

		// Helper mocks.
		$this->number_generator = m::mock( Number_Generator::class );
		$this->template         = m::mock( Template::class );

		// Partially mock system under test.
		$this->sut = m::mock( Robohash::class )->makePartial()->shouldAllowMockingProtectedMethods();

		// Set the necessary properties as the constructor is never invoked.
		$this->set_value( $this->sut, 'number_generator', $this->number_generator );
		$this->set_value( $this->sut, 'template', $this->template );
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Parts_Generator::__construct
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator::__construct
	 */
	public function test_constructor() {
		$transients       = m::mock( Site_Transients::class );
		$number_generator = m::mock( Number_Generator::class );
		$template         = m::mock( Template::class );
		$mock             = m::mock( Robohash::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invoke_method( $mock, '__construct', [ $number_generator, $transients, $template ] );

		$this->assert_attribute_same( $template, 'template', $mock );
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
			'body'  => 'fake_part.svg',
			'arms'  => 'fake_part.svg',
		];

		$this->number_generator->shouldReceive( 'get' )->times( 2 )->with( 0, m::type( 'int' ) )->andReturn( 3, 2 );

		$result = $this->sut->get_additional_arguments( $seed, $size, $parts );

		$this->assert_is_array( $result );
		$this->assertArrayHasKey( 'color', $result );
		$this->assertArrayHasKey( 'bg_color', $result );
	}

	/**
	 * Tests ::get_avatar.
	 *
	 * @covers ::get_avatar
	 */
	public function test_get_avatar() {
		// Input.
		$size  = 42;
		$parts = [
			'body'      => '<svg>body</svg>',
			'face'      => '<svg>face</svg>',
			'eyes'      => '<svg>eyes</svg>',
			'mouth'     => '<svg>mouth</svg>',
			'accessory' => '<svg>accessory</svg>',
		];
		$args  = [
			'color'    => '#fake_color1',
			'bg_color' => '#fake_color2',
		];

		// Result.
		$svg_image = 'some SVG data';

		$this->template->shouldReceive( 'get_partial' )->once()->with(
			'public/partials/robohash/svg.php',
			[
				// Colors.
				'color'     => $args['color'],
				'bg_color'  => $args['bg_color'],
				// Robot parts.
				'body'      => $parts['body'],
				'face'      => $parts['face'],
				'eyes'      => $parts['eyes'],
				'mouth'     => $parts['mouth'],
				'accessory' => $parts['accessory'],
			]
		)->andReturn( $svg_image );

		$this->assertSame( $svg_image, $this->sut->get_avatar( $size, $parts, $args ) );
	}

	/**
	 * Tests ::read_parts_from_filesystem.
	 *
	 * @covers ::read_parts_from_filesystem
	 */
	public function test_read_parts_from_filesystem() {
		// Input data.
		$parts = \array_fill_keys( [ 'body', 'arms', 'mouth', 'eyes', 'accessory' ], [] );

		// Expected result.
		$result = [
			'body'      => [
				'body-01.svg' => 'PREPARED_SVG_PART',
				'body-02.svg' => 'PREPARED_SVG_PART',
			],
			'arms'      => [],
			'mouth'     => [],
			'eyes'      => [
				'eyes-01.svg' => 'PREPARED_SVG_PART',
			],
			'accessory' => [
				'accessory-01.svg' => 'PREPARED_SVG_PART',
				'accessory-02.svg' => 'PREPARED_SVG_PART',
				'accessory-03.svg' => 'PREPARED_SVG_PART',
			],
		];

		$files           = \array_filter( $result, function ( $value ) {
			return \is_array( $value ) && ! empty( $value );
		} );
		$number_of_files = \count( $files, \COUNT_RECURSIVE ) - \count( $files );

		// Override necessary properties.
		$this->set_value( $this->sut, 'parts_dir', vfsStream::url( 'root/plugin/public/images/robohash' ) );

		$this->sut->shouldReceive( 'prepare_svg_part' )->times( $number_of_files )->with( m::type( 'string' ) )->andReturn( 'PREPARED_SVG_PART' );

		// Run test.
		$this->assertSame( $result, $this->sut->read_parts_from_filesystem( $parts ) );
	}

	/**
	 * Tests ::read_parts_from_filesystem.
	 *
	 * @covers ::read_parts_from_filesystem
	 */
	public function test_read_parts_from_filesystem_incorrect_parts_dir() {
		// Input data.
		$parts = \array_fill_keys( [ 'body', 'arms', 'mouth', 'eyes', 'accessory' ], [] );

		// Override necessary properties.
		$this->set_value( $this->sut, 'parts_dir', vfsStream::url( 'root/plugin/public/images/robohash-empty' ) );

		// Run test.
		$this->assertSame( $parts, $this->sut->read_parts_from_filesystem( $parts ) );
	}

	/**
	 * Tests ::sort_parts.
	 *
	 * @covers ::sort_parts
	 */
	public function test_sort_parts() {
		// Input data.
		$parts = [
			'body'  => [
				'body-1.svg'  => '<svg>body1</svg>',
				'body-10.svg' => '<svg>body10</svg>',
				'body-2.svg'  => '<svg>body2</svg>',
				'body-3.svg'  => '<svg>body3</svg>',
				'body-4.svg'  => '<svg>body4</svg>',
			],
			'arms'  => [
				'arms-3.svg'  => '<svg>arms3</svg>',
				'arms-2.svg'  => '<svg>arms2</svg>',
				'arms-1.svg'  => '<svg>arms1</svg>',
				'arms-10.svg' => '<svg>arms10</svg>',
				'arms-4.svg'  => '<svg>arms4</svg>',
			],
			'mouth' => [
				'mouth-4.svg'  => '<svg>mouth4</svg>',
			],
		];

		// Expected result.
		$result = [
			'body'  => [
				'<svg>body1</svg>',
				'<svg>body2</svg>',
				'<svg>body3</svg>',
				'<svg>body4</svg>',
				'<svg>body10</svg>',
			],
			'arms'  => [
				'<svg>arms1</svg>',
				'<svg>arms2</svg>',
				'<svg>arms3</svg>',
				'<svg>arms4</svg>',
				'<svg>arms10</svg>',
			],
			'mouth' => [
				'<svg>mouth4</svg>',
			],
		];

		// Run test.
		$this->assertSame( $result, $this->sut->sort_parts( $parts ) );
	}

	/**
	 * Tests ::prepare_svg_part.
	 *
	 * @covers ::prepare_svg_part
	 */
	public function test_prepare_svg_part() {
		$part   = '<svg some="attribute"><rect stroke="#26a9e0" /></svg>';
		$result = '<g transform="translate(0,20)"><rect stroke="currentColor" /></g>';

		$this->assertSame( $result, $this->sut->prepare_svg_part( $part ) );
	}
}
