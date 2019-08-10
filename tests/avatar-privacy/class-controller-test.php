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

namespace Avatar_Privacy\Tests\Avatar_Privacy;

use Avatar_Privacy\Core;

use Avatar_Privacy\Components\Avatar_Handling;
use Avatar_Privacy\Components\Block_Editor;
use Avatar_Privacy\Components\Comments;
use Avatar_Privacy\Components\Image_Proxy;
use Avatar_Privacy\Components\Integrations;
use Avatar_Privacy\Components\Network_Settings_Page;
use Avatar_Privacy\Components\Privacy_Tools;
use Avatar_Privacy\Components\REST_API;
use Avatar_Privacy\Components\Setup;
use Avatar_Privacy\Components\Settings_Page;
use Avatar_Privacy\Components\Shortcodes;
use Avatar_Privacy\Components\User_Profile;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

/**
 * Unit tests for plugin controller.
 *
 * @coversDefaultClass \Avatar_Privacy\Controller
 * @usesDefaultClass \Avatar_Privacy\Controller
 */
class Controller_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() { // @codingStandardsIgnoreLine
		parent::setUp();
	}

	/**
	 * Necesssary clean-up work.
	 */
	protected function tearDown() { // @codingStandardsIgnoreLine
		parent::tearDown();
	}

	/**
	 * Tests constructor.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$controller = m::mock(
			\Avatar_Privacy\Controller::class,
			[
				m::mock( Core::class ),
				m::mock( Setup::class ),
				m::mock( Image_Proxy::class ),
				m::mock( Avatar_Handling::class ),
				m::mock( Comments::class ),
				m::mock( User_Profile::class ),
				m::mock( Settings_Page::class ),
				m::mock( Network_Settings_Page::class ),
				m::mock( Privacy_Tools::class ),
				m::mock( REST_API::class ),
				m::mock( Integrations::class ),
				m::mock( Shortcodes::class ),
				m::mock( Block_Editor::class ),
			]
		)->makePartial();

		$this->assertInstanceOf( \Avatar_Privacy\Controller::class, $controller );

		return $controller;
	}

	/**
	 * Tests run method.
	 *
	 * @depends test_constructor
	 *
	 * @covers ::run
	 *
	 * @uses \Avatar_Privacy\Core::set_instance
	 *
	 * @param \Avatar_Privacy\Controller $controller Required.
	 */
	public function test_run( $controller ) {
		foreach ( $this->getValue( $controller, 'components', \Avatar_Privacy\Controller::class ) as $component ) {
			$component->shouldReceive( 'run' )->once();
		}

		$this->assertNull( $controller->run() );
	}
}
