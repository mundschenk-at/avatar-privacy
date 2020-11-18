<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Tools\Images;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Tools\Images\Image_File;

/**
 * Avatar_Privacy\Tools\Images\Image_File unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Tools\Images\Image_File
 * @usesDefaultClass \Avatar_Privacy\Tools\Images\Image_File
 */
class Image_File_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Image_File
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		$this->sut = m::mock( Image_File::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::handle_upload.
	 *
	 * @covers ::handle_upload
	 */
	public function test_handle_upload() {
		$file           = [ 'foo' => 'bar' ];
		$upload_dir     = '/some/upload/directory';
		$overrides      = [
			'upload_dir' => $upload_dir,
		];
		$prep_overrides = [
			'upload_dir' => $upload_dir,
			'foo'        => 'bar',
		];
		$result         = [
			'bar'  => 'foo',
			'file' => '/my/path',
		];

		$normalized_result         = $result;
		$normalized_result['file'] = '/my/normalized/path';

		$this->sut->shouldReceive( 'is_global_upload' )->once()->with( $overrides )->andReturn( false );
		Functions\expect( 'get_main_site_id' )->never();
		Functions\expect( 'switch_to_blog' )->never();
		Functions\expect( 'restore_current_blog' )->never();

		Filters\expectAdded( 'upload_dir' )->once()->with( m::type( 'Closure' ) );
		$this->sut->shouldReceive( 'prepare_overrides' )->once()->with( $overrides )->andReturn( $prep_overrides );
		Functions\expect( 'wp_handle_upload' )->once()->with( $file, $prep_overrides )->andReturn( $result );
		Functions\expect( 'wp_normalize_path' )->once()->with( $result['file'] )->andReturn( $normalized_result['file'] );
		Filters\expectRemoved( 'upload_dir' )->once()->with( m::type( 'Closure' ) );

		$this->assertSame( $normalized_result, $this->sut->handle_upload( $file, $overrides ) );
	}

	/**
	 * Tests ::handle_upload.
	 *
	 * @covers ::handle_upload
	 */
	public function test_handle_upload_error() {
		$file           = [ 'foo' => 'bar' ];
		$upload_dir     = '/some/upload/directory';
		$overrides      = [
			'upload_dir' => $upload_dir,
		];
		$prep_overrides = [
			'upload_dir' => $upload_dir,
			'foo'        => 'bar',
		];
		$result         = [
			'bar'  => 'foo',
		];

		$this->sut->shouldReceive( 'is_global_upload' )->once()->with( $overrides )->andReturn( false );
		Functions\expect( 'get_main_site_id' )->never();
		Functions\expect( 'switch_to_blog' )->never();
		Functions\expect( 'restore_current_blog' )->never();

		Filters\expectAdded( 'upload_dir' )->once()->with( m::type( 'Closure' ) );
		$this->sut->shouldReceive( 'prepare_overrides' )->once()->with( $overrides )->andReturn( $prep_overrides );
		Functions\expect( 'wp_handle_upload' )->once()->with( $file, $prep_overrides )->andReturn( $result );
		Functions\expect( 'wp_normalize_path' )->never();
		Filters\expectRemoved( 'upload_dir' )->once()->with( m::type( 'Closure' ) );

		$this->assertSame( $result, $this->sut->handle_upload( $file, $overrides ) );
	}

	/**
	 * Tests ::handle_upload.
	 *
	 * @covers ::handle_upload
	 */
	public function test_handle_upload_global_upload() {
		$file           = [ 'foo' => 'bar' ];
		$upload_dir     = '/some/upload/directory';
		$overrides      = [
			'upload_dir' => $upload_dir,
		];
		$prep_overrides = [
			'upload_dir' => $upload_dir,
			'foo'        => 'bar',
		];
		$result         = [
			'bar'  => 'foo',
			'file' => '/my/path',
		];
		$main_site_id   = 5;

		$normalized_result         = $result;
		$normalized_result['file'] = '/my/normalized/path';

		$this->sut->shouldReceive( 'is_global_upload' )->once()->with( $overrides )->andReturn( true );
		Functions\expect( 'get_main_site_id' )->once()->andReturn( $main_site_id );
		Functions\expect( 'switch_to_blog' )->once()->with( $main_site_id );
		Functions\expect( 'restore_current_blog' )->once();

		Filters\expectAdded( 'upload_dir' )->once()->with( m::type( 'Closure' ) );
		$this->sut->shouldReceive( 'prepare_overrides' )->once()->with( $overrides )->andReturn( $prep_overrides );
		Functions\expect( 'wp_handle_upload' )->once()->with( $file, $prep_overrides )->andReturn( $result );
		Functions\expect( 'wp_normalize_path' )->once()->with( $result['file'] )->andReturn( $normalized_result['file'] );
		Filters\expectRemoved( 'upload_dir' )->once()->with( m::type( 'Closure' ) );

		$this->assertSame( $normalized_result, $this->sut->handle_upload( $file, $overrides ) );
	}

	/**
	 * Provides data for testing ::is_global_upload.
	 *
	 * @return array
	 */
	public function provide_is_global_upload_data() {
		return [
			'No global upload, singlesite' => [ false, false, false ],
			'No global upload, multisite'  => [ false, true, false ],
			'Global upload, singlesite'    => [ true, false, false ],
			'Global upload, singlesite'    => [ true, true, true ],
		];
	}

	/**
	 * Tests ::is_global_upload.
	 *
	 * @covers ::is_global_upload
	 *
	 * @dataProvider provide_is_global_upload_data
	 *
	 * @param  bool $global_upload Whether global uploads are enabled.
	 * @param  bool $multisite     Whether this is a multisite.
	 * @param  bool $result        The expected result.
	 */
	public function test_is_global_upload( $global_upload, $multisite, $result ) {
		$overrides = [
			'global_upload' => $global_upload,
		];

		if ( $global_upload ) {
			Functions\expect( 'is_multisite' )->once()->andReturn( $multisite );
		} else {
			Functions\expect( 'is_multisite' )->never();
		}

		$this->assertSame( $result, $this->sut->is_global_upload( $overrides ) );
	}

	/**
	 * Tests ::prepare_overrides.
	 *
	 * @covers ::prepare_overrides
	 */
	public function test_prepare_overrides() {
		$overrides = [
			'global_upload' => true,
			'foo'           => 'bar',
		];

		Functions\expect( 'wp_parse_args' )->once()->andReturnUsing( function( $overrides, $defaults ) {
			return \array_merge( $defaults, $overrides );
		} );

		$result = $this->sut->prepare_overrides( $overrides );

		$this->assertArrayHasKey( 'global_upload', $result );
		$this->assertArrayHasKey( 'foo', $result );
		$this->assertArrayHasKey( 'mimes', $result );
		$this->assertArrayHasKey( 'action', $result );
		$this->assertArrayHasKey( 'test_form', $result );
	}
}
