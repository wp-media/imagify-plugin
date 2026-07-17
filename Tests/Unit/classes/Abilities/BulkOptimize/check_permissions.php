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
 * @group  MCP
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Tests that check_permissions() returns true for a user with manage_options.
	 */
	public function testReturnsTrueWhenUserCanManageOptions(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$ability = new BulkOptimize( Mockery::mock( BulkOptimizerInterface::class ) );
		$this->assertTrue( $ability->check_permissions() );
	}

	/**
	 * Tests that check_permissions() returns false for a user without manage_options.
	 */
	public function testReturnsFalseWhenUserCannotManageOptions(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$ability = new BulkOptimize( Mockery::mock( BulkOptimizerInterface::class ) );
		$this->assertFalse( $ability->check_permissions() );
	}
}
