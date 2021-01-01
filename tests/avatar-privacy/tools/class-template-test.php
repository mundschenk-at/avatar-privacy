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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Tools;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Tools\Template;

/**
 * Avatar_Privacy\Tools\Template unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Tools\Template
 * @usesDefaultClass \Avatar_Privacy\Tools\Template
 */
class Template_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system under test.
	 *
	 * @var Template
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		$filesystem = [
			'plugin' => [
				'public' => [
					'partials' => [
						'foobar' => [
							'partial.php'           => 'MY_PARTIAL',
							'partial-with-args.php' => '<?php echo "MY_PARTIAL_WITH_{$foo}_{$bar}";',
						],
					],
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Partially mock system under test.
		$this->sut = m::mock( Template::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::get_gravatar_link_url.
	 *
	 * @covers ::get_gravatar_link_url
	 */
	public function test_get_gravatar_link_url() {
		$translated_url = 'https://language.gravatar.com/';

		Functions\expect( '__' )->once()->with( m::type( 'string' ), 'avatar-privacy' )->andReturn( $translated_url );

		$this->assertSame( $translated_url, Template::get_gravatar_link_url() );
	}

	/**
	 * Tests ::get_gravatar_link_rel.
	 *
	 * @covers ::get_gravatar_link_rel
	 */
	public function test_get_gravatar_link_rel() {
		Functions\expect( 'esc_attr' )->once()->with( m::type( 'string' ) )->andReturn( 'foo' );
		Filters\expectApplied( 'avatar_privacy_gravatar_link_rel' )->once()->with( 'noopener nofollow' )->andReturn( 'bar' );

		$this->assertSame( 'foo', Template::get_gravatar_link_rel() );
	}

	/**
	 * Tests ::get_gravatar_link_target.
	 *
	 * @covers ::get_gravatar_link_target
	 */
	public function test_get_gravatar_link_target() {
		Functions\expect( 'esc_attr' )->once()->with( m::type( 'string' ) )->andReturn( 'foo' );
		Filters\expectApplied( 'avatar_privacy_gravatar_link_target' )->once()->with( '_self' )->andReturn( 'bar' );

		$this->assertSame( 'foo', Template::get_gravatar_link_target() );
	}

	/**
	 * Tests ::print_partial.
	 *
	 * @covers ::print_partial
	 */
	public function test_print_partial() {
		$this->expectOutputString( 'MY_PARTIAL' );

		$this->assertNull( $this->sut->print_partial( 'public/partials/foobar/partial.php' ) );
	}

	/**
	 * Tests ::print_partial.
	 *
	 * @covers ::print_partial
	 */
	public function test_print_partial_with_variables() {
		$args = [
			'foo' => 'A',
			'bar' => 'VARIABLE',
		];

		$this->expectOutputString( 'MY_PARTIAL_WITH_A_VARIABLE' );

		$this->assertNull( $this->sut->print_partial( 'public/partials/foobar/partial-with-args.php', $args ) );
	}

	/**
	 * Tests ::print_partial.
	 *
	 * @covers ::print_partial
	 */
	public function test_print_partial_doing_it_wrong() {
		$args = [
			'foo' => 'A',
			'bar' => 'VARIABLE',
			666   => 'invalid',
		];

		Functions\expect( 'esc_html' )->once()->with( m::type( 'string' ) )->andReturn( 'error message' );
		Functions\expect( '_doing_it_wrong' )->once()->with( m::type( 'string' ), m::type( 'string' ), 'Avatar Privacy 2.4.0' );

		$this->expectOutputString( 'MY_PARTIAL_WITH_A_VARIABLE' );

		$this->assertNull( $this->sut->print_partial( 'public/partials/foobar/partial-with-args.php', $args ) );
	}

	/**
	 * Tests ::get_partial.
	 *
	 * @covers ::get_partial
	 */
	public function test_get_partial() {
		$partial = 'public/partials/foobar/partial.php';
		$args    = [
			'foo' => 'bar',
		];

		$result = 'Some template output';

		$this->sut->shouldReceive( 'print_partial' )->once()->with( $partial, $args )->andReturnUsing( function() use ( $result ) {
			echo $result; // phpcs:ignore WordPress.Security.EscapeOutput
		} );

		$this->assertSame( $result, $this->sut->get_partial( $partial, $args ) );
	}
}
