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
	 * Tests ::get_gravatar_link_rel.
	 *
	 * @covers ::get_gravatar_link_rel
	 */
	public function test_get_gravatar_link_rel() {
		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), '2.4.0', m::type( 'string' ) );

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
		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), '2.4.0', m::type( 'string' ) );

		Functions\expect( 'esc_attr' )->once()->with( m::type( 'string' ) )->andReturn( 'foo' );
		Filters\expectApplied( 'avatar_privacy_gravatar_link_target' )->once()->with( '_self' )->andReturn( 'bar' );

		$this->assertSame( 'foo', Template::get_gravatar_link_target() );
	}

	/**
	 * Provides data for testing ::get_uploader_description.
	 *
	 * @return array
	 */
	public function provide_get_uploader_description_data() {
		return [
			[ true, false, 'No local profile picture is set. Use the upload field to add a local profile picture or change your profile picture on <a href="https://en.gravatar.com/" rel="noopener nofollow" target="_self">Gravatar</a>.' ],
			[ true, true, 'Replace the local profile picture by uploading a new avatar, or erase it (falling back on <a href="https://en.gravatar.com/" rel="noopener nofollow" target="_self">Gravatar</a>) by checking the delete option.' ],
			[ false, false, 'No local profile picture is set. Change your profile picture on <a href="https://en.gravatar.com/" rel="noopener nofollow" target="_self">Gravatar</a>.' ],
			[ false, true, 'You do not have media management permissions. To change your local profile picture, contact the site administrator.' ],
		];
	}

	/**
	 * Tests ::get_uploader_description.
	 *
	 * @covers ::get_uploader_description
	 *
	 * @dataProvider provide_get_uploader_description_data
	 *
	 * @param  bool   $can_upload       Whether the user can upload files.
	 * @param  bool   $has_local_avatar Whether a local avatar has been set previously.
	 * @param  string $result           The expected result.
	 */
	public function test_get_uploader_description( $can_upload, $has_local_avatar, $result ) {
		Functions\expect( '__' )->once()->with( m::type( 'string' ), 'avatar-privacy' )->andReturnFirstArg();

		$this->sut->shouldReceive( 'fill_in_gravatar_url' )->atMost()->once()->with( m::type( 'string' ) )->andReturnUsing( function( $message ) {
			return \sprintf( $message, 'https://en.gravatar.com/', 'noopener nofollow', '_self' );
		} );

		$this->assertSame( $result, $this->sut->get_uploader_description( $can_upload, $has_local_avatar ) );
	}

	/**
	 * Provides data for testing ::get_use_gravatar_label.
	 *
	 * @return array
	 */
	public function provide_get_use_gravatar_label_data() {
		return [
			[ 'user', true ],
			[ 'comment', true ],
			[ 'foobar', false ],
		];
	}

	/**
	 * Tests ::get_use_gravatar_label.
	 *
	 * @covers ::get_use_gravatar_label
	 *
	 * @dataProvider provide_get_use_gravatar_label_data
	 *
	 * @param  string $context The context.
	 * @param  bool   $success Whether the call is epxected to be successful.
	 */
	public function test_get_use_gravatar_label( $context, $success ) {
		$result = $success ? 'Label with link' : '';

		if ( $success ) {
			Functions\expect( '__' )->once()->with( m::type( 'string' ), 'avatar-privacy' )->andReturnFirstArg();
			$this->sut->shouldReceive( 'fill_in_gravatar_url' )->once()->with( m::type( 'string' ) )->andReturn( $result );
		} else {
			Functions\expect( '__' )->never();
			$this->sut->shouldReceive( 'fill_in_gravatar_url' )->never();
			Functions\expect( 'esc_html' )->once()->with( m::type( 'string' ) )->andReturnFirstArg();
			Functions\expect( '_doing_it_wrong' )->once()->with( m::type( 'string' ), "Invalid context $context", '2.4.0' );
		}

		$this->assertSame( $result, $this->sut->get_use_gravatar_label( $context ) );
	}

	/**
	 * Tests ::fill_in_gravatar_url.
	 *
	 * @covers ::fill_in_gravatar_url
	 */
	public function test_fill_in_gravatar_url() {
		$message = 'My test: <a href="%1$s" rel="%2$s" target="%3$s">Foo</a> bar.';
		$result  = 'My test: <a href="https://en.gravatar.com/" rel="nofollow" target="_blank">Foo</a> bar.';

		Functions\expect( '__' )->once()->with( m::type( 'string' ), 'avatar-privacy' )->andReturnFirstArg();
		$this->sut->shouldReceive( 'get_gravatar_link_rel_attribute' )->once()->andReturn( 'nofollow' );
		$this->sut->shouldReceive( 'get_gravatar_link_target_attribute' )->once()->andReturn( '_blank' );

		$this->assertSame( $result, $this->sut->fill_in_gravatar_url( $message ) );
	}

	/**
	 * Tests ::get_gravatar_link_rel_attribute.
	 *
	 * @covers ::get_gravatar_link_rel_attribute
	 */
	public function test_get_gravatar_link_rel_attribute() {
		Functions\expect( 'esc_attr' )->once()->with( m::type( 'string' ) )->andReturn( 'foo' );
		Filters\expectApplied( 'avatar_privacy_gravatar_link_rel' )->once()->with( 'noopener nofollow' )->andReturn( 'bar' );

		$this->assertSame( 'foo', $this->sut->get_gravatar_link_rel_attribute() );
	}

	/**
	 * Tests ::get_gravatar_link_target_attribute.
	 *
	 * @covers ::get_gravatar_link_target_attribute
	 */
	public function test_get_gravatar_link_target_attribute() {
		Functions\expect( 'esc_attr' )->once()->with( m::type( 'string' ) )->andReturn( 'foo' );
		Filters\expectApplied( 'avatar_privacy_gravatar_link_target' )->once()->with( '_self' )->andReturn( 'bar' );

		$this->assertSame( 'foo', $this->sut->get_gravatar_link_target_attribute() );
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
