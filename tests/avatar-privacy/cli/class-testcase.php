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

namespace Avatar_Privacy\Tests\Avatar_Privacy\CLI;

use Mockery as m;

/**
 * Special base class for CLI command unit tests.
 */
abstract class TestCase extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * Alias mock for static WP_CLI methods.
	 *
	 * @var \WP_CLI
	 */
	protected $wp_cli;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		// API class mock.
		$this->wp_cli = m::mock( 'alias:' . \WP_CLI::class );
	}

	/**
	 * Mocks WP_CLI::error.
	 *
	 * @param  object|null $expectation Optional. A mockery type expectation, or null. Default null.
	 */
	protected function expect_wp_cli_error( $expectation = null ) {
		$this->expectException( \RuntimeException::class );

		$method = $this->wp_cli->shouldReceive( 'error' )->once();

		if ( ! empty( $expectation ) ) {
			$method = $method->with( $expectation );
		}

		$method->andThrow( \RuntimeException::class );

	}

	/**
	 * Mocks WP_CLI::success.
	 *
	 * @param  object|null $expectation    Optional. A mockery type expectation, or null. Default null.
	 * @param  bool        $more_than_once Optional. Allow the method to be called more than once. Default false.
	 */
	protected function expect_wp_cli_success( $expectation = null, $more_than_once = false ) {
		$this->wp_cli->shouldReceive( 'error' )->never();

		$method = $this->wp_cli->shouldReceive( 'success' );

		if ( $more_than_once ) {
			$method = $method->atLeast();
		}

		$method = $method->once();

		if ( ! empty( $expectation ) ) {
			$method->with( $expectation );
		}
	}
}
