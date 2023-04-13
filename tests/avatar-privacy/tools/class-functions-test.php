<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2023 Peter Putzer.
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

use function Avatar_Privacy\Tools\delete_file;

/**
 * Unit tests for Avatar Privacy\Tools functions.
 */
class Functions_Test extends \Avatar_Privacy\Tests\TestCase {


	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		$filesystem = [
			'my'     => [
				'file'     => [
					'path' => 'something',
				],
				'filtered' => [
					'valid' => [
						'path' => 'something',
					],
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
	}

	/**
	 * Tests delete_file function.
	 *
	 * @covers \Avatar_Privacy\Tools\delete_file
	 */
	public function test_delete_file(): void {
		$orig_file     = vfsStream::url( 'root/my/file/path' );
		$filtered_file = vfsStream::url( 'root/my/filtered/valid/path' );

		Filters\expectApplied( 'wp_delete_file' )->with( $orig_file )->andReturn( $filtered_file );

		$this->assertTrue( delete_file( $orig_file ) );
	}

	/**
	 * Tests delete_file function.
	 *
	 * @covers \Avatar_Privacy\Tools\delete_file
	 */
	public function test_delete_file_invalid_file(): void {
		$orig_file     = vfsStream::url( 'root/my/file/path' );
		$filtered_file = vfsStream::url( 'root/my/filtered/invalid/path' );

		Filters\expectApplied( 'wp_delete_file' )->with( $orig_file )->andReturn( $filtered_file );

		$this->assertFalse( delete_file( $orig_file ) );
	}

	/**
	 * Tests delete_file function.
	 *
	 * @covers \Avatar_Privacy\Tools\delete_file
	 */
	public function test_delete_file_empty_filter_result(): void {
		$orig_file     = vfsStream::url( 'root/my/file/path' );
		$filtered_file = '';

		Filters\expectApplied( 'wp_delete_file' )->with( $orig_file )->andReturn( $filtered_file );

		$this->assertFalse( delete_file( $orig_file ) );
	}
}
