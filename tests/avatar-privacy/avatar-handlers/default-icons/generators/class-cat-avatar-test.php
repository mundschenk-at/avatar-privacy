<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019 Peter Putzer.
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
use org\bovigo\vfs\vfsStreamDirectory;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Cat_Avatar;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator;
use Avatar_Privacy\Tools\Images\Editor;


/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Cat_Avatar unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Cat_Avatar
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Cat_Avatar
 *
 * @uses ::__construct
 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator::__construct
 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator::__construct
 */
class Cat_Avatar_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Cat_Avatar
	 */
	private $sut;

	/**
	 * The Images\Editor mock.
	 *
	 * @var Editor
	 */
	private $editor;

	/**
	 * The full path of the folder containing the real images.
	 *
	 * @var string
	 */
	private $real_image_path;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$png_data = \base64_decode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl' .
			'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr' .
			'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r' .
			'8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg=='
		);

		$filesystem = [
			'plugin' => [
				'public' => [
					'images' => [
						'cats'       => [
							'back.png'    => $png_data,
							'body_1.png'  => $png_data,
							'body_2.png'  => $png_data,
							'arms_S8.png' => $png_data,
							'legs_1.png'  => $png_data,
							'mouth_6.png' => $png_data,
						],
						'cats-empty' => [],
					],
				],
			],
		];

		// Set up virtual filesystem.
		$root = vfsStream::setup( 'root', null, $filesystem );

		// Provide access to the real images.
		$this->real_image_path = \dirname( \dirname( \dirname( \dirname( \dirname( __DIR__ ) ) ) ) ) . '/public/images/cats';

		// Helper mocks.
		$this->editor = m::mock( Editor::class );

		// Partially mock system under test.
		$this->sut = m::mock( Cat_Avatar::class )->makePartial()->shouldAllowMockingProtectedMethods();

		// Manually invoke the constructor as it is protected.
		$this->invokeMethod( $this->sut, '__construct', [ $this->editor ] );

		// Override the parts directory.
		$this->setValue( $this->sut, 'parts_dir', vfsStream::url( 'root/plugin/public/images/cats' ) );
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$editor = m::mock( Editor::class );
		$mock   = m::mock( Cat_Avatar::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invokeMethod( $mock, '__construct', [ $editor ] );

		// An attribute of the PNG_Generator superclass.
		$this->assertAttributeSame( $editor, 'images', $mock );
	}

	/**
	 * Tests ::build.
	 *
	 * @covers ::build
	 */
	public function test_build() {
		$seed = 'fake email hash';
		$size = 42;
		$data = 'fake SVG image';

		// Intermediate results.
		$parts        = [
			'body'  => 'body_2.png',
			'arms'  => 'arms_S8.png',
			'legs'  => 'legs_1.png',
			'mouth' => 'mouth_6.png',
		];
		$parts_number = \count( $parts );

		$this->sut->shouldReceive( 'get_randomized_parts' )->once()->with( m::type( 'callable' ) )->andReturn( $parts );

		// The method takes int arguments in theory, but might be floats.
		$this->sut->shouldReceive( 'apply_image' )->times( $parts_number )->with( m::type( 'resource' ), m::type( 'string' ), Cat_Avatar::SIZE, Cat_Avatar::SIZE );

		$this->sut->shouldReceive( 'get_resized_image_data' )->once()->with( m::type( 'resource' ), $size )->andReturn( $data );

		$this->assertSame( $data, $this->sut->build( $seed, $size ) );
	}
}
