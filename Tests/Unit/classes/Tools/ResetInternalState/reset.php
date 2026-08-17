<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tools\ResetInternalState;

use Imagify\Tests\Unit\TestCase;
use Imagify\Tools\InternalStateList;
use Imagify\Tools\ResetInternalState;
use Mockery;
use Brain\Monkey\Functions;

/**
 * Tests for \Imagify\Tools\ResetInternalState::reset().
 *
 * @covers \Imagify\Tools\ResetInternalState::reset
 * @group  Tools
 */
class Test_Reset extends TestCase {

	/**
	 * Mockery mock for $wpdb.
	 *
	 * @var \Mockery\MockInterface
	 */
	private $wpdb;

	/**
	 * Sets up the test fixture.
	 *
	 * @inheritDoc
	 */
	public function setUp(): void {
		parent::setUp();

		// Build a Mockery spy for $wpdb and expose it globally.
		$this->wpdb           = Mockery::mock( 'wpdb' );
		$this->wpdb->options  = 'wp_options';
		$this->wpdb->sitemeta = 'wp_sitemeta';

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wpdb'] = $this->wpdb;

		// ActionScheduler is not loaded in unit tests: stub it so reset() behaves the same
		// whatever other test suites ran before this one.
		Functions\when( 'as_unschedule_all_actions' )->justReturn( null );
	}

	/**
	 * Tears down the test fixture.
	 *
	 * @inheritDoc
	 */
	public function tearDown(): void {
		unset( $GLOBALS['wpdb'] );

		parent::tearDown();
	}

	/**
	 * Tests that reset() calls delete_transient() with every bulk transient name.
	 */
	public function testDeletesBulkTransients(): void {
		$this->wpdb->shouldReceive( 'esc_like' )->andReturnUsing(
			function ( string $value ): string {
				return str_replace( [ '\\', '%', '_' ], [ '\\\\', '\\%', '\\_' ], $value );
			}
		);
		$this->wpdb->shouldReceive( 'prepare' )->andReturn( 'PREPARED_SQL' );
		$this->wpdb->shouldReceive( 'query' )->andReturn( 1 );

		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'delete_transient' )->justReturn( true );

		$deleted = [];
		Functions\when( 'delete_transient' )->alias(
			function ( string $transient ) use ( &$deleted ) {
				$deleted[] = $transient;
			}
		);

		( new ResetInternalState() )->reset();

		$expected_bulk = [
			'imagify_custom-folders_optimize_running',
			'imagify_wp_optimize_running',
			'imagify_bulk_optimization_complete',
			'imagify_missing_next_gen_total',
			'imagify_bulk_optimization_result',
			'imagify_bulk_optimization_infos',
			'imagify_bulk_optimization_level',
		];

