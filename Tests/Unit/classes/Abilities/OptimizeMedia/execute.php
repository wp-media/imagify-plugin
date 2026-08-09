<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\OptimizeMedia;

use Brain\Monkey\Functions;
use Imagify\Abilities\MediaResolver;
use Imagify\Abilities\OptimizeMedia;
use Imagify\Tests\Unit\TestCase;
use Mockery;
use WP_Error;

/**
 * Tests for \Imagify\Abilities\OptimizeMedia::execute().
 *
 * `Imagify_Requirements` is a legacy classmap-autoloaded class; it is stubbed via a
 * Mockery alias mock in every test, so every test in this class runs in its own
 * process (`@runTestsInSeparateProcesses`) to guarantee the alias is registered
 * before the real class would otherwise be autoloaded.
 *
 * @covers \Imagify\Abilities\OptimizeMedia::execute
 * @group  OptimizeMedia
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Test_Execute extends TestCase {

	/**
	 * Stub get_post() and get_post_type() as a valid attachment.
	 * Shared by tests that need to pass the early validation checks.
	 */
	private function stubValidAttachment(): void {
		Functions\when( 'get_post' )->justReturn( Mockery::mock( 'WP_Post' ) );
		Functions\when( 'get_post_type' )->justReturn( 'attachment' );
	}

	/**
	 * Inject a fake query factory into MediaResolver so the filename path
	 * returns the supplied attachment IDs.
	 *
	 * @param array $posts Attachment IDs returned by the fake query.
	 * @return void
	 */
	private function stubFilenameQuery( array $posts ): void {
		$obj       = new \stdClass();
		$obj->posts = $posts;

		MediaResolver::set_query_factory(
			static function () use ( $obj ) {
				return $obj;
			}
		);
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
	 * Tests that execute() returns a confirmation_required response when confirm is not passed,
	 * with impact.count === 1 (a single-media optimization always costs exactly one unit).
	 */
	public function testReturnsConfirmationRequiredWhenConfirmIsNotPassed(): void {
		$this->stubRequirements( true, false );

		$ability = new OptimizeMedia();
		$result  = $ability->execute( [ 'media_id' => 123 ] );

		$this->assertSame( 'confirmation_required', $result['status'] );
		$this->assertSame( 1, $result['impact']['count'] );
	}

	/**
	 * Tests that execute() returns insufficient_quota (and never reaches do_execute()) when
	 * confirm: true is passed but the account is over quota.
	 */
	public function testReturnsInsufficientQuotaWhenConfirmedButOverQuota(): void {
		$this->stubRequirements( true, true );

		Functions\when( 'imagify_get_optimization_process' )->justReturn(
			Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' )->shouldNotReceive( 'get_data' )->getMock()
		);

		$ability = new OptimizeMedia();
		$result  = $ability->execute(
			[
				'media_id' => 123,
				'confirm'  => true,
			]
		);

		$this->assertSame( 'insufficient_quota', $result['status'] );
	}

	/**
	 * Tests that execute() returns an error response when media_id is missing (confirmed, quota OK).
	 */
	public function testReturnsErrorWhenMediaIdIsMissing(): void {
		$this->stubRequirements( true, false );

		$ability = new OptimizeMedia();
		$result  = $ability->execute( [ 'confirm' => true ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['original_size'] );
		$this->assertNull( $result['optimized_size'] );
		$this->assertNull( $result['savings_percent'] );
		$this->assertIsString( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error response when media_id is zero (confirmed, quota OK).
	 */
	public function testReturnsErrorWhenMediaIdIsZero(): void {
		$this->stubRequirements( true, false );

		$ability = new OptimizeMedia();
		$result  = $ability->execute(
			[
				'media_id' => 0,
				'confirm'  => true,
			]
		);

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['original_size'] );
		$this->assertNull( $result['optimized_size'] );
		$this->assertNull( $result['savings_percent'] );
		$this->assertIsString( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error response when media_id is negative (confirmed, quota OK).
	 */
	public function testReturnsErrorWhenMediaIdIsNegative(): void {
		$this->stubRequirements( true, false );

		$ability = new OptimizeMedia();
		$result  = $ability->execute(
			[
				'media_id' => -5,
				'confirm'  => true,
			]
		);

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['original_size'] );
		$this->assertNull( $result['optimized_size'] );
		$this->assertNull( $result['savings_percent'] );
		$this->assertIsString( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error response when the attachment does not exist (confirmed, quota OK).
	 */
	public function testReturnsErrorWhenAttachmentDoesNotExist(): void {
		$this->stubRequirements( true, false );
		Functions\when( 'get_post' )->justReturn( false );

		$ability = new OptimizeMedia();
		$result  = $ability->execute(
			[
				'media_id' => 123,
				'confirm'  => true,
			]
		);

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['original_size'] );
		$this->assertNull( $result['optimized_size'] );
		$this->assertNull( $result['savings_percent'] );
		$this->assertIsString( $result['error_message'] );
	}

	/**
	 * Tests that execute() resolves media_filename via MediaResolver and treats the result like a numeric media_id.
	 */
	public function testResolvesByFilename(): void {
		$this->stubRequirements( true, false );
		$this->stubValidAttachment();
		$this->stubFilenameQuery( [ 262 ] );

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$data    = Mockery::mock();
		$data->shouldReceive( 'is_optimized' )->andReturn( false );
		$data->shouldReceive( 'get_optimization_data' )->andReturn( [ 'stats' => [] ] );
		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$process->shouldReceive( 'optimize' )->andReturn( true );
		$media = Mockery::mock();
		$media->shouldReceive( 'get_raw_original_path' )->andReturn( '/tmp/hero.jpg' );
		$process->shouldReceive( 'get_media' )->andReturn( $media );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		Functions\expect( 'sanitize_file_name' )
			->with( 'hero.jpg' )
			->andReturn( 'hero.jpg' );

		$ability = new OptimizeMedia();
		$result  = $ability->execute(
			[
				'media_filename' => 'hero.jpg',
				'confirm'        => true,
			]
		);

		$this->assertSame( 'success', $result['status'] );
		$this->assertNull( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error when the media_filename matches multiple attachments.
	 */
	public function testReturnsErrorWhenFilenameIsAmbiguous(): void {
		$this->stubRequirements( true, false );
		$this->stubFilenameQuery( [ 10, 11, 12 ] );

		Functions\expect( 'sanitize_file_name' )
			->with( 'shared.jpg' )
			->andReturn( 'shared.jpg' );

		$ability = new OptimizeMedia();
		$result  = $ability->execute(
			[
				'media_filename' => 'shared.jpg',
				'confirm'        => true,
			]
		);

		$this->assertSame( 'error', $result['status'] );
		$this->assertIsString( $result['error_message'] );
		$this->assertStringContainsString( 'Multiple', $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error when no identifier (media_id, media_url, media_filename) is provided.
	 */
	public function testReturnsErrorWhenNoIdentifierProvided(): void {
		$this->stubRequirements( true, false );

		$ability = new OptimizeMedia();
		$result  = $ability->execute( [ 'confirm' => true ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertIsString( $result['error_message'] );
		$this->assertStringContainsString( 'media_id', $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error response when the post is not an attachment (confirmed, quota OK).
	 */
	public function testReturnsErrorWhenPostIsNotAnAttachment(): void {
		$this->stubRequirements( true, false );
		Functions\when( 'get_post' )->justReturn( Mockery::mock( 'WP_Post' ) );
		Functions\when( 'get_post_type' )->justReturn( 'post' );

		$ability = new OptimizeMedia();
		$result  = $ability->execute(
			[
				'media_id' => 123,
				'confirm'  => true,
			]
		);

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['original_size'] );
		$this->assertNull( $result['optimized_size'] );
		$this->assertNull( $result['savings_percent'] );
		$this->assertIsString( $result['error_message'] );
	}

	/**
	 * Tests that execute() uses the provided optimization_level when optimizing (confirmed, quota OK).
	 */
	public function testPassesOptimizationLevelToOptimizeWhenNotOptimized(): void {
		$this->stubRequirements( true, false );
		$this->stubValidAttachment();

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$data    = Mockery::mock( 'Imagify\Optimization\Data\DataInterface' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$data->shouldReceive( 'is_optimized' )->andReturn( false );
		$data->shouldReceive( 'get_optimization_data' )->andReturn(
			[
				'stats' => [
					'original_size'  => 1000,
					'optimized_size' => 800,
				],
			]
		);

		$process->shouldReceive( 'optimize' )
			->once()
			->with( 1 )
			->andReturn( true );

		$process->shouldReceive( 'get_media' )->andReturn( null );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		$ability = Mockery::mock( OptimizeMedia::class )->makePartial();
		$ability->shouldAllowMockingProtectedMethods();
		$ability->shouldReceive( 'get_media_original_size' )->andReturn( 1000 );
		$ability->shouldReceive( 'get_media_optimized_size' )->andReturn( 800 );

		$ability->execute(
			[
				'media_id'           => 123,
				'optimization_level' => 1,
				'confirm'            => true,
			]
		);
	}

	/**
	 * Tests that execute() uses reoptimize() when media is already optimized (confirmed, quota OK).
	 */
	public function testCallsReoptimizeWhenMediaIsOptimized(): void {
		$this->stubRequirements( true, false );
		$this->stubValidAttachment();

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$data    = Mockery::mock( 'Imagify\Optimization\Data\DataInterface' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$data->shouldReceive( 'is_optimized' )->andReturn( true );

		$process->shouldReceive( 'reoptimize' )
			->once()
			->andReturn( true );

		$process->shouldReceive( 'get_media' )->andReturn( null );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		$ability = Mockery::mock( OptimizeMedia::class )->makePartial();
		$ability->shouldAllowMockingProtectedMethods();
		$ability->shouldReceive( 'get_media_original_size' )->andReturn( 1000 );
		$ability->shouldReceive( 'get_media_optimized_size' )->andReturn( 800 );

		$ability->execute(
			[
				'media_id'           => 123,
				'optimization_level' => 2,
				'confirm'            => true,
			]
		);
	}

	/**
	 * Tests that execute() returns an error response when optimize() returns a WP_Error (confirmed, quota OK).
	 */
	public function testReturnsErrorWhenOptimizeReturnsWpError(): void {
		$this->stubRequirements( true, false );
		$this->stubValidAttachment();

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$data    = Mockery::mock( 'Imagify\Optimization\Data\DataInterface' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		// is_optimized() is false — first-time optimization path.
		// get_optimization_data() and get_media() are called by get_media_original_size().
		$data->shouldReceive( 'is_optimized' )->andReturn( false );
		$data->shouldReceive( 'get_optimization_data' )->andReturn( [] );
		$process->shouldReceive( 'get_media' )->andReturn( null );

		// Use a real WP_Error so is_wp_error() (instanceof check) works.
		$error = new \WP_Error( 'optimization_failed', 'Optimization failed.' );

		$process->shouldReceive( 'optimize' )->andReturn( $error );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		$ability = new OptimizeMedia();
		$result  = $ability->execute(
			[
				'media_id' => 123,
				'confirm'  => true,
			]
		);

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 'Optimization failed.', $result['error_message'] );
	}

	/**
	 * Tests that execute() returns a success response with correct shape on successful optimization
	 * (confirmed, quota OK) — regression: guard does not alter the existing success shape.
	 */
	public function testReturnsSuccessResponseOnSuccessfulOptimization(): void {
		$this->stubRequirements( true, false );
		$this->stubValidAttachment();

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$data    = Mockery::mock( 'Imagify\Optimization\Data\DataInterface' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$data->shouldReceive( 'is_optimized' )->andReturn( false );
		$process->shouldReceive( 'optimize' )->andReturn( true );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		$ability = Mockery::mock( OptimizeMedia::class )->makePartial();
		$ability->shouldAllowMockingProtectedMethods();
		$ability->shouldReceive( 'get_media_original_size' )->andReturn( 1000 );
		$ability->shouldReceive( 'get_media_optimized_size' )->andReturn( 800 );

		$result = $ability->execute(
			[
				'media_id' => 123,
				'confirm'  => true,
			]
		);

		$this->assertSame( 'success', $result['status'] );
		$this->assertSame( 1000, $result['original_size'] );
		$this->assertSame( 800, $result['optimized_size'] );
		$this->assertIsFloat( $result['savings_percent'] );
		$this->assertSame( 20.0, $result['savings_percent'] );
		$this->assertNull( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns a success response with null savings_percent when original_size is 0
	 * (confirmed, quota OK).
	 */
	public function testReturnsSavingsPercentNullWhenOriginalSizeIsZero(): void {
		$this->stubRequirements( true, false );
		$this->stubValidAttachment();

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$data    = Mockery::mock( 'Imagify\Optimization\Data\DataInterface' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$data->shouldReceive( 'is_optimized' )->andReturn( false );
		$process->shouldReceive( 'optimize' )->andReturn( true );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		$ability = Mockery::mock( OptimizeMedia::class )->makePartial();
		$ability->shouldAllowMockingProtectedMethods();
		$ability->shouldReceive( 'get_media_original_size' )->andReturn( 0 );
		$ability->shouldReceive( 'get_media_optimized_size' )->andReturn( 800 );

		$result = $ability->execute(
			[
				'media_id' => 123,
				'confirm'  => true,
			]
		);

		$this->assertSame( 'success', $result['status'] );
		$this->assertNull( $result['savings_percent'] );
	}

	/**
	 * Tests that execute() returns a success response with null savings_percent when optimized_size is null
	 * (confirmed, quota OK).
	 */
	public function testReturnsSavingsPercentNullWhenOptimizedSizeIsNull(): void {
		$this->stubRequirements( true, false );
		$this->stubValidAttachment();

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$data    = Mockery::mock( 'Imagify\Optimization\Data\DataInterface' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$data->shouldReceive( 'is_optimized' )->andReturn( false );
		$process->shouldReceive( 'optimize' )->andReturn( true );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		$ability = Mockery::mock( OptimizeMedia::class )->makePartial();
		$ability->shouldAllowMockingProtectedMethods();
		$ability->shouldReceive( 'get_media_original_size' )->andReturn( 1000 );
		$ability->shouldReceive( 'get_media_optimized_size' )->andReturn( null );

		$result = $ability->execute(
			[
				'media_id' => 123,
				'confirm'  => true,
			]
		);

		$this->assertSame( 'success', $result['status'] );
		$this->assertNull( $result['savings_percent'] );
	}
}
