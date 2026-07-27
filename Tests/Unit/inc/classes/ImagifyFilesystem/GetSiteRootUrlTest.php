<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\inc\classes\ImagifyFilesystem;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Imagify_Filesystem;
use Imagify\Tests\Unit\TestCase;
use ReflectionClass;

/**
 * Tests for \Imagify_Filesystem::get_site_root_url().
 *
 * @covers \Imagify_Filesystem::get_site_root_url
 * @group  ImagifyFilesystemGetSiteRootUrl
 * @since  2.3.1
 */
class GetSiteRootUrlTest extends TestCase {

	/**
	 * Load the class under test. It is not autoloaded (legacy `class-*.php` naming) and it
	 * requires WP_Filesystem_Base/Direct from ABSPATH, which Tests/Unit/init-tests.php points
	 * at Tests/Fixtures/WP/ so those two files resolve to stubs.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once IMAGIFY_PLUGIN_ROOT . 'inc/classes/class-imagify-filesystem.php';
	}

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'trailingslashit' )->alias(
			function ( $string ) {
				return rtrim( (string) $string, '/\\' ) . '/';
			}
		);
		Functions\when( 'home_url' )->justReturn( 'https://example.com/' );
		Functions\when( 'get_current_blog_id' )->justReturn( 1 );
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'is_main_site' )->justReturn( true );
	}

	/**
	 * Get an instance without invoking the constructor: it only defines FS_CHMOD_*
	 * constants from real files on disk, which this method does not need.
	 *
	 * @return Imagify_Filesystem
	 */
	private function get_filesystem_instance(): Imagify_Filesystem {
		return ( new ReflectionClass( Imagify_Filesystem::class ) )->newInstanceWithoutConstructor();
	}

	/**
	 * On a single site with no filter hooked, the value is home_url( '/' ).
	 */
	public function testShouldReturnHomeUrlOnSingleSite() {
		Filters\expectApplied( 'imagify_site_root_url' )
			->once()
			->with( 'https://example.com/', 1 )
			->andReturnFirstArg();

		$this->assertSame( 'https://example.com/', $this->get_filesystem_instance()->get_site_root_url() );
	}

	/**
	 * On a multisite main site, the value is home_url( '/' ) — the network branch is skipped.
	 */
	public function testShouldReturnHomeUrlOnMultisiteMainSite() {
		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\when( 'is_main_site' )->justReturn( true );

		Filters\expectApplied( 'imagify_site_root_url' )->once()->andReturnFirstArg();

		$this->assertSame( 'https://example.com/', $this->get_filesystem_instance()->get_site_root_url() );
	}

	/**
	 * On a multisite subsite, the value is built from the current network's domain and path.
	 */
	public function testShouldBuildUrlFromNetworkOnMultisiteSubSite() {
		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\when( 'is_main_site' )->justReturn( false );
		Functions\when( 'is_ssl' )->justReturn( true );
		Functions\when( 'get_network' )->justReturn(
			(object) [
				'domain' => 'network.example',
				'path'   => '/',
			]
		);
		Functions\when( 'set_url_scheme' )->alias(
			function ( $url, $scheme ) {
				return preg_replace( '@^\w+://@', $scheme . '://', $url );
			}
		);

		Filters\expectApplied( 'imagify_site_root_url' )->once()->andReturnFirstArg();

		$this->assertSame( 'https://network.example/', $this->get_filesystem_instance()->get_site_root_url() );
	}

	/**
	 * On a multisite subsite with no network resolvable, it falls back to home_url( '/' ).
	 */
	public function testShouldFallBackToHomeUrlWhenNetworkIsUnavailable() {
		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\when( 'is_main_site' )->justReturn( false );
		Functions\when( 'get_network' )->justReturn( false );

		Filters\expectApplied( 'imagify_site_root_url' )->once()->andReturnFirstArg();

		$this->assertSame( 'https://example.com/', $this->get_filesystem_instance()->get_site_root_url() );
	}

	/**
	 * A hooked callback replacing the value is honoured — the domain-mapping use case.
	 */
	public function testShouldHonourFilteredValue() {
		Filters\expectApplied( 'imagify_site_root_url' )
			->once()
			->andReturn( 'https://mapped-domain.example/' );

		$this->assertSame( 'https://mapped-domain.example/', $this->get_filesystem_instance()->get_site_root_url() );
	}

	/**
	 * A filtered value with no trailing slash is trailing-slashed.
	 */
	public function testShouldTrailingSlashUnslashedFilteredValue() {
		Filters\expectApplied( 'imagify_site_root_url' )
			->once()
			->andReturn( 'https://mapped-domain.example' );

		$this->assertSame( 'https://mapped-domain.example/', $this->get_filesystem_instance()->get_site_root_url() );
	}

	/**
	 * A filtered value that is empty falls back to home_url( '/' ).
	 *
	 * @dataProvider provideEmptyFilteredValues
	 *
	 * @param mixed $filtered_value Value returned by the hooked callback.
	 */
	public function testShouldFallBackToHomeUrlWhenFilteredValueIsEmpty( $filtered_value ) {
		Filters\expectApplied( 'imagify_site_root_url' )
			->once()
			->andReturn( $filtered_value );

		$this->assertSame( 'https://example.com/', $this->get_filesystem_instance()->get_site_root_url() );
	}

	/**
	 * Empty-ish values a badly-behaved callback could return.
	 *
	 * @return array
	 */
	public function provideEmptyFilteredValues(): array {
		return [
			'empty string' => [ '' ],
			'null'         => [ null ],
			'false'        => [ false ],
			'zero'         => [ 0 ],
		];
	}

	/**
	 * The value is computed once per blog: a second call hits the cache.
	 */
	public function testShouldOnlyApplyFilterOncePerBlog() {
		Filters\expectApplied( 'imagify_site_root_url' )
			->once()
			->andReturn( 'https://mapped-domain.example/' );

		$filesystem = $this->get_filesystem_instance();

		$first  = $filesystem->get_site_root_url();
		$second = $filesystem->get_site_root_url();

		$this->assertSame( 'https://mapped-domain.example/', $first );
		$this->assertSame( $first, $second );
	}

	/**
	 * The cache is keyed by blog: after switch_to_blog(), the value is recomputed rather
	 * than reusing the previous blog's URL. This is the regression the blog-keyed cache fixes.
	 */
	public function testShouldNotReuseAnotherBlogsCachedUrl() {
		$blog_id = 1;

		Functions\when( 'get_current_blog_id' )->alias(
			function () use ( &$blog_id ) {
				return $blog_id;
			}
		);
		Functions\when( 'home_url' )->alias(
			function () use ( &$blog_id ) {
				return 'https://blog-' . $blog_id . '.example/';
			}
		);

		Filters\expectApplied( 'imagify_site_root_url' )->twice()->andReturnFirstArg();

		$filesystem = $this->get_filesystem_instance();

		$this->assertSame( 'https://blog-1.example/', $filesystem->get_site_root_url() );

		$blog_id = 2;

		$this->assertSame( 'https://blog-2.example/', $filesystem->get_site_root_url() );
	}
}
