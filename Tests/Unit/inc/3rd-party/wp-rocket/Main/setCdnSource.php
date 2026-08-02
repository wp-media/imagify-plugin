<?php
namespace {

	if ( ! class_exists( 'Imagify_Filesystem', false ) ) {
		/*
		 * Load the real class rather than declaring a stub of it.
		 *
		 * `Main::set_cdn_source()` reaches `\Imagify_Filesystem::get_instance()->get_site_root_url()`
		 * on any path that resolves a non-empty CDN URL. This used to declare a lightweight stub,
		 * because the real class requires WP core filesystem classes from ABSPATH. The unit
		 * bootstrap now resolves those (see register_abspath_stubs() in Tests/Unit/bootstrap.php),
		 * so the real class loads here.
		 *
		 * Declaring a stub is no longer merely unnecessary, it is fatal: any other test that loads
		 * the real file in the same process (Tests/Unit/inc/classes/ImagifyFilesystem) would hit
		 * "Cannot redeclare class Imagify_Filesystem" and take the whole suite down. The singleton
		 * is mocked per-test instead, in setUp().
		 */
		require_once IMAGIFY_PLUGIN_ROOT . 'inc/classes/class-imagify-filesystem.php';
	}

	if ( ! class_exists( 'Imagify\\ThirdParty\\WPRocket\\Main', false ) ) {
		// Force-load this worktree's copy: composer's classmap/autoload_psr4 baked-in
		// $baseDir resolves symlinked vendor/ to the main checkout, not this worktree.
		require_once IMAGIFY_PLUGIN_ROOT . 'inc/3rd-party/wp-rocket/classes/Main.php';
	}
}

namespace Imagify\Tests\Unit\inc\ThirdParty\WPRocket\Main {

	use Brain\Monkey\Filters;
	use Brain\Monkey\Functions;
	use Imagify\Tests\Unit\TestCase;
	use Imagify\ThirdParty\WPRocket\Main;

	/**
	 * Tests for \Imagify\ThirdParty\WPRocket\Main::set_cdn_source().
	 *
	 * @covers \Imagify\ThirdParty\WPRocket\Main::set_cdn_source
	 * @group  WPRocket
	 */
	class Test_SetCdnSource extends TestCase {
		/**
		 * The $source array passed to set_cdn_source() when it's not expected to be altered.
		 *
		 * @var array
		 */
		private $originalSource = [
			'name' => 'Original',
			'url'  => 'https://original.example.com',
		];

		protected function setUp(): void {
			parent::setUp();

			Functions\when( 'get_rocket_option' )->justReturn( true );

			$this->mockFilesystemSingleton();
		}

		protected function tearDown(): void {
			$this->getFilesystemInstanceProperty()->setValue( null, null );

			parent::tearDown();
		}

		/**
		 * Point `Imagify_Filesystem::get_instance()` at a mock returning a fixed site root URL.
		 *
		 * `set_cdn_source()` only ever asks the filesystem for that URL, and the real
		 * implementation would need WP multisite/home_url plumbing to answer. Replacing the
		 * singleton keeps this test about set_cdn_source() and leaves the real class free to be
		 * loaded by its own tests in the same process.
		 */
		private function mockFilesystemSingleton() {
			$filesystem = \Mockery::mock( 'Imagify_Filesystem' );
			$filesystem->shouldReceive( 'get_site_root_url' )->andReturn( 'https://example.com/' );

			$this->getFilesystemInstanceProperty()->setValue( null, $filesystem );
		}

		/**
		 * The singleton holder lives on InstanceGetterTrait, as a protected static.
		 *
		 * @return \ReflectionProperty
		 */
		private function getFilesystemInstanceProperty(): \ReflectionProperty {
			$property = new \ReflectionProperty( \Imagify_Filesystem::class, 'instance' );
			$property->setAccessible( true );

			return $property;
		}

		/**
		 * Stub the tail of the method (URL scheme normalisation) so it doesn't blow up
		 * once a non-empty $url has been resolved, either from the container or the
		 * get_rocket_cdn_cnames() fallback.
		 */
		private function stubUrlSchemeTail() {
			Functions\when( 'wp_parse_url' )->justReturn( [ 'scheme' => 'https' ] );
			Functions\when( 'set_url_scheme' )->alias(
				function ( $url, $scheme ) {
					return $scheme . ':' . $url;
				}
			);
		}

