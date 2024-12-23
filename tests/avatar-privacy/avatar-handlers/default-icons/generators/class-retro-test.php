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

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Retro;

use Avatar_Privacy\Tools\Number_Generator;
use Avatar_Privacy\Tools\Template;

use Colors\RandomColor;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Retro unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Retro
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Retro
 *
 * @uses ::__construct
 */
class Retro_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Retro&m\MockInterface
	 */
	private Retro $sut;

	/**
	 * Alias mock of Colors\RandomColor.
	 *
	 * @var \Colors\RandomColor&m\MockInterface
	 */
	private RandomColor $random_color;

	/**
	 * The random number generator.
	 *
	 * @var Number_Generator&m\MockInterface
	 */
	private Number_Generator $number_generator;

	/**
	 * The Template alias mock.
	 *
	 * @var Template&m\MockInterface
	 */
	private Template $template;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		// Helper mocks.
		$this->random_color     = m::mock( 'alias:' . RandomColor::class );
		$this->number_generator = m::mock( Number_Generator::class );
		$this->template         = m::mock( Template::class );

		// Partially mock system under test.
		$this->sut = m::mock( Retro::class )->makePartial()->shouldAllowMockingProtectedMethods();

		// Manually invoke the constructor as it is protected.
		$this->invoke_method( $this->sut, '__construct', [
			$this->number_generator,
			$this->template,
		] );
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$number_generator = m::mock( Number_Generator::class );
		$template         = m::mock( Template::class );
		$mock             = m::mock( Retro::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invoke_method( $mock, '__construct', [ $number_generator, $template ] );

		$this->assert_attribute_same( $number_generator, 'number_generator', $mock );
		$this->assert_attribute_same( $template, 'template', $mock );
	}

	/**
	 * Tests ::build.
	 *
	 * @covers ::build
	 */
	public function test_build() {
		$seed     = 'fake email hash';
		$seed_md5 = \md5( $seed );
		$size     = 42;
		$data     = 'fake SVG image';

		// Intermediate.
		$bright_color = 'A bright color definition';
		$light_color  = 'A light color definition';
		$bitmap       = [
			[ true, false, false, false, true ],
			[ false, true, false, true, false ],
			[ false, true, true, true, false ],
			[ true, true, false, true, true ],
			[ true, false, false, false, true ],
		];
		$path         = 'a fake SVG path string';

		$this->number_generator->shouldReceive( 'seed' )->once()->with( $seed );
		$this->number_generator->shouldReceive( 'reset' )->once();

		$this->random_color->shouldReceive( 'one' )->once()->with( [ 'luminosity' => 'bright' ] )->andReturn( $bright_color );
		$this->random_color->shouldReceive( 'one' )->once()->with( [ 'luminosity' => 'light' ] )->andReturn( $light_color );

		$this->sut->shouldReceive( 'get_bitmap' )->once()->with( $seed_md5 )->andReturn( $bitmap );
		$this->sut->shouldReceive( 'draw_path' )->once()->with( $bitmap )->andReturn( $path );

		$this->template->shouldReceive( 'get_partial' )->once()->with( 'public/partials/retro/svg.php', m::subset( [
			'rows'     => 5,
			'columns'  => 5,
			'path'     => $path,
			'color'    => $bright_color,
			'bg_color' => $light_color,
		] ) )->andReturn( $data );

		$this->assertSame( $data, $this->sut->build( $seed, $size ) );
	}

	/**
	 * Provides data for testing ::get_bitmap.
	 *
	 * @return array<array{ 0: string, 1: mixed[] }>
	 */
	public function provide_get_bitmap_data(): array {
		return [
			[
				'9737e3144aeeef544da072c45cbaf536',
				[
					[ true, false, true, false, true ],
					[ false, false, true, false, false ],
					[ true, true, false, true, true ],
					[ true, true, true, true, true ],
					[ true, true, true, true, true ],
				],
			],
			[
				'546c29d006818aa6d9ff710c2122a6b7',
				[
					[ true, true, false, true, true ],
					[ true, false, true, false, true ],
					[ true, true, true, true, true ],
					[ true, true, false, true, true ],
					[ false, false, true, false, false ],
				],
			],
		];
	}

	/**
	 * Tests the ::get_bitmap method.
	 *
	 * @covers ::get_bitmap
	 *
	 * @dataProvider provide_get_bitmap_data
	 *
	 * @param string $hash   The input MD5 hash.
	 * @param array  $result The expected result.
	 *
	 * @return void
	 *
	 * @phpstan-param mixed[] $result
	 */
	public function test_get_bitmap( string $hash, array $result ): void {
		$this->assertSame( $result, $this->sut->get_bitmap( $hash ) );
	}

	/**
	 * Provides data for testing ::draw_path.
	 *
	 * @return array<array{ 0: mixed[], 1: string }>
	 */
	public function provide_draw_path_data(): array {
		return [
			[
				[
					[ true, false, true, false, true ],
					[ false, false, true, false, false ],
					[ true, true, false, true, true ],
					[ true, true, true, true, true ],
					[ true, true, true, true, true ],
				],
				'M0,0h1v1h-1v-1M2,0h1v1h-1v-1M4,0h1v1h-1v-1M2,1h1v1h-1v-1M0,2h1v1h-1v-1M1,2h1v1h-1v-1M3,2h1v1h-1v-1M4,2h1v1h-1v-1M0,3h1v1h-1v-1M1,3h1v1h-1v-1M2,3h1v1h-1v-1M3,3h1v1h-1v-1M4,3h1v1h-1v-1M0,4h1v1h-1v-1M1,4h1v1h-1v-1M2,4h1v1h-1v-1M3,4h1v1h-1v-1M4,4h1v1h-1v-1',
			],
			[
				[
					[ true, true, false, true, true ],
					[ true, false, true, false, true ],
					[ true, true, true, true, true ],
					[ true, true, false, true, true ],
					[ false, false, true, false, false ],
				],
				'M0,0h1v1h-1v-1M1,0h1v1h-1v-1M3,0h1v1h-1v-1M4,0h1v1h-1v-1M0,1h1v1h-1v-1M2,1h1v1h-1v-1M4,1h1v1h-1v-1M0,2h1v1h-1v-1M1,2h1v1h-1v-1M2,2h1v1h-1v-1M3,2h1v1h-1v-1M4,2h1v1h-1v-1M0,3h1v1h-1v-1M1,3h1v1h-1v-1M3,3h1v1h-1v-1M4,3h1v1h-1v-1M2,4h1v1h-1v-1',
			],
		];
	}

	/**
	 * Tests the ::draw_path method.
	 *
	 * @covers ::draw_path
	 *
	 * @dataProvider provide_draw_path_data
	 *
	 * @param array  $bitmap The input bitmap.
	 * @param string $result The expected result.
	 *
	 * @return void
	 *
	 * @phpstan-param mixed[] $bitmap
	 */
	public function test_draw_path( array $bitmap, string $result ): void {
		$this->assertSame( $result, $this->sut->draw_path( $bitmap ) );
	}
}
