<?php

namespace Imagify\Tests\Integration\inc\classes\ImagifyFilesystem;

use Imagify_Filesystem;

/**
 * @covers \Imagify_Filesystem::get_site_root_url
 * @group  ImagifyFilesystem
 */
class Test_GetSiteRootUrl extends TestCase {

	/**
	 * Test the default value is returned when no filter is hooked.
	 */
	public function testShouldReturnHomeUrlByDefault() {
		$filesystem = Imagify_Filesystem::get_instance();

		$this->assertSame( home_url( '/' ), $filesystem->get_site_root_url() );
	}

	/**
	 * Test a hooked callback replacing the value is honoured.
	 */
	public function testShouldHonourFilteredValue() {
		$filesystem = Imagify_Filesystem::get_instance();

		$this->addTrackedFilter(
			'imagify_site_root_url',
			function () {
				return 'https://mapped-domain.example/';
			}
		);

		$this->assertSame( 'https://mapped-domain.example/', $filesystem->get_site_root_url() );
	}

	/**
	 * Test the callback receives home_url( '/' ) as $root_url and get_current_blog_id() as $blog_id.
	 */
	public function testShouldPassRootUrlAndBlogIdToCallback() {
		$filesystem = Imagify_Filesystem::get_instance();

		$received_args = [];

		$this->addTrackedFilter(
			'imagify_site_root_url',
			function ( $root_url, $blog_id ) use ( &$received_args ) {
				$received_args = [ $root_url, $blog_id ];
				return $root_url;
			}
		);

		$filesystem->get_site_root_url();

		$this->assertSame( home_url( '/' ), $received_args[0] );
		$this->assertSame( get_current_blog_id(), $received_args[1] );
	}

	/**
	 * Test a callback returning a URL with no trailing slash yields a trailing-slashed result.
	 */
	public function testShouldTrailingSlashUnslashedFilterValue() {
		$filesystem = Imagify_Filesystem::get_instance();

		$this->addTrackedFilter(
			'imagify_site_root_url',
			function () {
				return 'https://mapped-domain.example';
			}
		);

		$this->assertSame( 'https://mapped-domain.example/', $filesystem->get_site_root_url() );
	}

	/**
	 * Test a callback returning an empty string falls back to home_url( '/' ).
	 */
	public function testShouldFallBackToHomeUrlWhenFilterReturnsEmptyString() {
		$filesystem = Imagify_Filesystem::get_instance();

		$this->addTrackedFilter(
			'imagify_site_root_url',
			function () {
				return '';
			}
		);

		$this->assertSame( home_url( '/' ), $filesystem->get_site_root_url() );
	}

	/**
	 * Test the callback runs only once per blog: the value is cached after the first call.
	 */
	public function testShouldOnlyRunFilterOnceForTheSameBlog() {
		$filesystem = Imagify_Filesystem::get_instance();

		$call_count = 0;

		$this->addTrackedFilter(
			'imagify_site_root_url',
			function ( $root_url ) use ( &$call_count ) {
				++$call_count;
				return $root_url;
			}
		);

		$first  = $filesystem->get_site_root_url();
		$second = $filesystem->get_site_root_url();

		$this->assertSame( 1, $call_count );
		$this->assertSame( $first, $second );
	}

	/**
	 * Test that resetting the cache property picks up a different filtered value,
	 * proving the cache is resettable and the filter is not bypassed by a stale static.
	 */
	public function testShouldPickUpNewFilterValueAfterCacheReset() {
		$filesystem = Imagify_Filesystem::get_instance();

		$this->addTrackedFilter(
			'imagify_site_root_url',
			function () {
				return 'https://first-domain.example/';
			}
		);

		$this->assertSame( 'https://first-domain.example/', $filesystem->get_site_root_url() );

		$this->setPropertyValue( 'site_root_urls', $filesystem, [] );

		$this->addTrackedFilter(
			'imagify_site_root_url',
			function () {
				return 'https://second-domain.example/';
			}
		);

		$this->assertSame( 'https://second-domain.example/', $filesystem->get_site_root_url() );
	}
}
