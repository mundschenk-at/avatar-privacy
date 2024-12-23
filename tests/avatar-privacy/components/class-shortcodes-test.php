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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Components\Shortcodes;

use Avatar_Privacy\Tools\HTML\User_Form;

/**
 * Avatar_Privacy\Components\Shortcodes unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Components\Shortcodes
 * @usesDefaultClass \Avatar_Privacy\Components\Shortcodes
 *
 * @uses ::__construct
 */
class Shortcodes_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Shortcodes&m\MockInterface
	 */
	private $sut;


	/**
	 * Mocked helper object.
	 *
	 * @var User_Form&m\MockInterface
	 */
	private $form;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$this->form = m::mock( User_Form::class );
		$this->sut  = m::mock( Shortcodes::class, [ $this->form ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Shortcodes::class )->makePartial();

		$mock->__construct( $this->form );

		$this->assert_attribute_same( $this->form, 'form', $mock );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Functions\expect( 'is_admin' )->once()->andReturn( false );

		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'add_shortcodes' ] );

		$this->form->shouldReceive( 'register_form_submission' )->once();

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run_admin() {
		Functions\expect( 'is_admin' )->once()->andReturn( true );

		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'add_shortcodes' ] );

		$this->form->shouldReceive( 'register_form_submission' )->never();

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::add_shortcodes.
	 *
	 * @covers ::add_shortcodes
	 */
	public function test_add_shortcodes() {
		Functions\expect( 'add_shortcode' )->once()->with( 'avatar-privacy-form', [ $this->sut, 'render_frontend_form_shortcode' ] );

		$this->assertNull( $this->sut->add_shortcodes() );
	}

	/**
	 * Tests ::render_frontend_form_shortcode.
	 *
	 * @covers ::render_frontend_form_shortcode
	 */
	public function test_render_frontend_form_shortcode() {
		// Input data.
		$atts = [
			'avatar_size' => 120,
		];

		// Systme state.
		$user_id = 42;

		// Result.
		$shortcode = 'my shortcode markup';

		Functions\expect( 'get_current_user_id' )->once()->andReturn( $user_id );

		$this->sut->shouldReceive( 'sanitize_frontend_form_attributes' )->once()->with( $atts )->andReturn( $atts );
		$this->form->shouldReceive( 'get_form' )->once()->with( 'public/partials/shortcode/avatar-upload.php', $user_id, [ 'atts' => $atts ] )->andReturn( $shortcode );

		$this->assertSame( $shortcode, $this->sut->render_frontend_form_shortcode( $atts, null ) );
	}

	/**
	 * Tests ::render_frontend_form_shortcode.
	 *
	 * @covers ::render_frontend_form_shortcode
	 */
	public function test_render_frontend_form_shortcode_not_logged_in() {
		// Input data.
		$atts = [
			'avatar_size' => 120,
		];

		Functions\expect( 'get_current_user_id' )->once()->andReturn( 0 );

		$this->sut->shouldReceive( 'sanitize_frontend_form_attributes' )->never();
		$this->form->shouldReceive( 'get_form' )->never();

		$this->assertSame( '', $this->sut->render_frontend_form_shortcode( $atts, null ) );
	}

	/**
	 * Provides data for testing sanitize_frontend_form_attributes.
	 *
	 * @return array
	 */
	public function provide_sanitize_frontend_form_attributes_data() {
		return [
			[ [], [ 'avatar_size' => 96 ] ],
			[ [ 'avatar_size' => '120' ], [ 'avatar_size' => 120 ] ],
		];
	}

	/**
	 * Tests ::sanitize_frontend_form_attributes.
	 *
	 * @covers ::sanitize_frontend_form_attributes
	 *
	 * @dataProvider provide_sanitize_frontend_form_attributes_data
	 *
	 * @param  array $input  Input data.
	 * @param  array $result Expected result.
	 */
	public function test_sanitize_frontend_form_attributes( array $input, array $result ) {
		Functions\expect( 'shortcode_atts' )->once()->with( Shortcodes::FRONTEND_FORM_ATTRIBUTES, $input, 'avatar-privacy-form' )->andReturnUsing(
			function( $defaults, $atts ) {
				return \array_intersect_key( \array_merge( $defaults, $atts ), $defaults );
			}
		);

		$this->assertSame( $result, $this->sut->sanitize_frontend_form_attributes( $input ) );
	}
}
