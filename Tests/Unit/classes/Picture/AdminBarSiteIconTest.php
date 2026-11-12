<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Picture;

use Imagify\Picture\Display;
use Imagify\Tests\Unit\TestCase;
use ReflectionClass;

/**
 * Tests for \Imagify\Picture\Display::get_admin_bar_site_icon_tags().
 *
 * @covers \Imagify\Picture\Display::get_admin_bar_site_icon_tags
 * @group  PictureDisplayAdminBarSiteIcon
 * @since  2.3.1
 */
class AdminBarSiteIconTest extends TestCase {

	/**
	 * Call the protected method on an instance created without its constructor.
	 *
	 * @param string $html Page HTML.
	 *
	 * @return array
	 */
	private function get_tags( string $html ): array {
		$reflection = new ReflectionClass( Display::class );
		$display    = $reflection->newInstanceWithoutConstructor();
		$method     = $reflection->getMethod( 'get_admin_bar_site_icon_tags' );
		$method->setAccessible( true );

		return $method->invoke( $display, $html );
	}

	public function testShouldReturnSiteIconTagFromCoreMarkup() {
		$img  = '<img class="site-icon" src="https://example.org/icon-20x20.png" srcset="https://example.org/icon-40x40.png 2x" alt="" width="20" height="20" />';
		$html = "<div id=\"wpadminbar\"><li role='group' id='wp-admin-bar-site-name' class=\"menupop has-site-icon\"><a class='ab-item' aria-haspopup=\"true\" href=\"https://example.org/\">{$img}Site</a></li></div><img src=\"https://example.org/a.jpg\" />";

		$this->assertSame( [ $img ], $this->get_tags( $html ) );
	}

	public function testShouldHandleDoubleQuotedIdAndReorderedAttributes() {
		$img  = '<img width="20" height="20" alt="" src="https://example.org/i.png" class="site-icon" />';
		$html = "<li id=\"wp-admin-bar-site-name\" class=\"menupop\"><a class=\"ab-item\" href=\"/\">{$img} Site</a></li>";

		$this->assertSame( [ $img ], $this->get_tags( $html ) );
	}

	public function testShouldReturnEmptyWithoutAdminBar() {
		$this->assertSame( [], $this->get_tags( '<body><img class="site-icon" src="a.png" /></body>' ) );
	}

	public function testShouldNotCaptureLaterImageWhenSiteNameHasNoIcon() {
		$html = "<li role='group' id='wp-admin-bar-site-name' class=\"menupop\"><a class='ab-item' href=\"/\">Site</a><div class=\"ab-sub-wrapper\"></div></li><li><a href=\"#\"><img src=\"menu.png\" /></a></li><img src=\"content.jpg\" />";

		$this->assertSame( [], $this->get_tags( $html ) );
	}
}
