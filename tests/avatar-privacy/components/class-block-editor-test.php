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

use Avatar_Privacy\Components\Block_Editor;

use Avatar_Privacy\Tools\Template;
use Avatar_Privacy\Tools\HTML\Dependencies;
use Avatar_Privacy\Tools\HTML\User_Form;

/**
 * Avatar_Privacy\Components\Block_Editor unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Components\Block_Editor
 * @usesDefaultClass \Avatar_Privacy\Components\Block_Editor
 *
 * @uses ::__construct
 */
class Block_Editor_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Block_Editor&m\MockInterface
	 */
	private $sut;

	/**
	 * Mocked helper object.
	 *
	 * @var Dependencies&m\MockInterface
	 */
	private $dependencies;

	/**
	 * The Template alias mock.
	 *
	 * @var Template&m\MockInterface
	 */
	private $template;

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

		$this->dependencies = m::mock( Dependencies::class );
		$this->template     = m::mock( Template::class );
		$this->form         = m::mock( User_Form::class );
		$this->sut          = m::mock( Block_Editor::class, [ $this->dependencies, $this->template, $this->form ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Block_Editor::class )->makePartial();

		$mock->__construct( $this->dependencies, $this->template, $this->form );

		$this->assert_attribute_same( $this->dependencies, 'dependencies', $mock );
		$this->assert_attribute_same( $this->template, 'template', $mock );
		$this->assert_attribute_same( $this->form, 'form', $mock );
	}

	/**
	 * Tests ::run without the block editor being present. Has to be first.
	 *
	 * @covers ::run
	 */
	public function test_run_no_block_editor() {
		Functions\expect( 'is_admin' )->never();

		Actions\expectAdded( 'init' )->never();

		$this->form->shouldReceive( 'register_form_submission' )->never();

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		// Fake block editor.
		Functions\when( 'register_block_type' );

		Functions\expect( 'is_admin' )->once()->andReturn( false );

		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'register_blocks' ] );

		$this->form->shouldReceive( 'register_form_submission' )->once();

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run_admin() {
		// Fake block editor.
		Functions\when( 'register_block_type' );

		Functions\expect( 'is_admin' )->once()->andReturn( true );

		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'register_blocks' ] );

		$this->form->shouldReceive( 'register_form_submission' )->never();

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::register_blocks.
	 *
	 * @covers ::register_blocks
	 */
	public function test_register_blocks() {
		$this->dependencies->shouldReceive( 'register_block_script' )->once()
			->with( 'avatar-privacy-gutenberg', 'admin/blocks/js/blocks' );
		$this->dependencies->shouldReceive( 'register_style' )->once()
			->with( 'avatar-privacy-gutenberg-style', 'admin/css/blocks.css' );

		Functions\expect( 'register_block_type' )->once()->with(
			'avatar-privacy/form',
			[
				'editor_script'   => 'avatar-privacy-gutenberg',
				'editor_style'    => 'avatar-privacy-gutenberg-style',
				'render_callback' => [ $this->sut, 'render_frontend_form' ],
				'attributes'      => [
					'avatar_size'       => [
						'type'    => 'integer',
						'default' => 96,
					],
					'show_descriptions' => [
						'type'    => 'boolean',
						'default' => true,
					],
					'className'         => [
						'type'    => 'string',
						'default' => '',
					],
					'preview'           => [
						'type'    => 'boolean',
						'default' => false,
					],
				],
			]
		);
		Functions\expect( 'register_block_type' )->once()->with(
			'avatar-privacy/avatar',
			[
				'editor_script'   => 'avatar-privacy-gutenberg',
				'editor_style'    => 'avatar-privacy-gutenberg-style',
				'render_callback' => [ $this->sut, 'render_avatar' ],
				'attributes'      => [
					'avatar_size' => [
						'type'    => 'integer',
						'default' => 96,
					],
					'user_id'     => [
						'type'    => 'integer',
						'default' => 0,
					],
					'align'       => [
						'type'    => 'string',
						'default' => '',
					],
					'className'   => [
						'type'    => 'string',
						'default' => '',
					],

				],
			]
		);
		Functions\expect( 'wp_set_script_translations' )->once()->with( 'avatar-privacy-gutenberg', 'avatar-privacy' );

		$this->assertNull( $this->sut->register_blocks() );
	}

	/**
	 * Tests ::render_frontend_form.
	 *
	 * @covers ::render_frontend_form
	 */
	public function test_render_frontend_form() {
		// Input data.
		$atts = [
			'avatar_size' => 120,
		];

		// System state.
		$user_id = 42;

		// Result.
		$block = 'MY_BLOCK';

		Functions\expect( 'get_current_user_id' )->once()->andReturn( $user_id );

		$this->form->shouldReceive( 'get_form' )->once()->with( 'public/partials/block/frontend-form.php', $user_id, m::type( 'array' ) )->andReturn( $block );

		$this->assertSame( $block, $this->sut->render_frontend_form( $atts ) );
	}

	/**
	 * Tests ::render_frontend_form.
	 *
	 * @covers ::render_frontend_form
	 */
	public function test_render_frontend_form_not_logged_in() {
		// Input data.
		$atts = [
			'avatar_size' => 120,
		];

		Functions\expect( 'get_current_user_id' )->once()->andReturn( 0 );

		$this->form->shouldReceive( 'get_form' )->never();

		$this->assertSame( '', $this->sut->render_frontend_form( $atts ) );
	}

	/**
	 * Tests ::render_frontend_form.
	 *
	 * @covers ::render_frontend_form
	 */
	public function test_render_frontend_form_preview() {
		// Input data.
		$atts = [
			'avatar_size' => 120,
			'preview'     => true,
		];

		// System state.
		$user_id = 42;

		// Result.
		$block = 'MY_BLOCK';

		Functions\expect( 'get_current_user_id' )->once()->andReturn( $user_id );

		$this->form->shouldReceive( 'get_form' )->once()->with( 'public/partials/block/frontend-form.php', $user_id, m::type( 'array' ) )->andReturn( $block );

		// Cleanup should probably be a separate method for testing.
		$this->assertSame( $block, $this->sut->render_frontend_form( $atts ) );
	}

	/**
	 * Tests ::render_avatar.
	 *
	 * @covers ::render_avatar
	 */
	public function test_render_avatar() {
		// Input data.
		$user_id = 42;
		$atts    = [
			'user_id'     => $user_id,
			'avatar_size' => 120,
			'className'   => 'foo',
			'align'       => 'bar',
		];

		// Result.
		$block = 'MY_AVATAR_BLOCK';

		Functions\expect( 'get_user_by' )->once()->with( 'ID', $user_id )->andReturn( m::mock( \WP_User::class ) );

		$this->template->shouldReceive( 'get_partial' )->once()->with( 'public/partials/block/avatar.php', m::type( 'array' ) )->andReturn( $block );

		$this->assertSame( $block, $this->sut->render_avatar( $atts ) );
	}

	/**
	 * Tests ::render_avatar.
	 *
	 * @covers ::render_avatar
	 */
	public function test_render_avatar_invalid_user() {
		// Input data.
		$user_id = 42;
		$atts    = [
			'user_id'     => $user_id,
			'avatar_size' => 120,
			'className'   => 'foo',
			'align'       => 'bar',
		];

		Functions\expect( 'get_user_by' )->once()->with( 'ID', $user_id )->andReturn( null );

		$this->template->shouldReceive( 'get_partial' )->never();

		$this->assertSame( '', $this->sut->render_avatar( $atts ) );
	}
}
