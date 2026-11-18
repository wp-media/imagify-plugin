<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\classes\ImagifyViews;

use Imagify_Views;
use Imagify\Tests\Integration\TestCase;

/**
 * @covers Imagify_Views::is_files_page
 * @covers Imagify_Views::get_requested_page
 *
 * @group  ImagifyViews
 */
class Test_IsFilesPage extends TestCase {
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Original $_GET and $pagenow, restored after each test.
	 *
	 * @var array
	 */
	private $globals = [];

	public function set_up() {
		parent::set_up();

		$this->globals = [ $_GET, $GLOBALS['pagenow'] ?? null ];
	}

	public function tear_down() {
		[ $_GET, $GLOBALS['pagenow'] ] = $this->globals;

		parent::tear_down();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( $config, $expected ) {
		$GLOBALS['pagenow'] = $config['pagenow'];
		$_GET               = null === $config['page'] ? [] : [ 'page' => $config['page'] ];

		$this->assertSame( $expected, Imagify_Views::get_instance()->is_files_page() );
	}
}
