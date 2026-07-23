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

		// Act: fire the real WordPress hook as production does.
		do_action( 'imagify_after_optimize', $mock_process, $item );
	}
}
