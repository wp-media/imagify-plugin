<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Bulk;

use Brain\Monkey\Functions;
use Imagify\Bulk\Bulk;
use Imagify\Tests\Unit\TestCase;
use Mockery;
use RuntimeException;

/**
 * Tests for \Imagify\Bulk\Bulk::bulk_stop_callback().
 *
 * @covers \Imagify\Bulk\Bulk::bulk_stop_callback
 *
 * @group  BulkStopCallback
 * @since  2.3
 */
class Test_BulkStopCallback extends TestCase {

	/**
	 * Sets up the test fixture.
	 *
	 * @inheritDoc
	 */
	public function setUp(): void {
		parent::setUp();

		// Only the WP library context, to keep the assertions focused.
		Functions\when( 'is_network_admin' )->justReturn( false );
		Functions\when( 'imagify_can_optimize_custom_folders' )->justReturn( false );
		Functions\when( 'imagify_is_active_for_network' )->justReturn( false );
		// Every request must go through the bulk nonce check first.
		Functions\expect( 'imagify_check_nonce' )
			->once()
			->with( 'imagify-bulk-optimize' )
			->andReturn( true );

		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'as_get_scheduled_actions' )->justReturn( [] );
		Functions\when( 'as_unschedule_all_actions' )->justReturn( null );

		// wp_send_json_*() and imagify_die() never return: they call wp_die().
		Functions\when( 'wp_send_json_success' )->alias(
			static function ( $data = null ): void {
				throw new RuntimeException( 'success:' . wp_json_encode( $data ) );
			}
		);

		Functions\when( 'wp_send_json_error' )->alias(
			static function ( $data = null ): void {
				throw new RuntimeException( 'error:' . wp_json_encode( $data ) );
			}
		);

		Functions\when( 'imagify_die' )->alias(
			static function (): void {
				throw new RuntimeException( 'died' );
			}
		);
	}

	/**
	 * Grants or denies the bulk-optimize capability for every context.
	 *
	 * @param bool $can Whether the current user can bulk-optimize.
	 *
	 * @return void
	 */
	private function stubCapability( bool $can ) {
		$context = Mockery::mock();
		$context->shouldReceive( 'current_user_can' )->with( 'bulk-optimize' )->andReturn( $can );

		Functions\when( 'imagify_get_context' )->justReturn( $context );
	}

	/**
	 * Test: a stopped run answers with the number of cancelled media.
	 */
	public function testShouldReturnCancelledCountOnSuccess(): void {
		$this->stubCapability( true );

		Functions\when( 'get_transient' )->alias(
			static function ( string $transient ) {
				return 'imagify_wp_optimize_running' === $transient
					? [
						'total'     => 5,
						'remaining' => 5,
					]
					: false;
			}
		);

		Functions\when( 'as_get_scheduled_actions' )->justReturn( [ 1, 2, 3 ] );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'success:{"cancelled":6}' );

		( new Bulk() )->bulk_stop_callback();
	}

	/**
	 * Test: nothing running answers with the not-running error message.
	 */
	public function testShouldReturnErrorWhenNothingIsRunning(): void {
		$this->stubCapability( true );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'error:{"message":"not-running"}' );

		( new Bulk() )->bulk_stop_callback();
	}

	/**
	 * Test: a user without the bulk-optimize capability is rejected before anything is cancelled.
	 */
	public function testShouldDieWhenUserCannotBulkOptimize(): void {
		$this->stubCapability( false );

		Functions\expect( 'as_unschedule_all_actions' )->never();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'died' );

		( new Bulk() )->bulk_stop_callback();
	}
}
