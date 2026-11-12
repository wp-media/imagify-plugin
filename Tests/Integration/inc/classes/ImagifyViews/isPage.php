<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\classes\ImagifyViews;

use Imagify_Views;
use Imagify\Tests\Integration\TestCase;

/**
 * @covers Imagify_Views::is_settings_page
 * @covers Imagify_Views::is_bulk_page
 * @covers Imagify_Views::is_files_page
 *
 * @group  ImagifyViews
 */
class Test_IsPage extends TestCase {
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	private $original_pagenow;

	public function set_up() {
		parent::set_up();

		$this->original_pagenow = isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : null;
	}

	public function tear_down() {
		unset( $_GET['page'] );
		$GLOBALS['pagenow'] = $this->original_pagenow;

		parent::tear_down();
	}

	public function testShouldDetectPagesFromThePageArgument() {
		$views = Imagify_Views::get_instance();

		$GLOBALS['pagenow'] = 'upload.php';
		$_GET['page']       = $views->get_bulk_page_slug();

		$this->assertTrue( $views->is_bulk_page() );
		$this->assertFalse( $views->is_files_page() );
		$this->assertFalse( $views->is_settings_page() );

		$_GET['page'] = $views->get_files_page_slug();

		$this->assertTrue( $views->is_files_page() );
		$this->assertFalse( $views->is_bulk_page() );

		$GLOBALS['pagenow'] = 'options-general.php';
		$_GET['page']       = $views->get_settings_page_slug();

		$this->assertTrue( $views->is_settings_page() );
	}

	public function testShouldNotDetectPagesWithoutThePageArgument() {
		$views = Imagify_Views::get_instance();

		foreach ( [ 'upload.php', 'options-general.php' ] as $pagenow ) {
			$GLOBALS['pagenow'] = $pagenow;

			$this->assertFalse( $views->is_bulk_page() );
			$this->assertFalse( $views->is_files_page() );
			$this->assertFalse( $views->is_settings_page() );
		}
	}
}
