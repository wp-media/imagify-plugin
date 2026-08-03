<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Optimization\File;

use Brain\Monkey\Functions;
use Imagify\Optimization\File;
use Imagify\Tests\Unit\TestCase;
use Mockery;
use WP_Error;

/**
 * Tests for \Imagify\Optimization\File::optimize() — the wiring between the API `message`
 * guard, the next-gen bytes verification, and the file write destination.
 *
 * `Imagify_Requirements` is a legacy classmap-autoloaded class; it is stubbed via a Mockery
 * alias mock in setUp(), so every test in this class runs in its own process
 * (`@runTestsInSeparateProcesses`) to guarantee the alias is registered before the real class
 * would otherwise be autoloaded.
 *
 * @covers \Imagify\Optimization\File::optimize
 * @covers \Imagify\Optimization\File::is_file_format
 * @group  Optimization
 * @runTestsInSeparateProcesses
 */
class OptimizeTest extends TestCase {

	/**
	 * Path used for the fake source file under test.
	 *
	 * @var string
	 */
	private $path = '/uploads/thumbnail.jpg';

	/**
	 * Path to the (fake) temp file returned by download_url().
	 *
	 * @var string
	 */
	private $temp_file = '/tmp/download-xyz.tmp';

	/**
	 * Stubs the globals needed to reach the code under test.
	 *
	 * @inheritDoc
	 */
	public function setUp(): void {
		parent::setUp();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'do_action_deprecated' )->justReturn( null );
		Functions\when( 'wp_check_filetype' )->justReturn(
			[
				'ext'  => 'jpg',
				'type' => 'image/jpeg',
			]
		);

		Mockery::mock( 'alias:Imagify_Requirements' )
			->shouldReceive( 'is_imagify_blocked' )
			->andReturn( false );
	}

	/**
	 * Builds a File instance with a mocked filesystem injected, bypassing the real
	 * constructor (which requires Imagify_Filesystem::get_instance(), unavailable here).
	 *
	 * @param \Mockery\MockInterface $filesystem Mock for the Imagify_Filesystem dependency.
	 *
	 * @return File
	 */
	private function make_file( $filesystem ): File {
		$file = ( new \ReflectionClass( File::class ) )->newInstanceWithoutConstructor();

		$this->setPropertyValue( 'path', $file, $this->path );
		$this->setPropertyValue( 'filesystem', $file, $filesystem );

		return $file;
	}

	/**
	 * Tests the message-guard / bytes-verification wiring in optimize().
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   Test configuration.
	 * @param array $expected Expected results.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$response = (object) [
			'image'         => 'https://api.imagify.io/dl/xyz',
			'original_size' => 1000,
			'new_size'      => 900,
			'percent'       => 10,
		];

		if ( null !== $config['message'] ) {
			$response->message = $config['message'];
		}

		Functions\when( 'upload_imagify_image' )->justReturn( $response );
		Functions\when( 'download_url' )->justReturn( $this->temp_file );

		$filesystem         = Mockery::mock( \stdClass::class );
		$filesystem->errors = (object) [ 'errors' => [] ];
		$filesystem->shouldReceive( 'exists' )->with( $this->path )->andReturn( true );
		$filesystem->shouldReceive( 'is_file' )->with( $this->path )->andReturn( true );
		$filesystem->shouldReceive( 'is_writable' )->andReturn( true );
		$filesystem->shouldReceive( 'dir_path' )->andReturn( '/uploads/' );
		$filesystem->shouldReceive( 'get_contents' )->with( $this->temp_file )->andReturn( $config['temp_body'] );

		$next_gen_path = $this->path . '.' . $config['convert'];

		if ( $expected['move_called'] ) {
			$destination = 'next_gen' === $expected['destination'] ? $next_gen_path : $this->path;

			$filesystem->shouldReceive( 'move' )
				->once()
				->with( $this->temp_file, $destination, true )
				->andReturn( true );
		} else {
			$filesystem->shouldNotReceive( 'move' );
		}

		if ( $expected['delete_called'] ) {
			$filesystem->shouldReceive( 'delete' )->once()->with( $this->temp_file );
		} else {
			$filesystem->shouldNotReceive( 'delete' );
		}

		$file = $this->make_file( $filesystem );

		$result = $file->optimize(
			[
				'backup'  => false,
				'convert' => $config['convert'],
			]
		);

		if ( 'wp_error' === $expected['result'] ) {
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertSame( $expected['error_code'], $result->get_error_code() );
			// The raw API message must survive verbatim: AbstractProcess::
			// update_size_optimization_data() greps for a substring of it to resolve the
			// `already_optimized` status, which unblocks the self-healing recovery path.
			$this->assertSame( $expected['error_message'], $result->get_error_message() );

			// Neither the next-gen path nor the source file was touched: this is the actual
			// #816 assertion (the bug both failed to create the next-gen file AND overwrote
			// the source with the wrong bytes).
			$this->assertSame( $this->path, $file->get_path() );
		} else {
			$this->assertSame( $response, $result );

			if ( 'next_gen' === $expected['destination'] ) {
				$this->assertSame( $next_gen_path, $file->get_path() );
			} else {
				$this->assertSame( $this->path, $file->get_path() );
			}
		}
	}
}
