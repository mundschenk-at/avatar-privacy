<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020-2024 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Tools\HTML;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Tools\HTML\Dependencies;

/**
 * Avatar_Privacy\Tools\HTML\Dependencies unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Tools\HTML\Dependencies
 * @usesDefaultClass \Avatar_Privacy\Tools\HTML\Dependencies
 */
class Dependencies_Test extends \Avatar_Privacy\Tests\TestCase {

	const PLUGIN_BASE_URL     = 'https://plugin/base/url';
	const PLUGIN_BASE_PATH    = 'vfs://root/plugin/base/path';
	const MINIFICATION_SUFFIX = '.suffix';

	/**
	 * The system-under-test.
	 *
	 * @var Dependencies&m\MockInterface
	 */
	private $sut;

	/**
	 * The URL of a valid file.
	 *
	 * @var string
	 */
	private $valid_file = self::PLUGIN_BASE_PATH . '/some/fake/source.js';

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
				'base' => [
					'path' => [
						'some' => [
							'fake' => [
								'partial.php'    => 'MY_PARTIAL',
								'source.js'      => 'NOT_REALLY_JAVASCRIPT',
							],
						],
					],
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		\touch( $this->valid_file, 1595068083 );
		\clearstatcache( true, $this->valid_file );

		$this->sut = m::mock( Dependencies::class )->makePartial()->shouldAllowMockingProtectedMethods();

		// Fake constructor actions.
		$this->set_value( $this->sut, 'suffix', self::MINIFICATION_SUFFIX );
		$this->set_value( $this->sut, 'url', self::PLUGIN_BASE_URL );
		$this->set_value( $this->sut, 'path', self::PLUGIN_BASE_PATH );
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$plugin_base_url = 'https://my/plugin/base/url';

		$mock = m::mock( Dependencies::class )->makePartial()->shouldAllowMockingProtectedMethods();

		Functions\expect( 'plugins_url' )->twice()->with( '', \AVATAR_PRIVACY_PLUGIN_FILE )->andReturn( $plugin_base_url, "{$plugin_base_url}2" );

		// No SCRIPT_DEBUG.
		$mock->__construct();

		$this->assert_attribute_same( '.min', 'suffix', $mock );
		$this->assert_attribute_same( $plugin_base_url, 'url', $mock );
		$this->assert_attribute_same( \AVATAR_PRIVACY_PLUGIN_PATH, 'path', $mock );

		// With SCRIPT_DEBUG.
		define( 'SCRIPT_DEBUG', true );
		$mock->__construct();

		$this->assert_attribute_same( '', 'suffix', $mock );
		$this->assert_attribute_same( "{$plugin_base_url}2", 'url', $mock );
		$this->assert_attribute_same( \AVATAR_PRIVACY_PLUGIN_PATH, 'path', $mock );
	}

	/**
	 * Tests ::register_block_script.
	 *
	 * @covers ::register_block_script
	 */
	public function test_register_block_script() {
		$handle = 'my-handle';
		$block  = 'some/fake/source';
		$result = true;

		// Simulate blocks dependencies.
		$version = 'fake blocks version';
		$deps    = [ 'foo', 'bar' ];
		$asset   = '<?php return [ "dependencies" => ' . \var_export( $deps, true ) . ', "version" => ' . \var_export( $version, true ) . ' ]; ?>'; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		vfsStream::create( [
			'plugin' => [
				'base' => [
					'path' => [
						'some' => [
							'fake' => [
								'source.asset.php' => $asset,
							],
						],
					],
				],
			],
		] );

		Functions\expect( 'wp_register_script' )->once()->with( $handle, self::PLUGIN_BASE_URL . "/{$block}.js", $deps, $version, false )->andReturn( $result );

		$this->assertSame( $result, $this->sut->register_block_script( $handle, $block ) );
	}

	/**
	 * Tests ::register_script.
	 *
	 * @covers ::register_script
	 */
	public function test_register_script() {
		$handle    = 'my-handle';
		$src       = 'some/source.js';
		$deps      = [ 'foo', 'bar' ];
		$version   = false;
		$in_footer = true;
		$result    = true;

		$this->sut->shouldReceive( 'maybe_add_minification_suffix' )->once()->with( $src )->andReturn( 'minified/source' );
		$this->sut->shouldReceive( 'maybe_add_file_modification_version' )->once()->with( $version, 'minified/source' )->andReturn( 'my.version' );

		Functions\expect( 'wp_register_script' )->once()->with( $handle, m::pattern( '#minified/source$#' ), $deps, 'my.version', $in_footer )->andReturn( $result );

		$this->assertSame( $result, $this->sut->register_script( $handle, $src, $deps, $version, $in_footer ) );
	}

