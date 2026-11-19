<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\classes\ImagifyAdminAjaxPost;

use Imagify_Admin_Ajax_Post;
use Imagify\Tests\Integration\TestCase;

/**
 * @covers Imagify_Admin_Ajax_Post::get_context
 * @covers \Imagify\Traits\RequestParamTrait::get_request_param
 *
 * @group  ImagifyAdminAjaxPost
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

		$this->assertSame( $expected, Imagify_Admin_Ajax_Post::get_instance()->get_context( ...$config['args'] ) );
	}
}
