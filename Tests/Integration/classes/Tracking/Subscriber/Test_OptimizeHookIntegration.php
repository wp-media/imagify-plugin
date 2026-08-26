<?php
/**
 * Tests for the `imagify_after_optimize` -> Subscriber hook chain.
 *
 * Verifies the Subscriber is registered as an event listener and that firing
 * the hook triggers its track_media_optimized method. Uses a mock Tracking
 * object swapped into the live Subscriber to prove the chain runs end-to-end.
 *
 * @package  Imagify\Tests\Integration
 * @category Test
 */

namespace Imagify\Tests\Integration\classes\Tracking\Subscriber;

use Imagify\Job\MediaOptimization;
use Imagify\Optimization\Process\ProcessInterface;
use Imagify\Tracking\Subscriber;
use Imagify\Tracking\Tracking;
use Imagify\Tests\Integration\TestCase;

defined( 'ABSPATH' ) || exit;

/**
 * Tests the full integration path for imagify_after_optimize.
 */
class Test_OptimizeHookIntegration extends TestCase {

	/**
	 * Whether to use the Imagify API in these tests.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * PHPUnit mock of the Tracking service injected into the live Subscriber.
	 *
	 * Used to verify that the Subscriber delegates to Tracking::track_media_optimized()
	 * when imagify_after_optimize fires.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject|Tracking
	 */
	private $mock_tracking;

	/**
	 * Set up integration test fixtures.
	 *
	 * Resolve the real Subscriber from the DI container and inject a mock Tracking
	 * instance via reflection. Mocking Tracking keeps the Subscriber real while
	 * allowing us to assert end-to-end delegation without touching Mixpanel.
	 *
	 * @return void
	 */
	public function set_up(): void { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCase
		parent::set_up();

		$container = apply_filters( 'imagify_container', null );

		$this->assertNotNull( $container, 'Imagify container must be available.' );

		// Resolve the real Subscriber instance from the DI container.
		$subscriber = $container->get( Subscriber::class );

		$this->assertInstanceOf( Subscriber::class, $subscriber );

		// Create a PHPUnit mock. Real methods on Tracking are not called; we only assert delegation.
		$this->mock_tracking = $this->createMock( Tracking::class );

		// Inject mock into the real Subscriber's private $tracking property.
		$tracking_property = new \ReflectionProperty( Subscriber::class, 'tracking' );
		$tracking_property->setAccessible( true );
		$tracking_property->setValue( $subscriber, $this->mock_tracking );
	}

	/**
	 * Test that firing imagify_after_optimize invokes the Subscriber callback.
	 *
	 * Verifies the full integration path:
	 * Plugin boots -> ServiceProvider registers Subscriber -> EventManager adds
	 * filter -> do_action fires -> Subscriber::track_media_optimized() runs.
	 *
	 * The Subscriber is real. The injected mock captures the delegation step.
	 * If the hook isn't wired, the mock expectation never fires and the test fails.
	 *
	 * @return void
	 */
	public function testFiringImagifyAfterOptimizeCallsTrackMediaOptimized(): void {
		// Build a minimal ProcessInterface mock. Media stub keeps sibling hook listeners from getting a null check fatal.
		$media_mock   = $this->createMock( \Imagify\Media\MediaInterface::class );
		$mock_process = $this->createMock( ProcessInterface::class );
		$mock_process->method( 'get_media' )->willReturn( $media_mock );

		$item = [ 'sizes_done' => [ 'full' ] ];

		// Register expectation before the hook fires.
		$this->mock_tracking->expects( $this->once() )
			->method( 'track_media_optimized' )
			->with( $mock_process, $item );

		// Act: fire the hook directly. This covers the LISTENER side only; the
		// producer side is covered by the task_after() tests below.
		do_action( 'imagify_after_optimize', $mock_process, $item );
	}

	/**
	 * Build a MediaOptimization job with a mock process injected.
	 *
	 * The validate_item() path needs a real attachment plus a resolvable process
	 * class, which is far more setup than the producer contract needs. Injecting the
	 * process and
	 * invoking task_after() directly exercises the real production code that fires
	 * the hook, which is the part a regression would break.
	 *
	 * @param  ProcessInterface $process The process to inject.
	 * @return MediaOptimization
	 */
	private function job_with_process( $process ): MediaOptimization {
		$job = MediaOptimization::get_instance();

		$property = new \ReflectionProperty( MediaOptimization::class, 'optimization_process' );
		$property->setAccessible( true );
		$property->setValue( $job, $process );

		return $job;
	}

	/**
	 * Invoke the private MediaOptimization::task_after().
	 *
	 * @param  MediaOptimization $job  The job.
	 * @param  array             $item The item.
	 * @return array
	 */
	private function run_task_after( MediaOptimization $job, array $item ): array {
		$method = new \ReflectionMethod( MediaOptimization::class, 'task_after' );
		$method->setAccessible( true );

		return $method->invoke( $job, $item );
	}

	/**
	 * Build a process mock whose media is also mocked.
	 *
	 * The media stub keeps sibling listeners on the hook from fataling on a null media.
	 *
	 * @return ProcessInterface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function process_mock() {
		$media_mock = $this->createMock( \Imagify\Media\MediaInterface::class );
		$process    = $this->createMock( ProcessInterface::class );

		$process->method( 'get_media' )->willReturn( $media_mock );

		return $process;
	}

	/**
	 * The PRODUCER contract: task_after() must fire imagify_after_optimize.
	 *
	 * This is the test with teeth. The listener test above passes even if the
	 * do_action() call in MediaOptimization::task_after() is deleted, because it
	 * fires the hook itself. This one drives the real production method, so
	 * removing or renaming that call makes it fail.
	 *
	 * @return void
	 */
	public function testTaskAfterFiresImagifyAfterOptimizeWithProcessAndItem(): void {
		$process = $this->process_mock();
		$job     = $this->job_with_process( $process );
		$item    = [
			'sizes_done' => [ 'full' ],
			'data'       => [],
		];

		$fired = [];

		add_action(
			'imagify_after_optimize',
			function ( $received_process, $received_item ) use ( &$fired ) {
				$fired[] = [ $received_process, $received_item ];
			},
			10,
			2
		);

		$this->run_task_after( $job, $item );

		$this->assertCount( 1, $fired, 'task_after() must fire imagify_after_optimize exactly once.' );
		$this->assertSame( $process, $fired[0][0], 'The optimization process must be passed as the first argument.' );
		$this->assertSame( $item, $fired[0][1], 'The item must be passed as the second argument, unmodified.' );
	}

	/**
	 * The PRODUCER contract for the dynamic hook: imagify_after_{hook_suffix}.
	 *
	 * Third-party integrations hook the suffixed variant, so it is part of the
	 * public contract and needs the same guard.
	 *
	 * @return void
	 */
	public function testTaskAfterFiresTheSuffixedHookWhenHookSuffixIsSet(): void {
		$process = $this->process_mock();
		$job     = $this->job_with_process( $process );
		$item    = [
			'sizes_done' => [ 'full' ],
			'data'       => [ 'hook_suffix' => 'optimize_media' ],
		];

		$fired = [];

		add_action(
			'imagify_after_optimize_media',
			function ( $received_process, $received_item ) use ( &$fired ) {
				$fired[] = [ $received_process, $received_item ];
			},
			10,
			2
		);

		$this->run_task_after( $job, $item );

		$this->assertCount( 1, $fired, 'task_after() must fire the suffixed hook exactly once.' );
		$this->assertSame( $process, $fired[0][0], 'The optimization process must be passed as the first argument.' );
	}
}
