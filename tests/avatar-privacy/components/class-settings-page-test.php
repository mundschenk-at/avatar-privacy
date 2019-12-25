<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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

use Avatar_Privacy\Components\Settings_Page;

use Avatar_Privacy\Core;
use Avatar_Privacy\Settings;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Upload_Handlers\Custom_Default_Icon_Upload_Handler;

/**
 * Avatar_Privacy\Components\Settings_Page unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Components\Settings_Page
 * @usesDefaultClass \Avatar_Privacy\Components\Settings_Page
 *
 * @uses ::__construct
 */
class Settings_Page_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Settings_Page
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
	 * @var Settings
	 */
	private $settings;

	/**
	 * Mocked helper object.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Mocked helper object.
	 *
	 * @var Custom_Default_Icon_Upload_Handler
	 */
	private $upload;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$filesystem = [
			'plugin'    => [
				'admin' => [
					'partials' => [
						'sections' => [
							'avatars-disabled-script.php' => 'AVATARS_DISABLED_SCRIPT',
							'avatars-disabled.php'        => 'AVATARS_DISABLED',
							'avatars-enabled.php'         => 'AVATARS_ENABLED',
						],
					],
				],
			],
		];

		// Set up virtual filesystem.
		$root = vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		$this->core     = m::mock( Core::class );
		$this->settings = m::mock( Settings::class );
		$this->options  = m::mock( Options::class );
		$this->upload   = m::mock( Custom_Default_Icon_Upload_Handler::class );

		$this->sut = m::mock( Settings_Page::class, [ $this->core, $this->options, $this->upload, $this->settings ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Settings_Page::class )->makePartial();

		$mock->__construct( $this->core, $this->options, $this->upload, $this->settings );

		$this->assertAttributeSame( $this->core, 'core', $mock );
		$this->assertAttributeSame( $this->options, 'options', $mock );
		$this->assertAttributeSame( $this->upload, 'upload', $mock );
		$this->assertAttributeSame( $this->settings, 'settings', $mock );
	}


	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Functions\expect( 'is_admin' )->once()->andReturn( false );

		Actions\expectAdded( 'admin_init' )->never();
		Actions\expectAdded( 'admin_footer-options-discussion.php' )->never();
		Actions\expectAdded( 'admin_head-options-discussion.php' )->never();

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run_admin() {
		Functions\expect( 'is_admin' )->once()->andReturn( true );

		Actions\expectAdded( 'admin_init' )->once()->with( [ $this->sut, 'register_settings' ] );
		Actions\expectAdded( 'admin_head-options-discussion.php' )->once()->with( [ $this->sut, 'settings_head' ] );
		Actions\expectAdded( 'admin_footer-options-discussion.php' )->once()->with( [ $this->sut, 'settings_footer' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::settings_head.
	 *
	 * @covers ::settings_head
	 *
	 * @uses ::add_form_encoding
	 */
	public function test_settings_head() {
		$this->assertNull( $this->sut->settings_head() );
		$this->assertAttributeSame( true, 'buffering', $this->sut );

		// Clean up.
		\ob_end_flush();
	}

	/**
	 * Tests ::settings_footer.
	 *
	 * @covers ::settings_footer
	 */
	public function test_settings_footer() {
		Functions\expect( 'wp_script_is' )->once()->with( 'jquery', 'done' )->andReturn( true );

		// Fake settings_head.
		\ob_start();
		$this->setValue( $this->sut, 'buffering', true, Settings_Page::class );

		$this->expectOutputString( 'AVATARS_DISABLED_SCRIPT' );

		$this->assertNull( $this->sut->settings_footer() );
		$this->assertAttributeSame( false, 'buffering', $this->sut );
	}

	/**
	 * Tests ::register_settings.
	 *
	 * @covers ::register_settings
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_register_settings() {
		// External input.
		$templates = [
			'setting1' => 'Mundschenk\UI\Controls\Checkbox_Input',
			'setting2' => 'Mundschenk\UI\Controls\Textarea',
			'setting3' => 'Mundschenk\UI\Controls\Button_Input',
		];
		$controls  = [
			'setting1' => m::mock( 'Mundschenk\UI\Controls\Checkbox_Input' ),
			'setting2' => m::mock( 'Mundschenk\UI\Controls\Textarea' ),
			'setting3' => m::mock( 'Mundschenk\UI\Controls\Button_Input' ),
		];

		$this->options->shouldReceive( 'get_name' )->once()->with( Core::SETTINGS_NAME )->andReturn( 'my_settings' );
		Functions\expect( 'register_setting' )->once()->with( 'discussion', 'my_settings', [ $this->sut, 'sanitize_settings' ] );

		$this->sut->shouldReceive( 'get_settings_header' )->once()->andReturn( 'my_settings_header' );

		$this->settings->shouldReceive( 'get_fields' )->once()->with( 'my_settings_header' )->andReturn( $templates );

		$factory = m::mock( 'alias:Mundschenk\UI\Control_Factory' );
		$factory->shouldReceive( 'initialize' )->once()->with( $templates, $this->options, Core::SETTINGS_NAME )->andReturn( $controls );

		$controls['setting1']->shouldReceive( 'register' )->once()->with( 'discussion' );
		$controls['setting2']->shouldReceive( 'register' )->once()->with( 'discussion' );
		$controls['setting3']->shouldReceive( 'register' )->once()->with( 'discussion' );

		$this->assertNull( $this->sut->register_settings() );
	}

	/**
	 * Tests ::add_form_encoding.
	 *
	 * @covers ::add_form_encoding
	 */
	public function test_add_form_encoding() {
		$content = 'some content with <input> and <form method="post" action="foobar.php">';
		$this->assertSame( $content, $this->sut->add_form_encoding( $content ) );

		$content = 'some content with <input> and <form method="post" action="options.php">';
		$this->assertSame( 'some content with <input> and <form method="post" enctype="multipart/form-data" action="options.php">', $this->sut->add_form_encoding( $content ) );
	}

	/**
	 * Tests ::get_settings_header.
	 *
	 * @covers ::get_settings_header
	 */
	public function test_get_settings_header() {
		$this->options->shouldReceive( 'get' )->once()->with( 'show_avatars', false, true )->andReturn( true );

		$this->assertSame( 'AVATARS_DISABLEDAVATARS_ENABLED', $this->sut->get_settings_header() );
	}

	/**
	 * Tests ::sanitize_settings.
	 *
	 * @covers ::sanitize_settings
	 */
	public function test_sanitize_settings() {
		$input        = [
			'setting2' => 'foo',
			'setting3' => 'bar',
		];
		$fields       = [
			'setting1' => [
				'ui' => 'Mundschenk\UI\Controls\Checkbox_Input',
			],
			'setting2' => [
				'ui' => 'Mundschenk\UI\Controls\Textarea',
			],
			'setting3' => [
				'ui' => 'Mundschenk\UI\Controls\Button_Input',
			],
		];
		$old_avatar   = [ 'foobar' ];
		$old_settings = [ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR => $old_avatar ];
		$blog_id      = 8;

		$this->settings->shouldReceive( 'get_fields' )->once()->andReturn( $fields );
		$this->core->shouldReceive( 'get_settings' )->once()->andReturn( $old_settings );

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $blog_id );

		$this->upload->shouldReceive( 'save_uploaded_default_icon' )->once()->with( $blog_id, $old_avatar );

		$result = $this->sut->sanitize_settings( $input );

		$this->assertInternalType( 'array', $result );
		$this->assertSame( $old_avatar, $result[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ] );
		$this->assertFalse( $result['setting1'] );
		$this->assertSame( 'foo', $result['setting2'] );
		$this->assertSame( 'bar', $result['setting3'] );
	}

	/**
	 * Tests ::sanitize_settings.
	 *
	 * @covers ::sanitize_settings
	 */
	public function test_sanitize_settings_checkbox_set() {
		$input        = [
			'setting1' => 1,
			'setting2' => 'foo',
			'setting3' => 'bar',
		];
		$fields       = [
			'setting1' => [
				'ui' => 'Mundschenk\UI\Controls\Checkbox_Input',
			],
			'setting2' => [
				'ui' => 'Mundschenk\UI\Controls\Textarea',
			],
			'setting3' => [
				'ui' => 'Mundschenk\UI\Controls\Button_Input',
			],
		];
		$old_avatar   = [ 'foobar' ];
		$old_settings = [ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR => $old_avatar ];
		$blog_id      = 8;

		$this->settings->shouldReceive( 'get_fields' )->once()->andReturn( $fields );
		$this->core->shouldReceive( 'get_settings' )->once()->andReturn( $old_settings );

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $blog_id );

		$this->upload->shouldReceive( 'save_uploaded_default_icon' )->once()->with( $blog_id, $old_avatar );

		$result = $this->sut->sanitize_settings( $input );

		$this->assertInternalType( 'array', $result );
		$this->assertSame( $old_avatar, $result[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ] );
		$this->assertTrue( $result['setting1'] );
		$this->assertSame( 'foo', $result['setting2'] );
		$this->assertSame( 'bar', $result['setting3'] );
	}

	/**
	 * Tests ::sanitize_settings.
	 *
	 * @covers ::sanitize_settings
	 */
	public function test_sanitize_settings_new_avatar_set() {
		$new_avatar = [ 'newavatar' ];
		$input      = [ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR => $new_avatar ];
		$fields     = [
			'setting1' => [
				'ui' => 'Mundschenk\UI\Controls\Checkbox_Input',
			],
			'setting2' => [
				'ui' => 'Mundschenk\UI\Controls\Textarea',
			],
			'setting3' => [
				'ui' => 'Mundschenk\UI\Controls\Button_Input',
			],
		];
		$blog_id    = 8;

		$this->settings->shouldReceive( 'get_fields' )->once()->andReturn( $fields );
		$this->core->shouldReceive( 'get_settings' )->never();

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $blog_id );

		$this->upload->shouldReceive( 'save_uploaded_default_icon' )->once()->with( $blog_id, $new_avatar );

		$result = $this->sut->sanitize_settings( $input );

		$this->assertInternalType( 'array', $result );
		$this->assertSame( $new_avatar, $result[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ] );
		$this->assertFalse( $result['setting1'] );
		$this->assertFalse( isset( $result['setting2'] ) );
		$this->assertFalse( isset( $result['setting3'] ) );
	}
}
