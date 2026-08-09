<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetMediaStatus;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Imagify\Abilities\GetMediaStatus;
use Imagify\Abilities\MediaResolver;
use Imagify\Optimization\Data\WP;
use Imagify\Optimization\Process\ProcessInterface;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GetMediaStatus::execute().
 *
 * Uses a testable subclass of GetMediaStatus that overrides `create_wp_data()`
 * to inject a mock WP instance, so the real execute() logic is always exercised.
 *
 * @covers \Imagify\Abilities\GetMediaStatus::execute
 * @group  MCP
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Test_Execute extends TestCase {

	/**
	 * Reset the MediaResolver query factory and stub i18n functions so each
	 * test starts with a clean slate.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Functions\stubTranslationFunctions();
		MediaResolver::set_query_factory( null );
	}

	/**
	 * Inject a fake query factory that returns an object with the given
	 * `posts` array. Used by filename-resolution tests.
	 *
	 * @param array $posts Attachment IDs returned by the fake query.
	 * @return void
	 */
	private function stubFilenameQuery( array $posts ): void {
		$obj       = new \stdClass();
		$obj->posts = $posts;

		MediaResolver::set_query_factory(
			static function () use ( $obj ) {
				return $obj;
			}
		);
	}

	/**
	 * Build a testable GetMediaStatus subclass with a mocked WP data object.
	 *
	 * @param array $opt_data            Optimization data returned by get_optimization_data().
	 * @param int   $original_size_bytes Value returned by get_original_size(false).
	 *                                   For unoptimized media this comes from the filesystem;
	 *                                   for optimized media it is sizes.full.original_size.
	 * @return GetMediaStatus
	 */
	private function make_ability( array $opt_data, int $original_size_bytes = 0 ): GetMediaStatus {
		// Simulate an existing attachment so the get_post() guard passes.
		Functions\when( 'get_post' )->justReturn( true );

		$wp_mock = $this->createMock( WP::class );
		$wp_mock->method( 'get_optimization_data' )->willReturn( $opt_data );
		$wp_mock->method( 'get_original_size' )->with( false )->willReturn( $original_size_bytes );

		return new class( $wp_mock ) extends GetMediaStatus {
			/**
			 * Mocked WP data object.
			 *
			 * @var WP
			 */
			private $wp_mock;

			/**
			 * Constructor.
			 *
			 * @param WP $wp_mock Mocked WP data object.
			 */
			public function __construct( WP $wp_mock ) {
				$this->wp_mock = $wp_mock;
			}

			/**
			 * Returns the injected WP mock instead of constructing a real one.
			 *
			 * @param int $media_id Media ID (unused).
			 * @return WP
			 */
			protected function create_wp_data( int $media_id ): WP {
				return $this->wp_mock;
			}
		};
	}

	/**
	 * Tests that execute() fires the imagify_mcp_ability_executed action.
	 */
	public function testFiresAbilityExecutedHook(): void {
		Actions\expectDone( 'imagify_mcp_ability_executed' )->once();

		// The early-return error path fires the hook without needing WP data mocks.
		( new GetMediaStatus() )->execute( [ 'media_id' => 0 ] );
	}

	// -------------------------------------------------------------------------
	// Scenario (a): invalid / missing media_id
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns error when media_id is 0.
	 */
	public function testReturnsErrorWhenMediaIdIsZero(): void {
		$ability = new GetMediaStatus();
		$result  = $ability->execute( [ 'media_id' => 0 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertStringContainsString( 'media_id', $result['error_message'] );
		$this->assertNull( $result['optimization_level'] );
		$this->assertSame( 0, $result['original_size'] );
		$this->assertSame( 0, $result['optimized_size'] );
		$this->assertFalse( $result['webp_available'] );
		$this->assertFalse( $result['avif_available'] );
	}

	/**
	 * Tests that execute() returns error when media_id is absent from args.
	 */
	public function testReturnsErrorWhenMediaIdAbsent(): void {
		$ability = new GetMediaStatus();
		$result  = $ability->execute( [] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertStringContainsString( 'media_id', $result['error_message'] );
	}

	/**
	 * Tests that execute() returns error when media_id is negative.
	 */
	public function testReturnsErrorWhenMediaIdIsNegative(): void {
		$ability = new GetMediaStatus();
		$result  = $ability->execute( [ 'media_id' => -5 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertStringContainsString( 'media_id', $result['error_message'] );
	}

	/**
	 * Tests that execute() resolves media_filename via MediaResolver.
	 */
	public function testResolvesByFilename(): void {
		$opt_data = [
			'status'  => 'success',
			'message' => '',
			'level'   => 1,
			'sizes'   => [],
			'stats'   => [
				'original_size'  => 1000,
				'optimized_size' => 500,
				'percent'        => 50.0,
			],
		];

		$ability = $this->make_ability( $opt_data, 1000 );
		$this->stubFilenameQuery( [ 42 ] );

		Functions\expect( 'sanitize_file_name' )
			->with( 'hero.jpg' )
			->andReturn( 'hero.jpg' );

		$result = $ability->execute( [ 'media_filename' => 'hero.jpg' ] );

		$this->assertSame( 'success', $result['status'] );
		$this->assertSame( 1, $result['optimization_level'] );
	}

	/**
	 * Tests that execute() returns error when media_filename is ambiguous.
	 */
	public function testReturnsErrorWhenFilenameIsAmbiguous(): void {
		$this->stubFilenameQuery( [ 1, 2, 3 ] );

		Functions\expect( 'sanitize_file_name' )
			->with( 'shared.jpg' )
			->andReturn( 'shared.jpg' );

		$ability = new GetMediaStatus();
		$result  = $ability->execute( [ 'media_filename' => 'shared.jpg' ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertStringContainsString( 'Multiple', $result['error_message'] );
	}

	/**
	 * Tests that execute() returns error when the attachment does not exist.
	 */
	public function testReturnsErrorWhenAttachmentDoesNotExist(): void {
		Functions\when( 'get_post' )->justReturn( false );

		$ability = new GetMediaStatus();
		$result  = $ability->execute( [ 'media_id' => 999 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 'Media not found.', $result['error_message'] );
		$this->assertNull( $result['optimization_level'] );
		$this->assertSame( 0, $result['original_size'] );
		$this->assertSame( 0, $result['optimized_size'] );
		$this->assertFalse( $result['webp_available'] );
		$this->assertFalse( $result['avif_available'] );
	}

	// -------------------------------------------------------------------------
	// Scenario (b): unoptimized attachment
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns the actual filesystem file size for unoptimized media.
	 *
	 * When no optimization data exists, get_original_size(false) reads the file from
	 * disk rather than returning 0 from empty post meta.
	 */
	public function testReturnsUnoptimizedWhenStatusIsEmpty(): void {
		$opt_data = [
			'status'  => '',
			'message' => '',
			'level'   => false,
			'sizes'   => [],
			'stats'   => [
				'original_size'  => 0,
				'optimized_size' => 0,
				'percent'        => 0,
			],
		];

		// Simulate the filesystem fallback returning the real file size.
		$ability = $this->make_ability( $opt_data, 512000 );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'unoptimized', $result['status'] );
		$this->assertNull( $result['optimization_level'] );
		$this->assertSame( 512000, $result['original_size'] );
		$this->assertSame( 0, $result['optimized_size'] );
		$this->assertFalse( $result['webp_available'] );
		$this->assertFalse( $result['avif_available'] );
		$this->assertNull( $result['error_message'] );
	}

	// -------------------------------------------------------------------------
	// Scenario (c): successfully optimized attachment
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns success status when optimized by Imagify.
	 */
	public function testReturnsSuccessWhenStatusIsSuccess(): void {
		$opt_data = [
			'status'  => 'success',
			'message' => '',
			'level'   => 1,
			'sizes'   => [
				'full'                                 => [
					'success'        => true,
					'original_size'  => 200000,
					'optimized_size' => 150000,
					'percent'        => 25.00,
				],
				'full' . ProcessInterface::WEBP_SUFFIX => [
					'success'        => true,
					'original_size'  => 200000,
					'optimized_size' => 120000,
					'percent'        => 40.00,
				],
				'thumbnail'                            => [
					'success'        => true,
					'original_size'  => 50000,
					'optimized_size' => 40000,
					'percent'        => 20.00,
				],
			],
			'stats'   => [
				'original_size'  => 250000,
				'optimized_size' => 190000,
				'percent'        => 24.00,
			],
		];

		// get_original_size(false) reads sizes.full.original_size for optimized media.
		$ability = $this->make_ability( $opt_data, 200000 );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'success', $result['status'] );
		$this->assertSame( 1, $result['optimization_level'] );
		$this->assertSame( 200000, $result['original_size'] );
		$this->assertSame( 190000, $result['optimized_size'] );
		$this->assertTrue( $result['webp_available'] );
		$this->assertFalse( $result['avif_available'] );
		$this->assertNull( $result['error_message'] );
	}

	/**
	 * Tests that 'already_optimized' internal status maps to 'success'.
	 */
	public function testAlreadyOptimizedMapsToSuccess(): void {
		$opt_data = [
			'status'  => 'already_optimized',
			'message' => '',
			'level'   => 0,
			'sizes'   => [],
			'stats'   => [
				'original_size'  => 100000,
				'optimized_size' => 100000,
				'percent'        => 0,
			],
		];

		$ability = $this->make_ability( $opt_data, 100000 );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'success', $result['status'] );
		$this->assertSame( 0, $result['optimization_level'] );
	}

	/**
	 * Tests that both WebP and AVIF availability are detected.
	 */
	public function testDetectsBothWebpAndAvif(): void {
		$opt_data = [
			'status'  => 'success',
			'message' => '',
			'level'   => 2,
			'sizes'   => [
				'full'                                 => [
					'success'        => true,
					'original_size'  => 200000,
					'optimized_size' => 150000,
					'percent'        => 25,
				],
				'full' . ProcessInterface::WEBP_SUFFIX => [
					'success'        => true,
					'original_size'  => 200000,
					'optimized_size' => 120000,
					'percent'        => 40,
				],
				'full' . ProcessInterface::AVIF_SUFFIX => [
					'success'        => true,
					'original_size'  => 200000,
					'optimized_size' => 100000,
					'percent'        => 50,
				],
			],
			'stats'   => [
				'original_size'  => 200000,
				'optimized_size' => 150000,
				'percent'        => 25,
			],
		];

		$ability = $this->make_ability( $opt_data, 200000 );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertTrue( $result['webp_available'] );
		$this->assertTrue( $result['avif_available'] );
	}

	// -------------------------------------------------------------------------
	// Scenario (d): attachment with error
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns error status when internal status is error.
	 */
	public function testReturnsErrorStatusWhenOptimizationFailed(): void {
		$opt_data = [
			'status'  => 'error',
			'message' => 'API quota exceeded',
			'level'   => false,
			'sizes'   => [],
			'stats'   => [
				'original_size'  => 0,
				'optimized_size' => 0,
				'percent'        => 0,
			],
		];

		$ability = $this->make_ability( $opt_data, 0 );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['optimization_level'] );
		$this->assertSame( 'API quota exceeded', $result['error_message'] );
	}

	/**
	 * Tests that error_message is null when status is error but message is empty.
	 */
	public function testErrorMessageIsNullWhenMessageIsEmpty(): void {
		$opt_data = [
			'status'  => 'error',
			'message' => '',
			'level'   => false,
			'sizes'   => [],
			'stats'   => [
				'original_size'  => 0,
				'optimized_size' => 0,
				'percent'        => 0,
			],
		];

		$ability = $this->make_ability( $opt_data, 0 );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['error_message'] );
	}
}
