<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Picture\Display;

use Imagify\Picture\Display;
use Imagify\Tests\Unit\TestCase;
use ReflectionClass;

/**
 * Tests for \Imagify\Picture\Display::get_admin_bar_site_icon_tags().
 *
 * @covers \Imagify\Picture\Display::get_admin_bar_site_icon_tags
 * @group  PictureDisplay
 * @since  2.3.6
 */
class GetAdminBarSiteIconTagsTest extends TestCase {

	/**
	 * Tests get_admin_bar_site_icon_tags() returns the admin bar site icon <img> tags only.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   Test configuration.
	 * @param array $expected Expected results.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		// The method does not use the filesystem dependency: skip the constructor.
		$display = ( new ReflectionClass( Display::class ) )->newInstanceWithoutConstructor();
		$method  = $this->get_reflective_method( 'get_admin_bar_site_icon_tags', Display::class );

		$this->assertSame( $expected['tags'], $method->invoke( $display, $config['html'] ) );
	}
}
