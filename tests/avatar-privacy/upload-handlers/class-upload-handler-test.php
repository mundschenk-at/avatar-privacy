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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Upload_Handlers;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Upload_Handlers\Upload_Handler;

use Avatar_Privacy\Core;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;


/**
 * Avatar_Privacy\Upload_Handlers\Upload_Handler unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Upload_Handlers\Upload_Handler
 * @usesDefaultClass \Avatar_Privacy\Upload_Handlers\Upload_Handler
 *
 * @uses ::__construct
 */
class Upload_Handler_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Upload_Handler
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Required helper object.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

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
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Mock required helpers.
		$this->core       = m::mock( Core::class );
		$this->file_cache = m::mock( Filesystem_Cache::class );

		$this->sut = m::mock( Upload_Handler::class, [ 'uploads', $this->core, $this->file_cache ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Upload_Handler::class )->makePartial();

		$mock->__construct( 'uploads', $this->core, $this->file_cache );

		$this->assert_attribute_same( $this->core, 'core', $mock );
		$this->assert_attribute_same( $this->file_cache, 'file_cache', $mock );
	}

	/**
	 * Provides the data for testing get_unique_filename.
	 *
	 * @return array
	 */
	public function provide_get_unique_filename_data() {
		return [
			[ 'some.png', '.png', 'some_3.png' ],
			[ 'some.gif', '.gif', 'some_1.gif' ],
			[ 'other.png', '.png', 'other.png' ],
		];
	}

	/**
	 * Tests ::get_unique_filename.
	 *
	 * @covers ::get_unique_filename
	 *
	 * @dataProvider provide_get_unique_filename_data
	 *
	 * @param string $filename  The proposed filename.
	 * @param string $extension The file extension (including leading dot).
	 * @param string $result    The resulting filename.
	 */
	public function test_get_unique_filename( $filename, $extension, $result ) {
		$this->assertSame( $result, $this->sut->get_unique_filename( vfsStream::url( 'root/uploads' ), $filename, $extension ) );
	}

	/**
	 * Provides the data for testing get_unique_filename.
	 *
	 * @return array
	 */
	public function provide_upload_data() {
		return [
			[ false, false, false ],
			[ false, true, false ],
			[ true, false, false ],
			[ true, true, false ],
			[ false, false, true ],
			[ false, true, true ],
			[ true, false, true ],
			[ true, true, true ],
		];
	}

	/**
	 * Tests ::upload.
	 *
	 * @covers ::upload
	 *
	 * @dataProvider provide_upload_data
	 *
	 * @param bool $is_multisite  The result of is_multisite().
	 * @param bool $global        A flag indicating global uploads on multisite.
	 */
	public function test_upload( $is_multisite, $global, $has_file ) {
		$file         = [ 'foo' => 'bar' ];
		$result       = [ 'bar' => 'foo' ];
		$main_site_id = 5;

		if ( $has_file ) {
			$result['file'] = '/my/path';

			Functions\expect( 'wp_normalize_path' )->once()->with( $result['file'] )->andReturn( '/my/normalized/path' );
		}

		if ( $global ) {
			Functions\expect( 'is_multisite' )->once()->andReturn( $is_multisite );

			if ( $is_multisite ) {
				Functions\expect( 'get_main_site_id' )->once()->andReturn( $main_site_id );
				Functions\expect( 'switch_to_blog' )->once()->with( $main_site_id );
				Functions\expect( 'restore_current_blog' )->once();
			}
		}

		Filters\expectAdded( 'upload_dir' )->once()->with( [ $this->sut, 'custom_upload_dir' ] );
		Functions\expect( 'wp_handle_upload' )->once()->with( $file, m::type( 'array' ) )->andReturn( $result );

		if ( $has_file ) {
			// Path should be normalized in final result.
			$result['file'] = '/my/normalized/path';
		}

		$this->assertSame( $result, $this->sut->upload( $file, $global ) );
		$this->assertFalse( Filters\has( 'upload_dir', [ $this->sut, 'custom_upload_dir' ] ) );
	}

	/**
	 * Tests ::custom_upload_dir.
	 *
	 * @covers ::custom_upload_dir
	 */
	public function test_custom_upload_dir() {
		$result = $this->sut->custom_upload_dir(
			[
				'path'   => 'FOO/SUB',
				'url'    => 'https://FOO/SUB',
				'subdir' => 'SUB',
			]
		);

		$this->assertArrayHasKey( 'path', $result );
		$this->assertArrayHasKey( 'url', $result );
		$this->assertArrayHasKey( 'subdir', $result );
		$this->assertSame( 'FOO/uploads', $result['path'] );
		$this->assertSame( 'https://FOO/uploads', $result['url'] );
		$this->assertSame( 'uploads', $result['subdir'] );
	}

	/**
	 * Tests ::normalize_files_array.
	 *
	 * @covers ::normalize_files_array
	 */
	public function test_normalize_files_array_single() {
		$slice = [
			'name'     => 'facepalm.jpg',
			'type'     => 'image/jpeg',
			'tmp_name' => '/tmp/phpn3FmFr',
			'error'    => 0,
			'size'     => 15476,
		];

		$target = [ $slice ];

		$this->assertSame( $target, $this->sut->normalize_files_array( $slice ) );
	}

	/**
	 * Tests ::normalize_files_array.
	 *
	 * @covers ::normalize_files_array
	 */
	public function test_normalize_files_array_multiple() {
		$slice = [
			'name'     => [
				'facepalm.jpg',
				'other.gif',
			],
			'type'     => [
				'image/jpeg',
				'image/gif',
			],
			'tmp_name' => [
				'/tmp/phpn3FmFr',
				'/tmp/phpn3FmXX',
			],
			'error'    => [
				0,
				0,
			],
			'size'     => [
				15476,
				4000,
			],
		];

		$target = [
			[
				'name'     => 'facepalm.jpg',
				'type'     => 'image/jpeg',
				'tmp_name' => '/tmp/phpn3FmFr',
				'error'    => 0,
				'size'     => 15476,
			],
			[
				'name'     => 'other.gif',
				'type'     => 'image/gif',
				'tmp_name' => '/tmp/phpn3FmXX',
				'error'    => 0,
				'size'     => 4000,
			],
		];

		$this->assertSame( $target, $this->sut->normalize_files_array( $slice ) );
	}
}