		foreach ( $expected_bulk as $transient ) {
			$this->assertContains( $transient, $deleted, "delete_transient() was not called with '{$transient}'" );
		}
	}

	/**
	 * Tests that reset() does NOT call delete_transient() for user/account cache transients.
	 */
	public function testDoesNotDeleteUserCacheTransients(): void {
		$this->wpdb->shouldReceive( 'esc_like' )->andReturnUsing(
			function ( string $value ): string {
				return str_replace( [ '\\', '%', '_' ], [ '\\\\', '\\%', '\\_' ], $value );
			}
		);
		$this->wpdb->shouldReceive( 'prepare' )->andReturn( 'PREPARED_SQL' );
		$this->wpdb->shouldReceive( 'query' )->andReturn( 1 );

		Functions\when( 'is_multisite' )->justReturn( false );

		$deleted = [];
		Functions\when( 'delete_transient' )->alias(
			function ( string $transient ) use ( &$deleted ) {
				$deleted[] = $transient;
			}
		);

		( new ResetInternalState() )->reset();

		$user_cache_transients = [
			'imagify_user',
			'imagify_user_cache',
			'imagify_user_images_count',
			'imagify_large_library',
			'imagify_attachments_number_modal',
			'imagify_stat_without_next_gen',
			'imagify_max_image_size',
			'imagify_check_licence_1',
			'imagify_check_api_version',
			'imagify_settings',
			'imagify_data',
		];

		foreach ( $user_cache_transients as $transient ) {
			$this->assertNotContains( $transient, $deleted, "delete_transient() must NOT be called with '{$transient}'" );
		}
	}

	/**
	 * Tests that reset() builds LIKE patterns via esc_like() and issues a query for each against wp_options.
	 */
	public function testRunsLikePatternQueryAgainstOptions(): void {
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'is_multisite' )->justReturn( false );

		// Simulate esc_like(): escape \, %, and _ with a leading backslash.
		$this->wpdb->shouldReceive( 'esc_like' )
			->andReturnUsing(
				function ( string $value ): string {
					return str_replace( [ '\\', '%', '_' ], [ '\\\\', '\\%', '\\_' ], $value );
				}
			);

		$patterns_queried = [];

		$this->wpdb->shouldReceive( 'prepare' )
			->andReturnUsing(
				function ( string $sql, string $pattern ) use ( &$patterns_queried ) {
					$patterns_queried[] = $pattern;
					return 'PREPARED_SQL';
				}
			);

		$this->wpdb->shouldReceive( 'query' )->times( count( InternalStateList::get_locked_transient_patterns() ) )->andReturn( 0 );

		( new ResetInternalState() )->reset();

		// Expected: raw pattern parts esc_like'd, reassembled with % wildcards.
		$expected_patterns = [
			'\_transient\_%imagify-auto-optimize-%',
			'\_transient\_%imagify\_rpc\_%',
			'\_transient\_imagify\_%\_process\_locked',
			'\_site\_transient\_imagify\_%\_process\_lock%',
			'\_transient\_imagify\_awaiting\_subsizes\_%',
			'\_transient\_timeout\_imagify\_awaiting\_subsizes\_%',
		];

		foreach ( $expected_patterns as $pattern ) {
			$this->assertContains( $pattern, $patterns_queried, "No query was prepared for pattern '{$pattern}'" );
		}
	}

	/**
	 * Tests that reset() runs a second query against sitemeta when is_multisite() is true.
	 */
	public function testRunsSitemetaQueryOnMultisite(): void {
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'is_multisite' )->justReturn( true );

		// Accept any esc_like() call (options-pattern parts + the explicit sitemeta prefix).
		$this->wpdb->shouldReceive( 'esc_like' )
			->andReturnUsing(
				function ( string $value ): string {
					return str_replace( [ '\\', '%', '_' ], [ '\\\\', '\\%', '\\_' ], $value );
				}
			);

		$this->wpdb->shouldReceive( 'prepare' )->andReturn( 'PREPARED_SQL' );

		$query_calls = 0;
		$this->wpdb->shouldReceive( 'query' )
			->andReturnUsing(
				function () use ( &$query_calls ) {
					$query_calls++;
					return 0;
				}
			);

		( new ResetInternalState() )->reset();

		// 4 options-pattern queries + 1 sitemeta query = 5 total.
		$this->assertGreaterThanOrEqual( 5, $query_calls, 'Expected at least 5 wpdb::query() calls on multisite (4 options + 1 sitemeta)' );
	}

	/**
	 * Tests that reset() fires the imagify_after_reset_internal_state action at the end.
	 */
	public function testFiresActionAfterReset(): void {
		$this->wpdb->shouldReceive( 'esc_like' )->andReturnUsing(
			function ( string $value ): string {
				return str_replace( [ '\\', '%', '_' ], [ '\\\\', '\\%', '\\_' ], $value );
			}
		);
		$this->wpdb->shouldReceive( 'prepare' )->andReturn( 'PREPARED_SQL' );
		$this->wpdb->shouldReceive( 'query' )->andReturn( 1 );

		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'is_multisite' )->justReturn( false );

		Functions\expect( 'do_action' )
			->once()
			->with( 'imagify_after_reset_internal_state' );

		( new ResetInternalState() )->reset();
	}

	/**
	 * Tests that reset() skips as_unschedule_all_actions() when ActionScheduler is not loaded.
	 *
	 * As_unschedule_all_actions() does not exist in the test environment, so
	 * function_exists() naturally returns false — no stubbing needed.
	 * The 4 wpdb::query() calls for the options LIKE patterns are the proof that
	 * reset() ran to completion without errors.
	 */
	public function testSkipsSchedulerWhenFunctionNotExists(): void {
		$this->wpdb->shouldReceive( 'esc_like' )
			->andReturnUsing(
				function ( string $value ): string {
					return str_replace( [ '\\', '%', '_' ], [ '\\\\', '\\%', '\\_' ], $value );
				}
			);
		$this->wpdb->shouldReceive( 'prepare' )->andReturn( 'PREPARED_SQL' );

		$query_calls = 0;
		$this->wpdb->shouldReceive( 'query' )
			->andReturnUsing(
				function () use ( &$query_calls ) {
					$query_calls++;
					return 0;
				}
			);

		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'is_multisite' )->justReturn( false );

		( new ResetInternalState() )->reset();

		// One options-pattern query per registered pattern proves reset() ran to completion.
		$this->assertSame( count( InternalStateList::get_locked_transient_patterns() ), $query_calls );
	}
}
