<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\BulkOptimize;

use Brain\Monkey\Functions;
use Imagify\Abilities\BulkOptimize;
use Imagify\Bulk\BulkOptimizerInterface;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Abilities\BulkOptimize::check_permissions().
 *
 * @covers \Imagify\Abilities\BulkOptimize::check_permissions
 * @group  BulkOptimize
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Tests that check_permissions() returns true when current_user_can( 'manage_options' ) is true.
	 */
	public function testReturnsTrueWhenUserHasManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )
			->justReturn( true );

		$bulk    = Mockery::mock( BulkOptimizerInterface::class );
		$ability = new BulkOptimize( $bulk );
		$result  = $ability->check_permissions();

		$this->assertTrue( $result );
	}

	/**
	 * Tests that check_permissions() returns false when current_user_can( 'manage_options' ) is false.
	 */
	public function testReturnsFalseWhenUserLacksManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )
			->justReturn( false );

		$bulk    = Mockery::mock( BulkOptimizerInterface::class );
		$ability = new BulkOptimize( $bulk );
		$result  = $ability->check_permissions();

		$this->assertFalse( $result );
	}

	/**
	 * Tests that check_permissions() calls current_user_can() exactly once with 'manage_options'.
	 */
	public function testCallsCurrentUserCanWithManageOptionsCapability(): void {
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		$bulk    = Mockery::mock( BulkOptimizerInterface::class );
		$ability = new BulkOptimize( $bulk );
		$ability->check_permissions();
	}
}
