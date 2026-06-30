<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GenerateMissingNextgen;

use Brain\Monkey\Functions;
use Imagify\Abilities\GenerateMissingNextgen;
use Imagify\Bulk\Bulk;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GenerateMissingNextgen::execute().
 *
 * Covers all five contract-translation branches defined in the spec.
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
 * @covers \Imagify\Abilities\GenerateMissingNextgen::execute
 * @group  MCP
 */
class Test_Execute extends TestCase {

	/**
	 * Returns a GenerateMissingNextgen instance whose internal `$bulk` property
	 * has been replaced by an anonymous stub that returns `$bulk_result` from
	 * `run_generate_nextgen()` and `['wp']` from `get_contexts()`.
	 *
	 * @param array $bulk_result Value returned by the stubbed run_generate_nextgen().
	 * @return GenerateMissingNextgen
	 */
	private function make_ability( array $bulk_result ): GenerateMissingNextgen {
		// Construct with a real Bulk instance to satisfy the type hint.
		$ability = new GenerateMissingNextgen( Bulk::get_instance() );

		// Replace the injected Bulk with a lightweight stub via reflection.
		// Uses the two-argument ReflectionProperty::setValue( $object, $value ) form,
		// which does NOT trigger the PHP 8.4 single-argument deprecation.
		$stub = new class( $bulk_result ) {
			/** @var array */
			private $result;

			public function __construct( array $result ) {
				$this->result = $result;
			}

			/** @return array */
			public function get_contexts(): array {
				return [ 'wp' ];
			}

			/** @return array */
			public function run_generate_nextgen( array $contexts, array $formats ): array {
				return $this->result;
			}
		};

		$this->setPropertyValue( 'bulk', $ability, $stub );

		return $ability;
	}

	/**
	 * Tests the success path: Bulk returns success=true with a queued count (AC #1 and spec table row 1).
	 */
	public function testSuccessPathReturnsScheduledWithQueuedCount(): void {
		$result = $this->make_ability( [ 'success' => true, 'message' => 42 ] )->execute();

		$this->assertSame( 'scheduled', $result['status'] );
		$this->assertSame( 42, $result['queued_count'] );
		$this->assertNull( $result['error_message'] );
	}

	/**
	 * Tests that queued_count is cast to int on success.
	 */
	public function testSuccessPathCastsMessageToInt(): void {
		$result = $this->make_ability( [ 'success' => true, 'message' => '7' ] )->execute();

		$this->assertIsInt( $result['queued_count'] );
		$this->assertSame( 7, $result['queued_count'] );
	}

	/**
	 * Tests the no-images no-op path: status=scheduled, queued_count=0 (AC #3).
	 *
	 * This is the critical non-obvious mapping: Bulk returns success=false / message='no-images',
	 * but the MCP contract requires this to be a successful no-op — NOT an error.
	 */
	public function testNoImagesReturnsScheduledWithZeroCount(): void {
		$result = $this->make_ability( [ 'success' => false, 'message' => 'no-images' ] )->execute();

		$this->assertSame( 'scheduled', $result['status'] );
		$this->assertSame( 0, $result['queued_count'] );
		$this->assertNull( $result['error_message'] );
	}

	/**
	 * Tests the over-quota error path (spec table row 3).
	 */
	public function testOverQuotaReturnsError(): void {
		Functions\when( '__' )->returnArg();

		$result = $this->make_ability( [ 'success' => false, 'message' => 'over-quota' ] )->execute();

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 0, $result['queued_count'] );
		$this->assertNotNull( $result['error_message'] );
		$this->assertStringContainsString( 'quota', $result['error_message'] );
	}

	/**
	 * Tests the no-backup error path (spec table row 4).
	 */
	public function testNoBackupReturnsError(): void {
		Functions\when( '__' )->returnArg();

		$result = $this->make_ability( [ 'success' => false, 'message' => 'no-backup' ] )->execute();

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 0, $result['queued_count'] );
		$this->assertNotNull( $result['error_message'] );
		$this->assertStringContainsString( 'backup', $result['error_message'] );
	}

	/**
	 * Tests the generic/other error path (spec table row 5): the raw message is surfaced as-is.
	 */
	public function testOtherErrorMessageSurfacedAsIs(): void {
		$raw_message = 'The path to the selected files could not be retrieved.';

		$result = $this->make_ability( [ 'success' => false, 'message' => $raw_message ] )->execute();

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 0, $result['queued_count'] );
		$this->assertSame( $raw_message, $result['error_message'] );
	}

	/**
	 * Tests that execute() always returns exactly the three required contract keys.
	 *
	 * @dataProvider provideAllBranchResults
	 *
	 * @param array $bulk_result Stubbed Bulk return value.
	 */
	public function testReturnedShapeAlwaysHasThreeContractKeys( array $bulk_result ): void {
		Functions\when( '__' )->returnArg();

		$result = $this->make_ability( $bulk_result )->execute();

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
			'success'     => [ [ 'success' => true,  'message' => 5 ] ],
			'no-images'   => [ [ 'success' => false, 'message' => 'no-images' ] ],
			'over-quota'  => [ [ 'success' => false, 'message' => 'over-quota' ] ],
			'no-backup'   => [ [ 'success' => false, 'message' => 'no-backup' ] ],
			'other-error' => [ [ 'success' => false, 'message' => 'Some other error.' ] ],
		];
	}
}