	/**
	 * Tests ::register_style.
	 *
	 * @covers ::register_style
	 */
	public function test_register_style() {
		$handle  = 'my-handle';
		$src     = 'some/source.js';
		$deps    = [ 'foo', 'bar' ];
		$version = false;
		$media   = 'foo';
		$result  = true;

		$this->sut->shouldReceive( 'maybe_add_minification_suffix' )->once()->with( $src )->andReturn( 'minified/source' );
		$this->sut->shouldReceive( 'maybe_add_file_modification_version' )->once()->with( $version, 'minified/source' )->andReturn( 'my.version' );

		Functions\expect( 'wp_register_style' )->once()->with( $handle, m::pattern( '#minified/source$#' ), $deps, 'my.version', $media )->andReturn( $result );

		$this->assertSame( $result, $this->sut->register_style( $handle, $src, $deps, $version, $media ) );
	}

	/**
	 * Provides data for testing ::enqueue_script.
	 *
	 * @return array
	 */
	public function provide_enqueue_scripts_data() {
		return [
			[ 'avatar-privacy-foo-bar', 'foo_bar', true ],
			[ 'foobar', 'foobar', false ],
		];
	}

	/**
	 * Tests ::enqueue_script.
	 *
	 * @covers ::enqueue_script
	 *
	 * @dataProvider provide_enqueue_scripts_data
	 *
	 * @param  string $handle   The handle.
	 * @param  string $key      The expected "trimmed" handle.
	 * @param  bool   $enqueued Whether the style is expected to be enqueued.
	 */
	public function test_enqueue_script( $handle, $key, $enqueued = true ) {
		Filters\expectApplied( "avatar_privacy_enqueue_script_{$key}" )->once()->with( true )->andReturn( $enqueued );

		if ( $enqueued ) {
			Functions\expect( 'wp_enqueue_script' )->once()->with( $handle );
		} else {
			Functions\expect( 'wp_enqueue_script' )->never();
		}

		$this->assertNull( $this->sut->enqueue_script( $handle ) );
	}

	/**
	 * Provides data for testing ::enqueue_style.
	 *
	 * @return array
	 */
	public function provide_enqueue_style_data() {
		return [
			[ 'avatar-privacy-style-style', 'style', true ],
			[ 'foobar', 'foobar', false ],
		];
	}

	/**
	 * Tests ::enqueue_style.
	 *
	 * @covers ::enqueue_style
	 *
	 * @dataProvider provide_enqueue_style_data
	 *
	 * @param  string $handle   The handle.
	 * @param  string $key      The expected "trimmed" handle.
	 * @param  bool   $enqueued Whether the style is expected to be enqueued.
	 */
	public function test_enqueue_style( $handle, $key, $enqueued = true ) {
		Filters\expectApplied( "avatar_privacy_enqueue_style_{$key}" )->once()->with( true )->andReturn( $enqueued );

		if ( $enqueued ) {
			Functions\expect( 'wp_enqueue_style' )->once()->with( $handle );
		} else {
			Functions\expect( 'wp_enqueue_style' )->never();
		}

		$this->assertNull( $this->sut->enqueue_style( $handle ) );
	}

	/**
	 * Tests ::maybe_add_minification_suffix.
	 *
	 * @covers ::maybe_add_minification_suffix
	 */
	public function test_maybe_add_minification_suffix() {
		$src    = 'some/dir/source.js';
		$result = 'some/dir/source' . self::MINIFICATION_SUFFIX . '.js';

		$this->assertSame( $result, $this->sut->maybe_add_minification_suffix( $src ) );
	}

	/**
	 * Provides data for testing ::enqueue_style.
	 *
	 * @return array
	 */
	public function provide_maybe_add_file_modification_version_data() {
		$relative_file = \str_starts_with( $this->valid_file, self::PLUGIN_BASE_PATH ) ? \substr( $this->valid_file, \strlen( self::PLUGIN_BASE_PATH ) ) : $this->valid_file;
		return [
			[ '2.3.0', 'foo/bar', '2.3.0' ],
			[ null, 'foo/bar', null ],
			[ false, 'foo/bar', false ],
			[ '2.3.0', $relative_file, '2.3.0' ],
			[ null, $relative_file, null ],
			[ false, $relative_file, '1595068083' ],
		];
	}

	/**
	 * Tests ::maybe_add_file_modification_version.
	 *
	 * @covers ::maybe_add_file_modification_version
	 *
	 * @dataProvider provide_maybe_add_file_modification_version_data
	 *
	 * @param string|false|null $version The version parameter.
	 * @param string            $src     The source file path.
	 * @param string|false|null $result  The expected result.
	 */
	public function test_maybe_add_file_modification_version( $version, $src, $result ) {
		$this->assertSame( $result, $this->sut->maybe_add_file_modification_version( $version, $src ) );
	}
}
