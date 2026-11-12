<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Bulk;

use Imagify\Bulk\Bulk;
use Imagify\Tests\Integration\TestCase;

/**
 * @covers \Imagify\Bulk\Bulk::get_context
 * @covers \Imagify\Bulk\Bulk::get_request_param
 *
 * @group  Bulk
 */
class Test_GetContext extends TestCase {
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Original $_GET and $_POST, restored after each test.
	 *
	 * @var array
	 */
	private $superglobals = [];

	public function set_up() {
		parent::set_up();

		$this->superglobals = [ $_GET, $_POST ];
	}

	public function tear_down() {
		[ $_GET, $_POST ] = $this->superglobals;

		parent::tear_down();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( $config, $expected ) {
		$_GET  = $config['get'];
		$_POST = $config['post'];

		$this->assertSame( $expected, ( new Bulk() )->get_context( ...$config['args'] ) );
	}
}
