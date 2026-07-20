<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GenerateMissingNextgen;

use Brain\Monkey\Functions;
use Imagify\Abilities\GenerateMissingNextgen;
use Imagify\Bulk\Bulk;
use Imagify\Stats\StatInterface;
use Imagify\Tests\Unit\TestCase;
use Mockery;
use WP_Error;

/**
 * Tests for \Imagify\Abilities\GenerateMissingNextgen::execute().
 *
 * Covers all five contract-translation branches defined in the spec, plus the
 * credit-confirmation guard cases added by this issue.
 *
 * `imagify_nextgen_images_formats()` is provided by the unit test fixture at
 * Tests/Fixtures/inc/functions/nextgen-images-formats.php and returns
 * `['webp' => 'webp']` — no stubbing required.
 *
 * Because `Bulk` is a `final` class, Mockery cannot mock it. We construct the
 * ability with a real Bulk instance, then replace the private `$bulk` property
 * via reflection with a lightweight anonymous stub that controls the output.
 * The `$bulk` property is an instance property so `setPropertyValue` uses the
 * two-argument form of `ReflectionProperty::setValue()`, avoiding the PHP 8.4
 * single-argument deprecation that affects static-property resets.
 *
 * `Imagify_Requirements` is a legacy classmap-autoloaded class; it is stubbed via
 * a Mockery alias mock in every test that reaches the guard, so every test in
 * this class runs in its own process (`@runTestsInSeparateProcesses`) to
 * guarantee the alias is registered before the real class would otherwise be
 * autoloaded.
 *
 * @covers \Imagify\Abilities\GenerateMissingNextgen::execute
 * @group  MCP
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Test_Execute extends TestCase {

	/**
	 * Returns a GenerateMissingNextgen instance whose internal `$bulk` property
	 * has been replaced by an anonymous stub that returns `$bulk_result` from
	 * `run_generate_nextgen()` and `['wp']` from `get_contexts()`.
	 *
	 * @param array              $bulk_result Value returned by the stubbed run_generate_nextgen().
	 * @param StatInterface|null $stat        Stat service; a permissive mock (0) by default.
	 * @return GenerateMissingNextgen
	 */
	private function make_ability( array $bulk_result, ?StatInterface $stat = null ): GenerateMissingNextgen {
		if ( null === $stat ) {
			$stat = Mockery::mock( StatInterface::class );
			$stat->shouldReceive( 'get_stat' )->andReturn( 0 );
		}

		// Construct with a real Bulk instance to satisfy the type hint.
		$ability = new GenerateMissingNextgen( Bulk::get_instance(), $stat );

		// Replace the injected Bulk with a lightweight stub via reflection.
		// Uses the two-argument ReflectionProperty::setValue( $object, $value ) form,
		// which does NOT trigger the PHP 8.4 single-argument deprecation.
		$stub = new class( $bulk_result ) {
			/**
			 * Fixed value returned by run_generate_nextgen().
			 *
			 * @var array
			 */
			private $result;

			/**
			 * Constructor.
			 *
			 * @param array $result Value returned by the stubbed run_generate_nextgen().
			 */
			public function __construct( array $result ) {
				$this->result = $result;
			}

			/**
			 * Returns a fixed single-context list.
			 *
			 * @return array
			 */
			public function get_contexts(): array {
				return [ 'wp' ];
			}

			/**
			 * Returns the fixed result passed to the constructor.
			 *
			 * @param array $contexts Contexts to generate next-gen versions for (unused by this stub).
			 * @param array $formats  Next-gen formats to generate (unused by this stub).
			 * @return array
			 */
			public function run_generate_nextgen( array $contexts, array $formats ): array {
				return $this->result;
			}
		};

		$this->setPropertyValue( 'bulk', $ability, $stub );

		return $ability;
	}

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
	 * Finding 3 regression guard: execute() accepts the new `array $args = []` signature and
	 * plumbs `confirm` through to the guard (previously execute() took zero parameters).
	 */
	public function testExecuteAcceptsArgsWithConfirmFlag(): void {
		$this->stubRequirements( true, false );

		$result = $this->make_ability(
			[
				'success' => true,
				'message' => 5,
			]
		)->execute( [ 'confirm' => true ] );

		$this->assertSame( 'scheduled', $result['status'] );
		$this->assertSame( 5, $result['queued_count'] );
	}

	/**
	 * Tests that execute() returns a confirmation_required response when confirm is not passed.
	 */
	public function testReturnsConfirmationRequiredWhenConfirmIsNotPassed(): void {
		$this->stubRequirements( true, false );
		Functions\when( 'imagify_count_optimized_attachments' )->justReturn( 0 );

		$mock = Mockery::mock( 'alias:Imagify_Files_Stats' );
		$mock->shouldReceive( 'count_optimized_files' )->andReturn( 0 );

		$result = $this->make_ability(
			[
				'success' => true,
				'message' => 5,
			]
		)->execute();

		$this->assertSame( 'confirmation_required', $result['status'] );
	}

	/**
	 * Tests that execute() returns insufficient_quota (and never reaches do_execute()) when
	 * confirm: true is passed but the account is over quota.
	 */
	public function testReturnsInsufficientQuotaWhenConfirmedButOverQuota(): void {
		$this->stubRequirements( true, true );

		$result = $this->make_ability(
			[
				'success' => true,
				'message' => 5,
			]
		)->execute( [ 'confirm' => true ] );

		$this->assertSame( 'insufficient_quota', $result['status'] );
	}

	/**
	 * Finding 1 regression guard: get_impact_estimate()['total'] equals
	 * imagify_count_optimized_attachments() + Imagify_Files_Stats::count_optimized_files(),
	 * and 'count' comes from the injected StatInterface's live stat (not the 2-day cached stat),
	 * so the preview stays consistent with 'total' — regression guard for #1186.
	 */
	public function testGetImpactEstimateTotalSumsBothContextsOptimizedCounts(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'imagify_count_optimized_attachments' )->justReturn( 15 );

		$mock = Mockery::mock( 'alias:Imagify_Files_Stats' );
		$mock->shouldReceive( 'count_optimized_files' )->once()->andReturn( 5 );

		$stat = Mockery::mock( StatInterface::class );
		$stat->shouldReceive( 'get_stat' )->once()->andReturn( 7 );
		$stat->shouldNotReceive( 'get_cached_stat' );

		$ability = $this->make_ability(
			[
				'success' => true,
				'message' => 0,
			],
			$stat
		);
		$impact  = $ability->get_impact_estimate( [] );

		$this->assertSame( 'image', $impact['unit'] );
		$this->assertSame( 7, $impact['count'] );
		$this->assertSame( 20, $impact['total'] );
	}

	/**
	 * Regression guard for #1186: when the live stat somehow exceeds the total
	 * (e.g. a transient race), 'count' must be clamped to 'total' so the AI never
	 * sees a nonsensical "N of M" with N > M.
	 */
	public function testGetImpactEstimateClampsCountToTotal(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'imagify_count_optimized_attachments' )->justReturn( 2 );

		$mock = Mockery::mock( 'alias:Imagify_Files_Stats' );
		$mock->shouldReceive( 'count_optimized_files' )->once()->andReturn( 1 );

		$stat = Mockery::mock( StatInterface::class );
		$stat->shouldReceive( 'get_stat' )->once()->andReturn( 50 );

		$ability = $this->make_ability(
			[
				'success' => true,
				'message' => 0,
			],
			$stat
		);
		$impact  = $ability->get_impact_estimate( [] );

		$this->assertSame( 3, $impact['total'] );
		$this->assertSame( 3, $impact['count'] );
	}

	/**
	 * Tests the success path: Bulk returns success=true with a queued count (AC #1 and spec table row 1),
	 * confirmed with quota OK.
	 */
	public function testSuccessPathReturnsScheduledWithQueuedCount(): void {
		$this->stubRequirements( true, false );

		$result = $this->make_ability(
			[
				'success' => true,
				'message' => 42,
			]
		)->execute( [ 'confirm' => true ] );

		$this->assertSame( 'scheduled', $result['status'] );
		$this->assertSame( 42, $result['queued_count'] );
		$this->assertNull( $result['error_message'] );
	}

	/**
	 * Tests that queued_count is cast to int on success (confirmed, quota OK).
	 */
	public function testSuccessPathCastsMessageToInt(): void {
		$this->stubRequirements( true, false );

		$result = $this->make_ability(
			[
				'success' => true,
				'message' => '7',
			]
		)->execute( [ 'confirm' => true ] );

		$this->assertIsInt( $result['queued_count'] );
		$this->assertSame( 7, $result['queued_count'] );
	}

	/**
	 * Tests the no-images no-op path: status=scheduled, queued_count=0 (AC #3), confirmed with quota OK.
	 *
	 * This is the critical non-obvious mapping: Bulk returns success=false / message='no-images',
	 * but the MCP contract requires this to be a successful no-op — NOT an error.
	 */
	public function testNoImagesReturnsScheduledWithZeroCount(): void {
		$this->stubRequirements( true, false );

		$result = $this->make_ability(
			[
				'success' => false,
				'message' => 'no-images',
			]
		)->execute( [ 'confirm' => true ] );

		$this->assertSame( 'scheduled', $result['status'] );
		$this->assertSame( 0, $result['queued_count'] );
		$this->assertNull( $result['error_message'] );
	}

	/**
	 * Tests the over-quota error path (spec table row 3): the guard only gates the pre-flight
	 * case, so a mid-execution 'over-quota' string from Bulk (confirmed, quota OK at guard time)
	 * is still mapped by the pre-existing error_response() readable-message translation.
	 */
	public function testOverQuotaReturnsError(): void {
		$this->stubRequirements( true, false );

		$result = $this->make_ability(
			[
				'success' => false,
				'message' => 'over-quota',
			]
		)->execute( [ 'confirm' => true ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 0, $result['queued_count'] );
		$this->assertNotNull( $result['error_message'] );
		$this->assertStringContainsString( 'quota', $result['error_message'] );
	}

	/**
	 * Tests the no-backup error path (spec table row 4), confirmed with quota OK at guard time.
	 */
	public function testNoBackupReturnsError(): void {
		$this->stubRequirements( true, false );

		$result = $this->make_ability(
			[
				'success' => false,
				'message' => 'no-backup',
			]
		)->execute( [ 'confirm' => true ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 0, $result['queued_count'] );
		$this->assertNotNull( $result['error_message'] );
		$this->assertStringContainsString( 'backup', $result['error_message'] );
	}

	/**
	 * Tests the generic/other error path (spec table row 5): the raw message is surfaced as-is
	 * (confirmed, quota OK at guard time).
	 */
	public function testOtherErrorMessageSurfacedAsIs(): void {
		$this->stubRequirements( true, false );

		$raw_message = 'The path to the selected files could not be retrieved.';

		$result = $this->make_ability(
			[
				'success' => false,
				'message' => $raw_message,
			]
		)->execute( [ 'confirm' => true ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 0, $result['queued_count'] );
		$this->assertSame( $raw_message, $result['error_message'] );
	}

	/**
	 * Tests that execute() always returns exactly the three required contract keys
	 * (confirmed, quota OK at guard time).
	 *
	 * @dataProvider provideAllBranchResults
	 *
	 * @param array $bulk_result Stubbed Bulk return value.
	 */
	public function testReturnedShapeAlwaysHasThreeContractKeys( array $bulk_result ): void {
		$this->stubRequirements( true, false );

		$result = $this->make_ability( $bulk_result )->execute( [ 'confirm' => true ] );

		$this->assertArrayHasKey( 'status', $result );
		$this->assertArrayHasKey( 'queued_count', $result );
		$this->assertArrayHasKey( 'error_message', $result );
		$this->assertCount( 3, $result );
	}

	/**
	 * Data provider for all five contract-translation branches.
	 *
	 * @return array<string, array{array}>
	 */
	public function provideAllBranchResults(): array {
		return [
			'success'     => [
				[
					'success' => true,
					'message' => 5,
				],
			],
			'no-images'   => [
				[
					'success' => false,
					'message' => 'no-images',
				],
			],
			'over-quota'  => [
				[
					'success' => false,
					'message' => 'over-quota',
				],
			],
			'no-backup'   => [
				[
					'success' => false,
					'message' => 'no-backup',
				],
			],
			'other-error' => [
				[
					'success' => false,
					'message' => 'Some other error.',
				],
			],
		];
	}
}
