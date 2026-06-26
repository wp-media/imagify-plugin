<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\BulkOptimize;

use Brain\Monkey\Functions;
use Imagify\Abilities\BulkOptimize;
use Imagify\Bulk\BulkOptimizerInterface;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Abilities\BulkOptimize::execute().
 *
 * @covers \Imagify\Abilities\BulkOptimize::execute
 * @group  BulkOptimize
 */
class Test_Execute extends TestCase {

	/**
	 * Tests that execute() returns scheduled when context is valid and run_optimize succeeds.
	 */
	public function testReturnsScheduledWhenContextIsValidAndRunSucceeds(): void {
		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldReceive( 'run_optimize' )
			->once()
			->with( 'wp', \Mockery::type( 'int' ) )
			->andReturn( [ 'success' => true, 'message' => 'success' ] );

		Functions\when( 'get_imagify_option' )->justReturn( 1 );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute( [ 'context' => 'wp' ] );

		$this->assertSame( 'scheduled', $result['status'] );
		$this->assertSame( 'wp', $result['context'] );
		$this->assertNull( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error when context is invalid.
	 */
	public function testReturnsErrorWhenContextIsInvalid(): void {
		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldNotReceive( 'run_optimize' );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute( [ 'context' => 'invalid' ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 'invalid', $result['context'] );
		$this->assertIsString( $result['error_message'] );
		$this->assertNotEmpty( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error when context is empty string.
	 */
	public function testReturnsErrorWhenContextIsEmpty(): void {
		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldNotReceive( 'run_optimize' );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute( [ 'context' => '' ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( '', $result['context'] );
		$this->assertIsString( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error when run_optimize fails with over-quota.
	 */
	public function testReturnsErrorWhenRunOptimizeFailsWithOverQuota(): void {
		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldReceive( 'run_optimize' )
			->once()
			->andReturn( [ 'success' => false, 'message' => 'over-quota' ] );

		Functions\when( 'get_imagify_option' )->justReturn( 0 );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute( [ 'context' => 'wp' ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 'over-quota', $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error when run_optimize fails with no-images.
	 */
	public function testReturnsErrorWhenRunOptimizeFailsWithNoImages(): void {
		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldReceive( 'run_optimize' )
			->once()
			->andReturn( [ 'success' => false, 'message' => 'no-images' ] );

		Functions\when( 'get_imagify_option' )->justReturn( 0 );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute( [ 'context' => 'wp' ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 'no-images', $result['error_message'] );
	}

	/**
	 * Tests that execute() uses the global option when optimization_level is not provided.
	 */
	public function testUsesGlobalOptionWhenOptimizationLevelNotProvided(): void {
		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldReceive( 'run_optimize' )
			->once()
			->with( 'wp', 1 )
			->andReturn( [ 'success' => true, 'message' => 'success' ] );

		Functions\expect( 'get_imagify_option' )
			->once()
			->with( 'optimization_level' )
			->andReturn( 1 );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute( [ 'context' => 'wp' ] );

		$this->assertSame( 'scheduled', $result['status'] );
	}

	/**
	 * Tests that execute() uses the provided optimization_level.
	 */
	public function testUsesProvidedOptimizationLevel(): void {
		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldReceive( 'run_optimize' )
			->once()
			->with( 'wp', 2 )
			->andReturn( [ 'success' => true, 'message' => 'success' ] );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute( [ 'context' => 'wp', 'optimization_level' => 2 ] );

		$this->assertSame( 'scheduled', $result['status'] );
	}

	/**
	 * Tests that execute() clamps optimization_level above 2 to 2.
	 */
	public function testClampsOptimizationLevelAbove2(): void {
		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldReceive( 'run_optimize' )
			->once()
			->with( 'wp', 2 )
			->andReturn( [ 'success' => true, 'message' => 'success' ] );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute( [ 'context' => 'wp', 'optimization_level' => 5 ] );

		$this->assertSame( 'scheduled', $result['status'] );
	}

	/**
	 * Tests that execute() clamps optimization_level below 0 to 0.
	 */
	public function testClampsOptimizationLevelBelow0(): void {
		$bulk = Mockery::mock( BulkOptimizerInterface::class );
		$bulk->shouldReceive( 'run_optimize' )
			->once()
			->with( 'wp', 0 )
			->andReturn( [ 'success' => true, 'message' => 'success' ] );

		$ability = new BulkOptimize( $bulk );
		$result  = $ability->execute( [ 'context' => 'wp', 'optimization_level' => -1 ] );

		$this->assertSame( 'scheduled', $result['status'] );
	}
}
