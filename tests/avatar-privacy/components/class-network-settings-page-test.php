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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Components\Network_Settings_Page;

use Avatar_Privacy\Core;
use Avatar_Privacy\Settings;
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Data_Storage\Transients;

/**
 * Avatar_Privacy\Components\Network_Settings_Page unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Components\Network_Settings_Page
 * @usesDefaultClass \Avatar_Privacy\Components\Network_Settings_Page
 *
 * @uses ::__construct
 */
class Network_Settings_Page_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Network_Settings_Page
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
	 * @var Network_Options
	 */
	private $network_options;

	/**
	 * Mocked helper object.
	 *
	 * @var Transients
	 */
	private $transients;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		Functions\when( '__' )->returnArg();

		$filesystem = [
			'plugin'    => [
				'admin' => [
					'partials' => [
						'network' => [
							'section.php'       => 'NETWORK_SECTION',
							'settings-page.php' => 'NETWORK_SETTINGS_PAGE',
						],
					],
				],
			],
		];

		// Set up virtual filesystem.
		$root = vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		$this->core            = m::mock( Core::class );
		$this->settings        = m::mock( Settings::class );
		$this->network_options = m::mock( Network_Options::class );
		$this->transients      = m::mock( Transients::class );

		$this->sut = m::mock( Network_Settings_Page::class, [ 'plugin/file', $this->core, $this->network_options, $this->transients, $this->settings ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Network_Settings_Page::class )->makePartial();

		$mock->__construct( 'path/file', $this->core, $this->network_options, $this->transients, $this->settings );

		$this->assertAttributeSame( 'path/file', 'plugin_file', $mock );
		$this->assertAttributeSame( $this->core, 'core', $mock );
		$this->assertAttributeSame( $this->network_options, 'network_options', $mock );
		$this->assertAttributeSame( $this->settings, 'settings', $mock );
	}


	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Functions\expect( 'is_network_admin' )->once()->andReturn( false );

		Actions\expectAdded( 'network_admin_menu' )->never();
		Actions\expectAdded( 'network_admin_edit_' . Network_Settings_Page::ACTION )->never();

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 *
	 * @runInSeparateProcess
	 */
	public function test_run_network_admin() {
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
		$factory   = m::mock( 'alias:Mundschenk\UI\Control_Factory' );

		Functions\expect( 'is_network_admin' )->once()->andReturn( true );

		$this->settings->shouldReceive( 'get_network_fields' )->once()->andReturn( $templates );
		$this->network_options->shouldReceive( 'get' )->once()->with( Network_Options::USE_GLOBAL_TABLE )->andReturn( true );
		$factory->shouldReceive( 'initialize' )->once()->with( m::subset( $templates ), $this->network_options, '' )->andReturn( $controls );

		Actions\expectAdded( 'network_admin_menu' )->once()->with( [ $this->sut, 'register_network_settings' ] );
		Actions\expectAdded( 'network_admin_edit_' . Network_Settings_Page::ACTION )->once()->with( [ $this->sut, 'save_network_settings' ] );
		Actions\expectAdded( 'network_admin_notices' )->once()->with( 'settings_errors' );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::register_network_settings.
	 *
	 * @covers ::register_network_settings
	 */
	public function test_register_network_settings() {
		// External input.
		$migrate       = Network_Options::MIGRATE_FROM_GLOBAL_TABLE;
		$controls      = [
			'setting1' => m::mock( \Mundschenk\UI\Controls\Checkbox_Input::class ),
			'setting2' => m::mock( \Mundschenk\UI\Controls\Textarea::class ),
			'setting3' => m::mock( \Mundschenk\UI\Controls\Button_Input::class ),
			$migrate   => m::mock( \Mundschenk\UI\Controls\Submit_Input::class ),
		];
		$control_count = \count( $controls );
		$this->setValue( $this->sut, 'controls', $controls, Network_Settings_Page::class );

		Functions\expect( 'add_submenu_page' )->once()->with( 'settings.php', m::type( 'string' ), m::type( 'string' ), 'manage_network_options', Network_Settings_Page::OPTION_GROUP, [ $this->sut, 'print_settings_page' ] );
		Functions\expect( 'add_settings_section' )->once()->with( Network_Settings_Page::SECTION, '', [ $this->sut, 'print_settings_section' ], Network_Settings_Page::OPTION_GROUP );

		$this->network_options->shouldReceive( 'get_name' )->times( $control_count )->with( m::type( 'string' ) )->andReturn( 'fake_option_name' );
		Functions\expect( 'register_setting' )->times( $control_count )->with( Network_Settings_Page::OPTION_GROUP, 'fake_option_name', m::type( 'callable' ) );

		$controls['setting1']->shouldReceive( 'register' )->once()->with( Network_Settings_Page::OPTION_GROUP );
		$controls['setting2']->shouldReceive( 'register' )->once()->with( Network_Settings_Page::OPTION_GROUP );
		$controls['setting3']->shouldReceive( 'register' )->once()->with( Network_Settings_Page::OPTION_GROUP );
		$controls[ $migrate ]->shouldReceive( 'register' )->once()->with( Network_Settings_Page::OPTION_GROUP );

		$this->assertNull( $this->sut->register_network_settings() );
	}

	/**
	 * Tests ::print_settings_page.
	 *
	 * @covers ::print_settings_page
	 */
	public function test_print_settings_page() {
		$this->expectOutputString( 'NETWORK_SETTINGS_PAGE' );

		$this->assertNull( $this->sut->print_settings_page() );
	}

	/**
	 * Tests ::save_network_settings.
	 *
	 * @covers ::save_network_settings
	 */
	public function test_save_network_settings() {
		global $new_whitelist_options;
		$new_whitelist_options[ Network_Settings_Page::OPTION_GROUP ] = [ // phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited
			'prefix_option1',
			'prefix_option2',
			'prefix_option3',
		];

		// Fake post.
		global $_POST; // phpcs:ignore  WordPress.Security.NonceVerification.NoNonceVerification
		$_POST = [ // phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited
			'prefix_option1' => 'foo',
		];

		// Control mocks.
		$controls      = [
			'option1' => m::mock( \Mundschenk\UI\Controls\Textarea::class ),
			'option2' => m::mock( \Mundschenk\UI\Controls\Checkbox_Input::class ),
			'option3' => m::mock( \Mundschenk\UI\Controls\Number_Input::class ),
		];
		$control_count = \count( $controls );
		$this->setValue( $this->sut, 'controls', $controls, Network_Settings_Page::class );

		// URLs.
		$network_admin_url              = 'https://network.admin/url/settings.php';
		$network_admin_url_with_queries = $network_admin_url . '?foo=bar';

		// Permissions check.
		Functions\expect( 'current_user_can' )->once()->with( 'manage_network_options' )->andReturn( true );
		Functions\expect( 'esc_html' )->never();
		Functions\expect( 'wp_die' )->never();

		// Referer/nonce check.
		Functions\expect( 'check_admin_referer' )->once()->with( Network_Settings_Page::OPTION_GROUP . '-options' );

		// Do the work.
		Functions\expect( 'wp_unslash' )->once()->with( 'foo' )->andReturn( 'unslashed_foo' );
		$this->network_options->shouldReceive( 'set' )->once()->with( 'prefix_option1', 'unslashed_foo', false, true );
		$this->network_options->shouldReceive( 'remove_prefix' )->once()->with( 'prefix_option2' )->andReturn( 'option2' );
		$this->network_options->shouldReceive( 'set' )->once()->with( 'prefix_option2', false, false, true );
		$this->network_options->shouldReceive( 'remove_prefix' )->once()->with( 'prefix_option3' )->andReturn( 'option3' );
		$this->network_options->shouldReceive( 'delete' )->once()->with( 'prefix_option3', true );

		// Settings errors.
		Functions\expect( 'get_settings_errors' )->once()->andReturn( [] );
		Functions\expect( 'add_settings_error' )->once()->with( Network_Settings_Page::OPTION_GROUP, 'settings_updated', m::type( 'string' ), 'updated' );
		$this->sut->shouldReceive( 'persist_settings_errors' )->once();

		// Finish.
		Functions\expect( 'network_admin_url' )->once()->with( 'settings.php' )->andReturn( $network_admin_url );
		Functions\expect( 'add_query_arg' )->once()->with(
			[
				'page'             => Network_Settings_Page::OPTION_GROUP,
				'settings-updated' => true,
			],
			$network_admin_url
		)->andReturn( $network_admin_url_with_queries );
		Functions\expect( 'wp_safe_redirect' )->once()->with( $network_admin_url_with_queries );
		$this->sut->shouldReceive( 'exit_request' )->once();

		$this->assertNull( $this->sut->save_network_settings() );
	}

	/**
	 * Tests ::save_network_settings.
	 *
	 * @covers ::save_network_settings
	 */
	public function test_save_network_settings_no_permissions() {
		Functions\expect( 'current_user_can' )->once()->with( 'manage_network_options' )->andReturn( false );
		Functions\expect( 'esc_html' )->once()->with( m::type( 'string' ) )->andReturn( 'escaped_error_message' );
		Functions\expect( 'wp_die' )->once()->with( 'escaped_error_message', 403 )->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->assertNull( $this->sut->save_network_settings() );
	}

	/**
	 * Tests ::save_network_settings.
	 *
	 * @covers ::save_network_settings
	 */
	public function test_save_network_settings_invalid_nonce() {
		Functions\expect( 'current_user_can' )->once()->with( 'manage_network_options' )->andReturn( true );
		Functions\expect( 'esc_html' )->never();
		Functions\expect( 'wp_die' )->never();

		Functions\expect( 'check_admin_referer' )->once()->with( Network_Settings_Page::OPTION_GROUP . '-options' )->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->assertNull( $this->sut->save_network_settings() );
	}

	/**
	 * Tests ::print_settings_section.
	 *
	 * @covers ::print_settings_section
	 */
	public function test_print_settings_section() {
		$section = [
			'id' => 'foo',
		];

		$this->expectOutputString( 'NETWORK_SECTION' );

		$this->assertNull( $this->sut->print_settings_section( $section ) );
	}

	/**
	 * Tests ::print_styles.
	 *
	 * @covers ::print_styles
	 */
	public function test_print_styles() {
		$plugin_url = 'my-plugin-url';
		$version    = '9.9.9';

		$this->core->shouldReceive( 'get_version' )->once()->andReturn( $version );
		Functions\expect( 'plugins_url' )->once()->with( 'admin/css/settings.css', 'plugin/file' )->andReturn( $plugin_url );
		Functions\expect( 'wp_enqueue_style' )->once()->with( 'avatar-privacy-settings', $plugin_url, [], $version, 'all' );

		$this->assertNull( $this->sut->print_styles() );
	}

	/**
	 * Tests ::sanitize_migrate_from_global_table.
	 *
	 * @covers ::sanitize_migrate_from_global_table
	 */
	public function test_sanitize_migrate_from_global_table() {
		$input  = 'foo';
		$result = 'bar';

		$this->sut->shouldReceive( 'trigger_admin_notice' )->once()->with( Network_Options::MIGRATE_FROM_GLOBAL_TABLE, 'migrated-to-site-tables', m::type( 'string' ), 'notice-info', $input )->andReturn( $result );

		$this->assertSame( $result, $this->sut->sanitize_migrate_from_global_table( $input ) );
	}

	/**
	 * Provides data for testing filter_update_option.
	 *
	 * @return array
	 */
	public function provide_trigger_admin_notice_data() {
		return [
			[ 'foo', true ],
			[ 0, false ],
		];
	}

	/**
	 * Tests ::trigger_admin_notice.
	 *
	 * @covers ::trigger_admin_notice
	 *
	 * @dataProvider provide_trigger_admin_notice_data
	 *
	 * @param  mixed $input     The input value.
	 * @param  bool  $result    Expected result.
	 */
	public function test_trigger_admin_notice( $input, $result ) {
		$setting_name = 'my-setting-name';
		$notice_id    = 'my-notice-id';
		$message      = 'some message';
		$notice_level = 'my-notice-level';

		$index = 'foo';

		// Fake $_POST.
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
		$_POST = [ $index => 'bar' ];

		$this->network_options->shouldReceive( 'get_name' )->once()->with( $setting_name )->andReturn( $index );
		Functions\expect( 'add_settings_error' )->once()->with( Network_Settings_Page::OPTION_GROUP, $notice_id, $message, $notice_level );

		$this->assertSame( $result, $this->sut->trigger_admin_notice( $setting_name, $notice_id, $message, $notice_level, $input ) );
	}

	/**
	 * Tests ::trigger_admin_notice.
	 *
	 * @covers ::trigger_admin_notice
	 *
	 * @dataProvider provide_trigger_admin_notice_data
	 *
	 * @param  mixed $input     The input value.
	 * @param  bool  $result    Expected result.
	 */
	public function test_trigger_admin_notice_no_button( $input, $result ) {
		$setting_name = 'my-setting-name';
		$notice_id    = 'my-notice-id';
		$message      = 'some message';
		$notice_level = 'my-notice-level';

		$index = 'foo';

		// Fake $_POST.
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
		$_POST = [];

		$this->network_options->shouldReceive( 'get_name' )->once()->with( $setting_name )->andReturn( $index );
		Functions\expect( 'add_settings_error' )->never();

		$this->assertSame( $result, $this->sut->trigger_admin_notice( $setting_name, $notice_id, $message, $notice_level, $input ) );
	}

	/**
	 * Tests ::trigger_admin_notice.
	 *
	 * @covers ::trigger_admin_notice
	 *
	 * @dataProvider provide_trigger_admin_notice_data
	 *
	 * @param  mixed $input     The input value.
	 * @param  bool  $result    Expected result.
	 */
	public function test_trigger_admin_notice_already_triggered( $input, $result ) {
		$setting_name = 'my-setting-name';
		$notice_id    = 'my-notice-id';
		$message      = 'some message';
		$notice_level = 'my-notice-level';

		$index = 'foo';

		// Fake $_POST.
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
		$_POST = [ $index => 'bar' ];

		// Notice already triggered.
		$triggered = [ $setting_name => true ];
		$this->setValue( $this->sut, 'triggered_notice', $triggered, Network_Settings_Page::class );

		$this->network_options->shouldReceive( 'get_name' )->once()->with( $setting_name )->andReturn( $index );
		Functions\expect( 'add_settings_error' )->never();

		$this->assertSame( $result, $this->sut->trigger_admin_notice( $setting_name, $notice_id, $message, $notice_level, $input ) );
	}

	/**
	 * Tests ::persist_settings_errors.
	 *
	 * @covers ::persist_settings_errors
	 */
	public function test_persist_settings_errors() {
		$settings_errors = [ 'foo', 'bar' ];

		Functions\expect( 'get_settings_errors' )->once()->andReturn( $settings_errors );
		$this->transients->shouldReceive( 'set' )->once()->with( 'settings_errors', $settings_errors, 30, true );

		$this->assertNull( $this->sut->persist_settings_errors() );
	}

	/**
	 * Provides data for testing filter_update_option.
	 *
	 * @return array
	 */
	public function provide_filter_update_option_data() {
		return [
			[ 5, 6, true, 6 ],
			[ 5, 6, false, 5 ],
		];
	}

	/**
	 * Tests ::filter_update_option.
	 *
	 * @covers ::filter_update_option
	 *
	 * @dataProvider provide_filter_update_option_data
	 *
	 * @param  mixed $new_value New value of the network option.
	 * @param  mixed $old_value Old value of the network option.
	 * @param  bool  $migrate   Whether the migrate button was pressed.
	 * @param  mixed $result    Expected result.
	 */
	public function test_filter_update_option( $new_value, $old_value, $migrate, $result ) {
		$index = 'foo';

		// Fake $_POST.
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
		$_POST = $migrate ? [ $index => 'bar' ] : [];

		$this->network_options->shouldReceive( 'get_name' )->once()->with( Network_Options::MIGRATE_FROM_GLOBAL_TABLE )->andReturn( $index );

		$this->assertSame( $result, $this->sut->filter_update_option( $new_value, $old_value ) );
	}
}
