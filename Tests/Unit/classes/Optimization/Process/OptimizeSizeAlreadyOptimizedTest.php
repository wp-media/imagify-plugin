<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Optimization\Process;

use Brain\Monkey\Functions;
use Imagify\Optimization\Process\WP;
use Imagify\Tests\Unit\TestCase;
use Mockery;
use WP_Error;

/**
 * Tests for \Imagify\Optimization\Process\AbstractProcess::optimize_size() — the
 * "already optimized" bail must not fire for a next-gen size whose file is missing.
 *
 * `Imagify_Filesystem` and `Imagify_Options` are legacy classmap-autoloaded classes; they are
 * stubbed via Mockery alias mocks in the "falls through" case, so every test in this class runs
 * in its own process (`@runTestsInSeparateProcesses`) to guarantee the aliases are registered
 * before the real classes would otherwise be autoloaded.
 *
 * @covers \Imagify\Optimization\Process\AbstractProcess::optimize_size
 * @covers \Imagify\Optimization\Process\AbstractProcess::is_next_gen_file_missing
 * @group  Optimization
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class OptimizeSizeAlreadyOptimizedTest extends TestCase {

	/**
	 * The (next-gen) size name under test. Must use the "webp" suffix: unit tests stub
	 * imagify_nextgen_images_formats() to only return the webp format (see
	 * Tests/Fixtures/inc/functions/nextgen-images-formats.php).
	 *
	 * @var string
	 */
	private $size = 'thumbnail@imagify-webp';

	/**
	 * The non-next-gen size name.
	 *
	 * @var string
	 */
	private $thumb_size = 'thumbnail';

	/**
	 * Path to the non-next-gen version of the size.
	 *
	 * @var string
	 */
	private $original_path = '/uploads/thumbnail.jpg';

	/**
	 * Path to the next-gen version of the size.
	 *
	 * @var string
	 */
	private $next_gen_path = '/uploads/thumbnail.jpg.webp';

	/**
	 * Stubs the globals needed to reach the bail decision under test.
	 *
	 * @inheritDoc
	 */
	public function setUp(): void {
		parent::setUp();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'do_action' )->justReturn( null );

		// Short-circuit `imagify_before_optimize_size` with a WP_Error: this is the earliest
		// point past the bail under test where we can stop the method deterministically,
		// without needing to wire up the rest of the (unrelated) optimization flow. Every
		// other filter is passed through unchanged.
		Functions\when( 'apply_filters' )->alias(
			function () {
				$args = func_get_args();

				if ( 'imagify_before_optimize_size' === $args[0] ) {
					return new WP_Error( 'short_circuited_by_test', 'Short-circuited by test.' );
				}

				return $args[1] ?? null;
			}
		);
	}

	/**
	 * Builds a process instance with mocked collaborators injected, bypassing the real
	 * constructor (which requires a full WordPress environment, unavailable here).
	 *
	 * @param \Mockery\MockInterface $data       Mock for the DataInterface dependency.
	 * @param \Mockery\MockInterface $filesystem Mock for the Imagify_Filesystem dependency.
	 *
	 * @return WP
	 */
	private function make_process( $data, $filesystem ): WP {
		$process = ( new \ReflectionClass( WP::class ) )->newInstanceWithoutConstructor();

		$this->setPropertyValue( 'data', $process, $data );
		$this->setPropertyValue( 'filesystem', $process, $filesystem );

		return $process;
	}

	/**
	 * Tests the bail decision at the top of optimize_size() for a next-gen size whose data
	 * says `success`.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   Test configuration.
	 * @param array $expected Expected results.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$media = Mockery::mock();
		$media->shouldReceive( 'is_valid' )->andReturn( true );
		$media->shouldReceive( 'get_media_files' )->andReturn(
			[
				$this->thumb_size => [ 'path' => $this->original_path ],
			]
		);
		$media->shouldReceive( 'get_allowed_mime_types' )->andReturn( [ 'image/jpeg' ] );

		$data = Mockery::mock();
		$data->shouldReceive( 'get_media' )->andReturn( $media );
		$data->shouldReceive( 'get_size_data' )->with( $this->size, 'success' )->andReturn( true );

		$filesystem = Mockery::mock();
		$filesystem->shouldReceive( 'exists' )
			->with( $this->next_gen_path )
			->andReturn( $config['file_exists'] );

		if ( $expected['bails_out'] ) {
			// If it bails out, the rest of the method (and its collaborators) must never run.
			$data->shouldNotReceive( 'get_size_data' )->with( $this->thumb_size, 'success' );
			$data->shouldNotReceive( 'update_size_optimization_data' );
		} else {
			$data->shouldReceive( 'get_size_data' )->with( $this->thumb_size, 'success' )->andReturn( false );
			$data->shouldReceive( 'update_size_optimization_data' )->once()->andReturnNull();

			Functions\when( 'wp_check_filetype' )->justReturn(
				[
					'ext'  => 'jpg',
					'type' => 'image/jpeg',
				]
			);

			// Falling through reaches `new File( $path )`, whose constructor needs
			// Imagify_Filesystem::get_instance(). The instance is never actually used on
			// this path: it's short-circuited by the `imagify_before_optimize_size` filter
			// before any of its methods would be called.
			Mockery::mock( 'alias:Imagify_Filesystem' )
				->shouldReceive( 'get_instance' )
				->andReturn( Mockery::mock() );

			// sanitize_optimization_level() unconditionally goes through Imagify_Options for
			// a numeric level. Pass the value straight through: it's the level we already
			// provided.
			Mockery::mock( 'alias:Imagify_Options' )
				->shouldReceive( 'get_instance->sanitize_and_validate' )
				->andReturnUsing(
					function ( $key, $value ) {
						return $value;
					}
				);
		}

		$process = $this->make_process( $data, $filesystem );

		$result = $process->optimize_size( $this->size, 0 );

		if ( $expected['bails_out'] ) {
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertSame( 'size_is_successfully_optimized', $result->get_error_code() );
		} else {
			// It fell through: the method reached the (short-circuited) optimization flow and
			// returned the recorded optimization data, not the early bail's WP_Error.
			$this->assertIsArray( $result );
			$this->assertSame( 'error', $result['status'] );
		}
	}
}
