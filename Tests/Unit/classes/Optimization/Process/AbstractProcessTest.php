<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Optimization\Process;

use Brain\Monkey\Functions;
use Imagify\Job\MediaOptimization;
use Imagify\Optimization\Data\DataInterface;
use Imagify\Optimization\Process\AbstractProcess;
use Imagify\Media\MediaInterface;
use Imagify\Tests\Unit\TestCase;
use Mockery;
use ReflectionClass;

/**
 * Tests for \Imagify\Optimization\Process\AbstractProcess::optimize_sizes() — the priority
 * flag forwarding introduced for issue #704.
 *
 * `MediaOptimization::get_instance()` is a legacy "not-so-single" instance (InstanceGetterTrait,
 * a static property on the class), so it is swapped out for a Mockery duck-typed stub via
 * reflection on the static `$instance` property, and restored in tearDown().
 *
 * @covers \Imagify\Optimization\Process\AbstractProcess::optimize_sizes
 * @group  AbstractProcess
 * @since  2.4
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AbstractProcessTest extends TestCase {

	/**
	 * Tears down the test fixture: restore MediaOptimization's singleton instance.
	 */
	protected function tearDown(): void {
		$this->resetPropertyValue( 'instance', MediaOptimization::class );
		parent::tearDown();
	}

	/**
	 * Builds a concrete, constructor-bypassed AbstractProcess instance wrapping the given
	 * DataInterface mock, so `is_valid()`/`get_media()`/`get_data()` work without running
	 * through the real constructor (which would touch Imagify_Filesystem and plugin options).
	 *
	 * @param DataInterface $data The (mocked) data instance to wrap.
	 *
	 * @return AbstractProcess
	 */
	private function buildProcess( DataInterface $data ): AbstractProcess {
		$reflection = new ReflectionClass( ConcreteProcessDouble::class );
		$instance   = $reflection->newInstanceWithoutConstructor();

		$property = $reflection->getProperty( 'data' );
		$property->setAccessible( true );
		$property->setValue( $instance, $data );

		return $instance;
	}

	/**
	 * Builds a MediaInterface mock that is valid, supported, and not an image (so the
	 * Next-Gen conversion branch — which would otherwise touch the filesystem — is skipped).
	 *
	 * @return MediaInterface
	 */
	private function buildMedia(): MediaInterface {
		$media = Mockery::mock( MediaInterface::class );
		$media->shouldReceive( 'is_valid' )->andReturn( true );
		$media->shouldReceive( 'is_supported' )->andReturn( true );
		$media->shouldReceive( 'is_image' )->andReturn( false );
		$media->shouldReceive( 'get_id' )->andReturn( 42 );

		return $media;
	}

	/**
	 * Stubs the WordPress/Imagify functions optimize_sizes() calls through on the way to
	 * push_to_queue(), regardless of the priority flag.
	 */
	private function stubCommonFunctions(): void {
		Functions\when( 'apply_filters' )->returnArg( 1 );
		Functions\when( 'get_imagify_option' )->justReturn( 0 );
		Functions\when( '__' )->returnArg( 1 );
	}

	/**
	 * Replaces MediaOptimization::get_instance() with a duck-typed stub whose push_to_queue()
	 * assertion is provided by the caller, and whose save() is a no-op spy.
	 *
	 * @param callable $push_to_queue_assertion Callback receiving the pushed $item array.
	 */
	private function stubMediaOptimizationPush( callable $push_to_queue_assertion ): void {
		$stub = new class( $push_to_queue_assertion ) {
			/**
			 * The assertion callback.
			 *
			 * @var callable
			 */
			private $assertion;

			/**
			 * Constructor.
			 *
			 * @param callable $assertion Callback receiving the pushed $item array.
			 */
			public function __construct( callable $assertion ) {
				$this->assertion = $assertion;
			}

			/**
			 * Duck-typed push_to_queue(): forwards the item to the assertion callback and
			 * returns $this so ->save() can be chained, exactly like the real method.
			 *
			 * @param array $item The queued item.
			 *
			 * @return self
			 */
			public function push_to_queue( $item ) {
				( $this->assertion )( $item );

				return $this;
			}

			/**
			 * Duck-typed save(): a no-op, since the real DB write is irrelevant here.
			 *
			 * @return self
			 */
			public function save() {
				return $this;
			}
		};

		$this->setPropertyValue( 'instance', MediaOptimization::class, $stub );
	}

	/**
	 * Test: when $args['priority'] is truthy, optimize_sizes() pushes an item that carries
	 * 'priority' => true as a sibling key (not nested inside 'data').
	 */
	public function testOptimizeSizesForwardsPriorityAsSiblingKeyWhenTruthy(): void {
		$this->stubCommonFunctions();

		$media = $this->buildMedia();

		$data = Mockery::mock( DataInterface::class );
		$data->shouldReceive( 'get_media' )->andReturn( $media );

		$process = $this->buildProcess( $data );

		$pushed_item = null;

		$this->stubMediaOptimizationPush(
			function ( $item ) use ( &$pushed_item ) {
				$pushed_item = $item;
			}
		);

		$result = $process->optimize_sizes(
			[ 'full' ],
			null,
			[
				'priority' => true,
				'locked'   => true,
			]
		);

		$this->assertTrue( $result );
		$this->assertIsArray( $pushed_item );
		$this->assertArrayHasKey( 'priority', $pushed_item );
		$this->assertTrue( $pushed_item['priority'] );
		$this->assertArrayNotHasKey( 'priority', $pushed_item['data'] );
	}

	/**
	 * Test: when the 'priority' arg is omitted, optimize_sizes() pushes an item with no
	 * 'priority' key at all (not even 'priority' => false).
	 */
	public function testOptimizeSizesDoesNotAddPriorityKeyWhenOmitted(): void {
		$this->stubCommonFunctions();

		$media = $this->buildMedia();

		$data = Mockery::mock( DataInterface::class );
		$data->shouldReceive( 'get_media' )->andReturn( $media );

		$process = $this->buildProcess( $data );

		$pushed_item = null;

		$this->stubMediaOptimizationPush(
			function ( $item ) use ( &$pushed_item ) {
				$pushed_item = $item;
			}
		);

		$result = $process->optimize_sizes( [ 'full' ], null, [ 'locked' => true ] );

		$this->assertTrue( $result );
		$this->assertIsArray( $pushed_item );
		$this->assertArrayNotHasKey( 'priority', $pushed_item );
	}

	/**
	 * Test: an explicitly falsy 'priority' arg (e.g. false) behaves the same as omission: no
	 * 'priority' key is added to the pushed item.
	 */
	public function testOptimizeSizesDoesNotAddPriorityKeyWhenFalsy(): void {
		$this->stubCommonFunctions();

		$media = $this->buildMedia();

		$data = Mockery::mock( DataInterface::class );
		$data->shouldReceive( 'get_media' )->andReturn( $media );

		$process = $this->buildProcess( $data );

		$pushed_item = null;

		$this->stubMediaOptimizationPush(
			function ( $item ) use ( &$pushed_item ) {
				$pushed_item = $item;
			}
		);

		$result = $process->optimize_sizes(
			[ 'full' ],
			null,
			[
				'priority' => false,
				'locked'   => true,
			]
		);

		$this->assertTrue( $result );
		$this->assertIsArray( $pushed_item );
		$this->assertArrayNotHasKey( 'priority', $pushed_item );
	}
}
