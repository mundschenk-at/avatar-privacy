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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Upload_Handlers\UI;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Upload_Handlers\UI\File_Upload_Input;

use Avatar_Privacy\Core;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;


/**
 * Avatar_Privacy\Upload_Handlers\File_Upload_Input unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Upload_Handlers\UI\File_Upload_Input
 * @usesDefaultClass \Avatar_Privacy\Upload_Handlers\UI\File_Upload_Input
 *
 * @uses ::__construct
 */
class File_Upload_Input_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var File_Upload_Input
	 */
	private $sut;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		Functions\when( 'wp_parse_args' )->alias(
			function( $args, $defaults ) {
				return \array_merge( $defaults, $args );
			}
		);

		$filesystem = [
			'plugin'    => [
				'public' => [
					'partials' => [
						'comments' => [
							'use-gravatar.php' => 'USE_GRAVATAR',
						],
					],
				],
			],
			'uploads'   => [
				'some.png'   => '',
				'some_1.png' => '',
				'some_2.png' => '',
				'some.gif'   => '',
			],
		];

		// Set up virtual filesystem.
		$root = vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Ubiquitous functions.
		Functions\when( '__' )->returnArg();

		// Don't run the constructor.
		$this->sut     = m::mock( File_Upload_Input::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$this->options = m::mock( Options::class );

		// Set necessary values manually.
		$this->setValue( $this->sut, 'options', $this->options );
		$this->setValue( $this->sut, 'erase_checkbox_id', 'erase-checkbox-id', File_Upload_Input::class );
		$this->setValue( $this->sut, 'action', 'action-name', File_Upload_Input::class );
		$this->setValue( $this->sut, 'nonce', 'nonce-name', File_Upload_Input::class );
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses Mundschenk\UI\Controls\Input::__construct
	 */
	public function test_constructor() {
		$args = [
			'erase_checkbox' => 'my_erase_checkbox_id',
			'action'         => 'my_upload_action',
			'nonce'          => 'my_upload_nonce',
			'help_no_file'   => 'help-text-no-file',
			'help_no_upload' => 'help-text-not-enough-capabilities',
			'tab_id'         => 'my_tab_id',
			'section'        => 'my_section',
			'default'        => 'my_default',
			'short'          => 'my_short',
			'label'          => 'my_label',
			'help_text'      => 'my_help_text',
			'inline_help'    => false,
		];

		$mock    = m::mock( File_Upload_Input::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$options = m::mock( Options::class );

		Functions\expect( 'current_user_can' )->once()->with( 'upload_files' )->andReturn( true );
		Functions\expect( 'esc_attr' )->times( 3 )->andReturnUsing(
			function( $arg ) {
				return $arg;
			}
		);

		$mock->shouldReceive( 'prepare_args' )->once()->with( m::type( 'array' ), [ 'erase_checkbox', 'action', 'nonce', 'help_no_file', 'help_no_upload' ] )->andReturn( $args );
		$mock->shouldReceive( 'get_value' )->atLeast()->once()->andReturn( 'foo' );

		// Let's go!
		$mock->__construct( $options, 'my_options_key', 'my_control_id', $args );

		$this->assertAttributeSame( $options, 'options', $mock );
		$this->assertAttributeSame( 'my_help_text', 'help_text', $mock );
		$this->assertAttributeSame( 'my_erase_checkbox_id', 'erase_checkbox_id', $mock );
		$this->assertAttributeSame( 'my_upload_action', 'action', $mock );
		$this->assertAttributeSame( 'my_upload_nonce', 'nonce', $mock );
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses Mundschenk\UI\Controls\Input::__construct
	 */
	public function test_constructor_no_value() {
		$args = [
			'erase_checkbox' => 'my_erase_checkbox_id',
			'action'         => 'my_upload_action',
			'nonce'          => 'my_upload_nonce',
			'help_no_file'   => 'help-text-no-file',
			'help_no_upload' => 'help-text-not-enough-capabilities',
			'tab_id'         => 'my_tab_id',
			'section'        => 'my_section',
			'default'        => 'my_default',
			'short'          => 'my_short',
			'label'          => 'my_label',
			'help_text'      => 'my_help_text',
			'inline_help'    => false,
			'outer_attributes' => [ 'foo' => 'bar' ],
		];

		$mock    = m::mock( File_Upload_Input::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$options = m::mock( Options::class );

		Functions\expect( 'current_user_can' )->once()->with( 'upload_files' )->andReturn( true );
		Functions\expect( 'esc_attr' )->times( 3 )->andReturnUsing(
			function( $arg ) {
				return $arg;
			}
		);

		$mock->shouldReceive( 'prepare_args' )->once()->with( m::type( 'array' ), [ 'erase_checkbox', 'action', 'nonce', 'help_no_file', 'help_no_upload' ] )->andReturn( $args );
		$mock->shouldReceive( 'get_value' )->atLeast()->once()->andReturn( false );

		// Let's go!
		$mock->__construct( $options, 'my_options_key', 'my_control_id', $args );

		$this->assertAttributeSame( $options, 'options', $mock );
		$this->assertAttributeSame( 'help-text-no-file', 'help_text', $mock );
		$this->assertAttributeSame( 'my_erase_checkbox_id', 'erase_checkbox_id', $mock );
		$this->assertAttributeSame( 'my_upload_action', 'action', $mock );
		$this->assertAttributeSame( 'my_upload_nonce', 'nonce', $mock );
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses Mundschenk\UI\Controls\Input::__construct
	 */
	public function test_constructor_cannot_upload() {
		$args = [
			'erase_checkbox' => 'my_erase_checkbox_id',
			'action'         => 'my_upload_action',
			'nonce'          => 'my_upload_nonce',
			'help_no_file'   => 'help-text-no-file',
			'help_no_upload' => 'help-text-not-enough-capabilities',
			'tab_id'         => 'my_tab_id',
			'section'        => 'my_section',
			'default'        => 'my_default',
			'short'          => 'my_short',
			'label'          => 'my_label',
			'help_text'      => 'my_help_text',
			'inline_help'    => false,
		];

		$mock    = m::mock( File_Upload_Input::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$options = m::mock( Options::class );

		Functions\expect( 'current_user_can' )->once()->with( 'upload_files' )->andReturn( false );
		Functions\expect( 'esc_attr' )->times( 3 )->andReturnUsing(
			function( $arg ) {
				return $arg;
			}
		);

		$mock->shouldReceive( 'prepare_args' )->once()->with( m::type( 'array' ), [ 'erase_checkbox', 'action', 'nonce', 'help_no_file', 'help_no_upload' ] )->andReturn( $args );
		$mock->shouldReceive( 'get_value' )->never();

		// Let's go!
		$mock->__construct( $options, 'my_options_key', 'my_control_id', $args );

		$this->assertAttributeSame( $options, 'options', $mock );
		$this->assertAttributeSame( 'help-text-not-enough-capabilities', 'help_text', $mock );
		$this->assertAttributeSame( 'my_erase_checkbox_id', 'erase_checkbox_id', $mock );
		$this->assertAttributeSame( 'my_upload_action', 'action', $mock );
		$this->assertAttributeSame( 'my_upload_nonce', 'nonce', $mock );
	}

	/**
	 * Tests ::get_value_markup.
	 *
	 * @covers ::get_value_markup
	 */
	public function test_get_value_markup() {
		$this->assertSame( 'value="" ', $this->sut->get_value_markup( 'foo' ) );
	}

	/**
	 * Provides data for testing get_element_markup.
	 *
	 * @return array
	 */
	public function provide_get_element_markup_data() {
		return [
			[ 'foo', true ],
			[ '', false ],
		];
	}

	/**
	 * Tests ::get_element_markup.
	 *
	 * @covers ::get_element_markup
	 *
	 * @dataProvider provide_get_element_markup_data
	 *
	 * @uses Mundschenk\UI\Controls\Input::get_element_markup
	 *
	 * @param  string $value    The value.
	 * @param  bool   $checkbox Whether the checkbox is expected to be rendered.
	 */
	public function test_get_element_markup( $value, $checkbox ) {
		$blog_id = 5;

		Functions\when( 'esc_attr' )->returnArg();

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $blog_id );
		Functions\expect( 'wp_nonce_field' )->once()->with( 'action-name', "nonce-name{$blog_id}", true, false )->andReturn( 'nonce-markup' );

		$this->options->shouldReceive( 'get_name' )->once()->andReturn( 'option-name' );
		$this->sut->shouldReceive( 'get_value' )->atLeast()->once()->andReturn( $value );
		$this->sut->shouldReceive( 'get_inner_html_attributes' )->once()->andReturn( 'html-attributes' );
		$this->sut->shouldReceive( 'get_value_markup' )->once()->andReturn( 'value-markup' );

		$result = $this->sut->get_element_markup();

		if ( $checkbox ) {
			$this->assertRegExp( '/<input id="erase-checkbox-id" name="erase-checkbox-id" value="true" type="checkbox">/', $result );
		} else {
			$this->assertNotRegExp( '/<input id="erase-checkbox-id" name="erase-checkbox-id" value="true" type="checkbox">/', $result );
		}
	}
}
