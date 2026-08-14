<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\inc\classes\AutoOptimization;

use Brain\Monkey\Functions;
use Imagify_Auto_Optimization;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify_Auto_Optimization::maybe_store_generate_step() — WordPress 7.1 can let the
 * browser process an upload, in which case the attachment is created with no sub sizes and each
 * one is sent afterwards. Optimizing when the attachment is created would only ever cover the
 * full size, so the "generate" step has to wait for the request that brings the sub sizes in.
 *
 * @covers \Imagify_Auto_Optimization::maybe_store_generate_step
 * @covers \Imagify_Auto_Optimization::flag_awaiting_client_side_subsizes
 * @group  AutoOptimization
 * @since  2.3.3
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MaybeStoreGenerateStepTest extends TestCase {

	/**
	 * Stubs everything maybe_store_generate_step() needs beyond the transient calls.
	 */
	private function stubDependencies(): void {
		Functions\when( 'imagify_is_attachment_mime_type_supported' )->justReturn( true );
	}

	/**
	 * Test: on an ordinary upload the generate step is stored, as it always was.
	 */
	public function testStoresTheGenerateStepOnAnOrdinaryUpload(): void {
		$this->stubDependencies();
		Functions\when( 'get_transient' )->justReturn( false );

		$auto_optimization = new Imagify_Auto_Optimization();
		$metadata          = [ 'file' => 'image.jpg' ];

		$this->assertSame( $metadata, $auto_optimization->maybe_store_generate_step( $metadata, 42, 'create' ) );
		$this->assertTrue( $auto_optimization->has_step( 42, 'generate' ) );
	}

	/**
	 * Test: while the browser still owes its sub sizes, the create phase stores nothing, so
	 * no optimization is launched against the full size alone.
	 */
	public function testSkipsTheGenerateStepWhileAwaitingClientSideSubsizes(): void {
		$this->stubDependencies();
		Functions\when( 'get_transient' )->justReturn( 1 );

		$auto_optimization = new Imagify_Auto_Optimization();
		$metadata          = [ 'file' => 'image.jpg' ];

		$this->assertSame( $metadata, $auto_optimization->maybe_store_generate_step( $metadata, 42, 'create' ) );
		$this->assertFalse( $auto_optimization->has_step( 42, 'generate' ) );
		$this->assertFalse( $auto_optimization->has_step( 42, 'upload' ) );
	}

	/**
	 * Test: the request that brings the sub sizes in restores the upload step, so the media is
	 * treated as the new upload it still is, and clears the flag.
	 */
	public function testRestoresTheUploadStepWhenTheSubsizesArrive(): void {
		$this->stubDependencies();
		Functions\when( 'get_transient' )->justReturn( 1 );

		$deleted = [];

		Functions\when( 'delete_transient' )->alias(
			function ( $name ) use ( &$deleted ) {
				$deleted[] = $name;
				return true;
			}
		);

		$auto_optimization = new Imagify_Auto_Optimization();
		$metadata          = [ 'file' => 'image.jpg' ];

		$auto_optimization->maybe_store_generate_step( $metadata, 42, 'update' );

		$this->assertTrue( $auto_optimization->has_step( 42, 'generate' ) );
		$this->assertTrue( $auto_optimization->has_step( 42, 'upload' ) );
		$this->assertSame( [ 'imagify_awaiting_subsizes_42' ], $deleted );
	}

	/**
	 * Build a request stub returning the given value for the 'generate_sub_sizes' parameter.
	 *
	 * @param  mixed $value Value the parameter should return.
	 * @return Mockery\MockInterface
	 */
	private function requestReturning( $value ) {
		$request = Mockery::mock( 'WP_REST_Request' );
		$request->shouldReceive( 'get_param' )->with( 'generate_sub_sizes' )->andReturn( $value );

		return $request;
	}

	/**
	 * Test: the attachment is flagged when WordPress hands the sub sizes over to the browser.
	 */
	public function testFlagsTheAttachmentWhenTheBrowserHandlesTheSubsizes(): void {
		$stored = [];

		Functions\when( 'set_transient' )->alias(
			function ( $name, $value ) use ( &$stored ) {
				$stored[ $name ] = $value;
				return true;
			}
		);

		( new Imagify_Auto_Optimization() )->flag_awaiting_client_side_subsizes( (object) [ 'ID' => 42 ], $this->requestReturning( false ), true );

		$this->assertSame( [ 'imagify_awaiting_subsizes_42' => 1 ], $stored );
	}

	/**
	 * Test: nothing is flagged for an ordinary upload, where WordPress builds the sub sizes,
	 * nor when the parameter is absent entirely.
	 */
	public function testDoesNotFlagWhenWordPressBuildsTheSubsizes(): void {
		Functions\expect( 'set_transient' )->never();

		$attachment = (object) [ 'ID' => 42 ];

		( new Imagify_Auto_Optimization() )->flag_awaiting_client_side_subsizes( $attachment, $this->requestReturning( true ), true );
		( new Imagify_Auto_Optimization() )->flag_awaiting_client_side_subsizes( $attachment, $this->requestReturning( null ), true );
	}

	/**
	 * Test: nothing is flagged when an existing attachment is being updated rather than created.
	 */
	public function testDoesNotFlagWhenUpdatingAnAttachment(): void {
		Functions\expect( 'set_transient' )->never();

		( new Imagify_Auto_Optimization() )->flag_awaiting_client_side_subsizes( (object) [ 'ID' => 42 ], $this->requestReturning( false ), false );
	}
}
