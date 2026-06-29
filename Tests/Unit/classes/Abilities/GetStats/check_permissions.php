<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetStats;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetStats;
use Imagify\Tests\Unit\TestCase;

/**
 * @covers \Imagify\Abilities\GetStats::check_permissions
 * @group  GetStats
 */
class Test_CheckPermissions extends TestCase {

	public function testReturnsTrueWhenContextAllows(): void {
		$context = new class {
			public function current_user_can( string $capability ): bool {
				return true;
			}
		};
		Functions\when( 'imagify_get_context' )->justReturn( $context );

		$this->assertTrue( ( new GetStats() )->check_permissions() );
	}

	public function testReturnsFalseWhenContextDenies(): void {
		$context = new class {
			public function current_user_can( string $capability ): bool {
				return false;
			}
		};
		Functions\when( 'imagify_get_context' )->justReturn( $context );

		$this->assertFalse( ( new GetStats() )->check_permissions() );
	}
}