		/**
		 * Regression guard for the reported bug: the container exposes get() but does not
		 * register the 'cdn' alias. has( 'cdn' ) must be checked before get() is attempted,
		 * so get() throwing here proves it was never called.
		 */
		public function testShouldFallBackToCnamesWhenServiceNotRegistered() {
			$container = new class() {
				public function has( $id ) {
					return false;
				}

				public function get( $id ) {
					throw new \RuntimeException( 'get() must not be called when has( "cdn" ) returns false.' );
				}
			};

			Filters\expectApplied( 'rocket_container' )->once()->andReturn( $container );

			Functions\when( 'get_rocket_cdn_cnames' )->justReturn( [ 'cdn.example.com' ] );
			$this->stubUrlSchemeTail();

			$source = ( new Main() )->set_cdn_source( $this->originalSource );

			$this->assertSame( 'WP Rocket', $source['name'] );
			$this->assertSame( 'https://cdn.example.com', $source['url'] );
		}

		/**
		 * has( 'cdn' ) returns true but get( 'cdn' ) throws (e.g. a ContainerException):
		 * the throw must be contained and the CNAME fallback used.
		 */
		public function testShouldContainThrowingGet() {
			$container = new class() {
				public function has( $id ) {
					return true;
				}

				public function get( $id ) {
					throw new \Exception( 'Alias (cdn) is not being managed by the container.' );
				}
			};

			Filters\expectApplied( 'rocket_container' )->once()->andReturn( $container );

			Functions\when( 'get_rocket_cdn_cnames' )->justReturn( [ 'fallback.example.com' ] );
			$this->stubUrlSchemeTail();

			$source = ( new Main() )->set_cdn_source( $this->originalSource );

			$this->assertSame( 'WP Rocket', $source['name'] );
			$this->assertSame( 'https://fallback.example.com', $source['url'] );
		}

		/**
		 * The resolved $cdn service itself throws from get_cdn_urls(): must be contained
		 * by the same boundary and fall back.
		 */
		public function testShouldContainThrowingGetCdnUrls() {
			$cdn = new class() {
				public function get_cdn_urls( $types ) {
					throw new \Exception( 'CDN service could not build its URLs.' );
				}
			};

			$container = new class( $cdn ) {
				private $cdn;

				public function __construct( $cdn ) {
					$this->cdn = $cdn;
				}

				public function has( $id ) {
					return true;
				}

				public function get( $id ) {
					return $this->cdn;
				}
			};

			Filters\expectApplied( 'rocket_container' )->once()->andReturn( $container );

			Functions\when( 'get_rocket_cdn_cnames' )->justReturn( [ 'fallback.example.com' ] );
			$this->stubUrlSchemeTail();

			$source = ( new Main() )->set_cdn_source( $this->originalSource );

			$this->assertSame( 'WP Rocket', $source['name'] );
			$this->assertSame( 'https://fallback.example.com', $source['url'] );
		}

		/**
		 * Happy path is unchanged: the container resolves a working 'cdn' service and its
		 * URL is used, not the get_rocket_cdn_cnames() fallback.
		 */
		public function testShouldUseContainerCdnUrlWhenAvailable() {
			$cdn = new class() {
				public function get_cdn_urls( $types ) {
					return [ 'cdn-from-container.example.com' ];
				}
			};

			$container = new class( $cdn ) {
				private $cdn;

				public function __construct( $cdn ) {
					$this->cdn = $cdn;
				}

				public function has( $id ) {
					return true;
				}

				public function get( $id ) {
					return $this->cdn;
				}
			};

			Filters\expectApplied( 'rocket_container' )->once()->andReturn( $container );

			Functions\expect( 'get_rocket_cdn_cnames' )->never();
			$this->stubUrlSchemeTail();

			$source = ( new Main() )->set_cdn_source( $this->originalSource );

			$this->assertSame( 'WP Rocket', $source['name'] );
			$this->assertSame( 'https://cdn-from-container.example.com', $source['url'] );
		}

		/**
		 * The container fails AND get_rocket_cdn_cnames() has nothing to offer: the
		 * original $source must be returned untouched.
		 */
		public function testShouldReturnOriginalSourceWhenNoFallbackAvailable() {
			$container = new class() {
				public function has( $id ) {
					return true;
				}

				public function get( $id ) {
					throw new \Exception( 'Container could not resolve the service.' );
				}
			};

			Filters\expectApplied( 'rocket_container' )->once()->andReturn( $container );

			Functions\when( 'get_rocket_cdn_cnames' )->justReturn( [] );

			$source = ( new Main() )->set_cdn_source( $this->originalSource );

			$this->assertSame( $this->originalSource, $source );
		}
	}
}
