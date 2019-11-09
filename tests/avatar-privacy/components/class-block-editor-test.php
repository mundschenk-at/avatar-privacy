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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Components\Block_Editor;

use Avatar_Privacy\Core;
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
	 * @var Block_Editor
	 */
	private $sut;

	/**
	 * Mocked helper object.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Mocked helper object.
	 *
	 * @var User_Form
	 */
	private $form;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$filesystem = [
			'plugin'    => [
				'public' => [
					'partials' => [
						'block' => [
							'frontend-form.php'    => 'BLOCK',
							'avatar.php'           => 'AVATAR',
						],
					],
				],
				'js'     => [
					'dummy.js'        => 'Not really JavaScript',
					'dummy.deps.json' => '["wp-blocks","wp-components","wp-editor","wp-element","wp-i18n","wp-polyfill"]',
				],
			],
		];

		// Set up virtual filesystem.
		$root = vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		$this->core = m::mock( Core::class );
		$this->form = m::mock( User_Form::class );
		$this->sut  = m::mock( Block_Editor::class, [ $this->core, $this->form ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Block_Editor::class )->makePartial();

		$mock->__construct( $this->core, $this->form );

		$this->assertAttributeSame( $this->core, 'core', $mock );
		$this->assertAttributeSame( $this->form, 'form', $mock );
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
		$base_url = 'fake://url';
		$path     = '/plugin/';
		$suffix   = '.min';
		$version  = 'fake version';

		// Simulate blocks dependencies.
		$blocks_version = 'fake blocks version';
		$deps           = [ 'foo', 'bar' ];
		$asset          = '<?php return [ "dependencies" => ' . \var_export( $deps, true ) . ', "version" => ' . \var_export( $blocks_version, true ) . ' ]; ?>'; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		vfsStream::create( [
			'plugin' => [
				'admin' => [
					'blocks' => [
						'js' => [
							'blocks.asset.php' => $asset,
						],
					],
				],
			],
		] );

		Functions\expect( 'plugins_url' )->once()->with( '', \AVATAR_PRIVACY_PLUGIN_FILE )->andReturn( $base_url );

		// Deprecated and unused method.
		$this->sut->shouldReceive( 'get_dependencies' )->never();

		Functions\expect( 'wp_register_script' )->once()->with(
			'avatar-privacy-gutenberg',
			"{$base_url}/admin/blocks/js/blocks.js",
			$deps,
			$blocks_version,
			false
		);

		$this->core->shouldReceive( 'get_version' )->once()->andReturn( $version );
		Functions\expect( 'wp_register_style' )->once()->with(
			'avatar-privacy-gutenberg-style',
			"{$base_url}/admin/css/blocks{$suffix}.css",
			[],
			$version
		);

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
	 * Tests ::get_dependencies.
	 *
	 * @covers ::get_dependencies
	 */
	public function test_get_dependencies() {
		$file   = vfsStream::url( 'root/plugin/js/dummy.js' );
		$result = [
			'wp-blocks',
			'wp-components',
			'wp-editor',
			'wp-element',
			'wp-i18n',
			'wp-polyfill',
		];

		$this->assertSame( $result, $this->sut->get_dependencies( $file ) );
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

		Functions\expect( 'get_current_user_id' )->once()->andReturn( $user_id );

		$this->assertSame( 'BLOCK', $this->sut->render_frontend_form( $atts ) );
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

		Functions\expect( 'get_current_user_id' )->once()->andReturn( $user_id );

		// Cleanup should probably be a separate method for testing.
		$this->assertSame( 'BLOCK', $this->sut->render_frontend_form( $atts ) );
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

		Functions\expect( 'get_user_by' )->once()->with( 'ID', $user_id )->andReturn( m::mock( \WP_User::class ) );

		$this->assertSame( 'AVATAR', $this->sut->render_avatar( $atts ) );
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

		$this->assertSame( '', $this->sut->render_avatar( $atts ) );
	}
}
