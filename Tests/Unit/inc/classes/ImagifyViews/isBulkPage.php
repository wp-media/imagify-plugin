<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\inc\classes\ImagifyViews;

use Brain\Monkey\Functions;
use Imagify_Views;
use Imagify\Tests\Unit\TestCase;
use ReflectionClass;

/**
 * @covers Imagify_Views::is_bulk_page
 * @covers Imagify_Views::get_requested_page
 *
 * @group  ImagifyViews
 */
class Test_IsBulkPage extends TestCase {
	/**
	 * Original $_GET and $pagenow, restored after each test.
	 *
	 * @var array
	 */
	private $globals = [];

	public function setUp(): void {
		parent::setUp();

		$this->globals = [ $_GET, $GLOBALS['pagenow'] ?? null ];

		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_text_field' )->alias(
			function ( $value ) {
				return is_string( $value ) ? trim( strip_tags( $value ) ) : '';
			}
		);
		Functions\when( 'imagify_is_active_for_network' )->justReturn( false );
	}

	public function tearDown(): void {
		[ $_GET, $GLOBALS['pagenow'] ] = $this->globals;

		parent::tearDown();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( $config, $expected ) {
		$GLOBALS['pagenow'] = $config['pagenow'];
		$_GET               = null === $config['page'] ? [] : [ 'page' => $config['page'] ];

		$this->assertSame( $expected, $this->get_views()->is_bulk_page() );
	}

	/**
	 * Build the views instance without its constructor, which needs a real filesystem.
	 *
	 * @return Imagify_Views
	 */
	private function get_views() {
		$class = new ReflectionClass( Imagify_Views::class );
		$views = $class->newInstanceWithoutConstructor();
		$slugs = [
			'slug_settings' => IMAGIFY_SLUG,
			'slug_bulk'     => IMAGIFY_SLUG . '-bulk-optimization',
			'slug_files'    => IMAGIFY_SLUG . '-files',
		];

		foreach ( $slugs as $property => $slug ) {
			$reflection = $class->getProperty( $property );
			$reflection->setAccessible( true );
			$reflection->setValue( $views, $slug );
		}

		return $views;
	}
}
