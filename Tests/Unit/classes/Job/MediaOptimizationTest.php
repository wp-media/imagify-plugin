<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Job;

use Brain\Monkey\Functions;
use Imagify\Job\MediaOptimization;
use Imagify\Tests\Unit\TestCase;
use Mockery;
use ReflectionClass;

/**
 * Tests for \Imagify\Job\MediaOptimization::generate_key() and ::get_batches().
 *
 * The instance is built via ReflectionClass::newInstanceWithoutConstructor() to avoid
 * running through the vendored WP_Background_Process/WP_Async_Request constructors
 * (which register WordPress hooks); the protected properties they would have set
 * ($identifier, $allowed_batch_data_classes) are set directly via reflection instead.
 *
 * @covers \Imagify\Job\MediaOptimization::generate_key
 * @covers \Imagify\Job\MediaOptimization::get_batches
 * @covers \Imagify\Job\MediaOptimization::has_priority_item_queued
 * @group  MediaOptimization
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MediaOptimizationTest extends TestCase {

	/**
	 * Build a MediaOptimization instance without running its constructor.
	 *
	 * @return MediaOptimization
	 */
	private function buildInstance(): MediaOptimization {
		Functions\when( 'wp_rand' )->justReturn( 12345 );

		$reflection = new ReflectionClass( MediaOptimization::class );
		$instance   = $reflection->newInstanceWithoutConstructor();

		$identifier = $reflection->getProperty( 'identifier' );
		$identifier->setAccessible( true );
		$identifier->setValue( $instance, 'imagify_optimize_media' );

		$allowed = $reflection->getProperty( 'allowed_batch_data_classes' );
		$allowed->setAccessible( true );
		$allowed->setValue( $instance, true );

		return $instance;
	}

	/**
	 * Call the protected generate_key() method.
	 *
	 * @param MediaOptimization $instance The instance.
	 * @param int               $length   Length arg.
	 * @param string            $key      Key arg.
	 *
	 * @return string
	 */
	private function callGenerateKey( MediaOptimization $instance, int $length = 64, string $key = 'batch' ): string {
		$method = ( new ReflectionClass( $instance ) )->getMethod( 'generate_key' );
		$method->setAccessible( true );

		return $method->invoke( $instance, $length, $key );
	}

	/**
	 * Set the protected, not-yet-persisted $data property (the queued items).
	 *
	 * @param MediaOptimization $instance The instance.
	 * @param array             $data     The queue items.
	 */
	private function setQueuedData( MediaOptimization $instance, array $data ): void {
		$property = ( new ReflectionClass( $instance ) )->getProperty( 'data' );
		$property->setAccessible( true );
		$property->setValue( $instance, $data );
	}

	/**
	 * Test: a batch containing a priority item produces a key using the shared
	 * URGENT_KEY_SEGMENT constant ("batch_urgent"), matching the literal shape used
	 * by get_batches() to order urgent batches first.
	 */
	public function testGenerateKeyProducesUrgentShapeWhenPriorityItemQueued(): void {
		$instance = $this->buildInstance();

		$this->setQueuedData(
			$instance,
			[
				[
					'id'       => 42,
					'priority' => true,
				],
			]
		);

		$key = $this->callGenerateKey( $instance );

		$this->assertMatchesRegularExpression( '/^imagify_optimize_media_batch_urgent_[a-f0-9]+$/', $key );
	}

	/**
	 * Test: a batch with no priority item produces the default (non-urgent) shape,
	 * with no "urgent" segment at all.
	 */
	public function testGenerateKeyProducesDefaultShapeWhenNoPriorityItemQueued(): void {
		$instance = $this->buildInstance();

		$this->setQueuedData(
			$instance,
			[
				[ 'id' => 42 ],
			]
		);

		$key = $this->callGenerateKey( $instance );

		$this->assertMatchesRegularExpression( '/^imagify_optimize_media_batch_[a-f0-9]+$/', $key );
		$this->assertStringNotContainsString( 'urgent', $key );
	}

	/**
	 * Test: an empty queue also produces the default (non-urgent) shape.
	 */
	public function testGenerateKeyProducesDefaultShapeWhenQueueIsEmpty(): void {
		$instance = $this->buildInstance();

		$this->setQueuedData( $instance, [] );

		$key = $this->callGenerateKey( $instance );

		$this->assertMatchesRegularExpression( '/^imagify_optimize_media_batch_[a-f0-9]+$/', $key );
		$this->assertStringNotContainsString( 'urgent', $key );
	}

	/**
	 * Test: generate_key() with a non-'batch' $key (e.g. a status key or any other caller)
	 * is left untouched and never converted to the urgent shape, regardless of queued data.
	 */
	public function testGenerateKeyDoesNotAffectNonBatchKeys(): void {
		$instance = $this->buildInstance();

		$this->setQueuedData(
			$instance,
			[
				[ 'priority' => true ],
			]
		);

		$key = $this->callGenerateKey( $instance, 64, 'something_else' );

		$this->assertMatchesRegularExpression( '/^imagify_optimize_media_something_else_[a-f0-9]+$/', $key );
	}

	/**
	 * Test: get_batches() queries urgent batches ahead of normal ones via a two-tier
	 * ORDER BY built from the same URGENT_KEY_SEGMENT constant used by generate_key(),
	 * and defers to the multisite table set when is_multisite() is true.
	 */
	public function testGetBatchesOrdersUrgentBatchesFirstOnSingleSite(): void {
		Functions\when( 'is_multisite' )->justReturn( false );

		$instance = $this->buildInstance();

		global $wpdb;

		$wpdb          = Mockery::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->options = 'wp_options';

		$wpdb->shouldReceive( 'esc_like' )->andReturnUsing(
			function ( $text ) {
				return $text;
			}
		);

		$wpdb->shouldReceive( 'prepare' )
			->once()
			->withArgs(
				function ( $sql, $args ) {
					return false !== strpos( $sql, 'ORDER BY ( option_name LIKE %s ) DESC, option_id ASC' )
						&& false !== strpos( $sql, 'WHERE option_name LIKE %s' )
						&& 'imagify_optimize_media_batch_%' === $args[0]
						&& 'imagify_optimize_media_batch_urgent_%' === $args[1];
				}
			)
			->andReturn( 'PREPARED_SQL' );

		$wpdb->shouldReceive( 'get_results' )->once()->with( 'PREPARED_SQL' )->andReturn( [] );

		$method = ( new ReflectionClass( $instance ) )->getMethod( 'get_batches' );
		$method->setAccessible( true );

		$result = $method->invoke( $instance );

		$this->assertSame( [], $result );
	}
}
