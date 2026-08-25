<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\BulkOptimize;

use Brain\Monkey\Functions;
use Imagify\Abilities\BulkOptimize;
use Imagify\Bulk\BulkOptimizerInterface;
use Imagify\Tests\Unit\TestCase;
use Mockery;
use WP_Error;

/**
 * Tests for \Imagify\Abilities\BulkOptimize::execute().
 *
 * `Imagify_Requirements` is a legacy classmap-autoloaded class; it is stubbed via a
 * Mockery alias mock in every test, so every test in this class runs in its own
 * process (`@runTestsInSeparateProcesses`) to guarantee the alias is registered
 * before the real class would otherwise be autoloaded.
 *
 * @covers \Imagify\Abilities\BulkOptimize::execute
 * @group  BulkOptimize
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Test_Execute extends TestCase {

	/**
	 * Stubs `Imagify_Requirements::is_api_key_valid()` / `is_over_quota()` via a Mockery
	 * alias mock so guard_credit_confirmation() lets execution reach do_execute()
	 * (or, for the insufficient_quota case, stops it there).
	 *
	 * @param bool $api_key_valid Value returned by is_api_key_valid().
	 * @param bool $over_quota    Value returned by is_over_quota().
	 */
	private function stubRequirements( bool $api_key_valid, bool $over_quota ): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'imagify_get_external_url' )->justReturn( 'https://example.com/subscription' );
		// fetch_user()'s User::init_user() calls get_imagify_user(); avoid a real API/transient call.
		Functions\when( 'get_imagify_user' )->justReturn( new WP_Error( 'no_api_key', 'No API key.' ) );

		$mock = Mockery::mock( 'alias:Imagify_Requirements' );
		$mock->shouldReceive( 'is_api_key_valid' )->andReturn( $api_key_valid );
		$mock->shouldReceive( 'is_over_quota' )->andReturn( $over_quota );
	}

	/**
	 * Tests that execute() returns a confirmation_required response when confirm is not passed,
	 * using get_impact_estimate()'s wp-context count.
	 */
	public function testReturnsConfirmationRequiredWhenConfirmIsNotPassed(): void {
		$this->stubRequirements( true, false );

		Functions\when( 'imagify_count_unoptimized_attachments' )->justReturn( 3 );
		Functions\when( 'imagify_count_attachments' )->justReturn( 10 );

		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldNotReceive( 'run_optimize' );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute( [ 'context' => 'wp' ] );

		$this->assertSame( 'confirmation_required', $result['status'] );
		$this->assertSame( 3, $result['impact']['count'] );
		$this->assertSame( 10, $result['impact']['total'] );
	}

	/**
	 * Tests that execute() returns insufficient_quota (and never reaches do_execute()) when
	 * confirm: true is passed but the account is over quota.
	 */
	public function testReturnsInsufficientQuotaWhenConfirmedButOverQuota(): void {
		$this->stubRequirements( true, true );

		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldNotReceive( 'run_optimize' );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute(
			[
				'context' => 'wp',
				'confirm' => true,
			]
		);

		$this->assertSame( 'insufficient_quota', $result['status'] );
	}

	/**
	 * Finding 2 regression guard: get_impact_estimate() must use the cheap COUNT helpers per
	 * context, never `Bulk::get_context_data()` (which has no remaining/total figures) and
	 * never `get_unoptimized_media_ids()` (a heavy ID+metadata JOIN query).
	 */
	public function testGetImpactEstimateUsesWpCountHelpersForWpContext(): void {
		Functions\stubTranslationFunctions();
		Functions\expect( 'imagify_count_unoptimized_attachments' )->once()->andReturn( 4 );
		Functions\expect( 'imagify_count_attachments' )->once()->andReturn( 20 );

		$ability = new BulkOptimize( Mockery::mock( BulkOptimizerInterface::class ) );
		$impact  = $ability->get_impact_estimate( [ 'context' => 'wp' ] );

		$this->assertSame( 'image', $impact['unit'] );
		$this->assertSame( 4, $impact['count'] );
		$this->assertSame( 20, $impact['total'] );
		$this->assertSame( 'unoptimized images in the WordPress media library', $impact['label'] );
	}

	/**
	 * Regression guard for #1186: an unrecognized context value must fall back to the
	 * 'wp' counts AND the matching 'wp' label (not a mismatched raw-slug label).
	 */
	public function testGetImpactEstimateFallsBackToWpCountsAndLabelForUnrecognizedContext(): void {
		Functions\stubTranslationFunctions();
		Functions\expect( 'imagify_count_unoptimized_attachments' )->once()->andReturn( 4 );
		Functions\expect( 'imagify_count_attachments' )->once()->andReturn( 20 );

		$ability = new BulkOptimize( Mockery::mock( BulkOptimizerInterface::class ) );
		$impact  = $ability->get_impact_estimate( [ 'context' => 'banana' ] );

		$this->assertSame( 4, $impact['count'] );
		$this->assertSame( 20, $impact['total'] );
		$this->assertSame( 'unoptimized images in the WordPress media library', $impact['label'] );
	}

	/**
	 * Regression guard for #1186: a missing context key must also fall back to the
	 * 'wp' counts AND label.
	 */
	public function testGetImpactEstimateFallsBackToWpCountsAndLabelForMissingContext(): void {
		Functions\stubTranslationFunctions();
		Functions\expect( 'imagify_count_unoptimized_attachments' )->once()->andReturn( 4 );
		Functions\expect( 'imagify_count_attachments' )->once()->andReturn( 20 );

		$ability = new BulkOptimize( Mockery::mock( BulkOptimizerInterface::class ) );
		$impact  = $ability->get_impact_estimate( [] );

		$this->assertSame( 4, $impact['count'] );
		$this->assertSame( 20, $impact['total'] );
		$this->assertSame( 'unoptimized images in the WordPress media library', $impact['label'] );
	}

	/**
	 * Finding 2 regression guard: custom-folders context uses Imagify_Files_Stats' cheap
	 * COUNT-only static methods, not Bulk::get_context_data().
	 */
	public function testGetImpactEstimateUsesFilesStatsCountHelpersForCustomFoldersContext(): void {
		Functions\stubTranslationFunctions();
		$mock = Mockery::mock( 'alias:Imagify_Files_Stats' );
		$mock->shouldReceive( 'count_unoptimized_files' )->once()->andReturn( 6 );
		$mock->shouldReceive( 'count_files' )->once()->andReturn( 30 );

		$ability = new BulkOptimize( Mockery::mock( BulkOptimizerInterface::class ) );
		$impact  = $ability->get_impact_estimate( [ 'context' => 'custom-folders' ] );

		$this->assertSame( 'image', $impact['unit'] );
		$this->assertSame( 6, $impact['count'] );
		$this->assertSame( 30, $impact['total'] );
		$this->assertSame( 'unoptimized images in custom folders', $impact['label'] );
	}

	/**
	 * Tests that execute() returns scheduled when context is valid and run_optimize succeeds
	 * (confirmed, quota OK) — regression: guard does not alter the existing success shape.
	 */
	public function testReturnsScheduledWhenContextIsValidAndRunSucceeds(): void {
		$this->stubRequirements( true, false );

		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldReceive( 'run_optimize' )
			->once()
			->with( 'wp', \Mockery::type( 'int' ) )
			->andReturn(
				[
					'success' => true,
					'message' => 'success',
				]
			);

		Functions\when( 'get_imagify_option' )->justReturn( 1 );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute(
			[
				'context' => 'wp',
				'confirm' => true,
			]
		);

		$this->assertSame( 'scheduled', $result['status'] );
		$this->assertSame( 'wp', $result['context'] );
		$this->assertNull( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error when context is invalid (confirmed, quota OK).
	 */
	public function testReturnsErrorWhenContextIsInvalid(): void {
		$this->stubRequirements( true, false );

		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldNotReceive( 'run_optimize' );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute(
			[
				'context' => 'invalid',
				'confirm' => true,
			]
		);

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 'invalid', $result['context'] );
		$this->assertIsString( $result['error_message'] );
		$this->assertNotEmpty( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error when context is empty string (confirmed, quota OK).
	 */
	public function testReturnsErrorWhenContextIsEmpty(): void {
		$this->stubRequirements( true, false );

		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldNotReceive( 'run_optimize' );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute(
			[
				'context' => '',
				'confirm' => true,
			]
		);

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( '', $result['context'] );
		$this->assertIsString( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error when run_optimize fails with over-quota
	 * (confirmed, quota OK at guard time — mid-execution failure is unrelated to the guard).
	 */
	public function testReturnsErrorWhenRunOptimizeFailsWithOverQuota(): void {
		$this->stubRequirements( true, false );

		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldReceive( 'run_optimize' )
			->once()
			->andReturn(
				[
					'success' => false,
					'message' => 'over-quota',
				]
			);

		Functions\when( 'get_imagify_option' )->justReturn( 0 );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute(
			[
				'context' => 'wp',
				'confirm' => true,
			]
		);

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 'over-quota', $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error when run_optimize fails with no-images (confirmed, quota OK).
	 */
	public function testReturnsErrorWhenRunOptimizeFailsWithNoImages(): void {
		$this->stubRequirements( true, false );

		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldReceive( 'run_optimize' )
			->once()
			->andReturn(
				[
					'success' => false,
					'message' => 'no-images',
				]
			);

		Functions\when( 'get_imagify_option' )->justReturn( 0 );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute(
			[
				'context' => 'wp',
				'confirm' => true,
			]
		);

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 'no-images', $result['error_message'] );
	}

	/**
	 * Tests that execute() uses the global option when optimization_level is not provided
	 * (confirmed, quota OK).
	 */
	public function testUsesGlobalOptionWhenOptimizationLevelNotProvided(): void {
		$this->stubRequirements( true, false );

		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldReceive( 'run_optimize' )
			->once()
			->with( 'wp', 1 )
			->andReturn(
				[
					'success' => true,
					'message' => 'success',
				]
			);

		Functions\expect( 'get_imagify_option' )
			->once()
			->with( 'optimization_level' )
			->andReturn( 1 );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute(
			[
				'context' => 'wp',
				'confirm' => true,
			]
		);

		$this->assertSame( 'scheduled', $result['status'] );
	}

	/**
	 * Tests that execute() uses the provided optimization_level (confirmed, quota OK).
	 */
	public function testUsesProvidedOptimizationLevel(): void {
		$this->stubRequirements( true, false );

		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldReceive( 'run_optimize' )
			->once()
			->with( 'wp', 2 )
			->andReturn(
				[
					'success' => true,
					'message' => 'success',
				]
			);

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute(
			[
				'context'            => 'wp',
				'optimization_level' => 2,
				'confirm'            => true,
			]
		);

		$this->assertSame( 'scheduled', $result['status'] );
	}

	/**
	 * Tests that execute() clamps optimization_level above 2 to 2 (confirmed, quota OK).
	 */
	public function testClampsOptimizationLevelAbove2(): void {
		$this->stubRequirements( true, false );

		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldReceive( 'run_optimize' )
			->once()
			->with( 'wp', 2 )
			->andReturn(
				[
					'success' => true,
					'message' => 'success',
				]
			);

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute(
			[
				'context'            => 'wp',
				'optimization_level' => 5,
				'confirm'            => true,
			]
		);

		$this->assertSame( 'scheduled', $result['status'] );
	}

	/**
	 * Tests that execute() clamps optimization_level below 0 to 0 (confirmed, quota OK).
	 */
	public function testClampsOptimizationLevelBelow0(): void {
		$this->stubRequirements( true, false );

		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldReceive( 'run_optimize' )
			->once()
			->with( 'wp', 0 )
			->andReturn(
				[
					'success' => true,
					'message' => 'success',
				]
			);

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute(
			[
				'context'            => 'wp',
				'optimization_level' => -1,
				'confirm'            => true,
			]
		);

		$this->assertSame( 'scheduled', $result['status'] );
	}
}
