<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetNextgenCoverage;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Imagify\Abilities\GetNextgenCoverage;
use Imagify\Stats\StatInterface;
use Imagify\Tests\Unit\TestCase;

/**
 * @covers \Imagify\Abilities\GetNextgenCoverage::check_permissions
 * @group  GetNextgenCoverage
 */
class Test_CheckPermissions extends TestCase {

	private function make_ability(): GetNextgenCoverage {
		$stat = new class implements StatInterface {
			public function get_stat() {
				return null;
			}
			public function get_cached_stat() {
				return null;
			}
			public function clear_cache(): void {}
		};

		return new GetNextgenCoverage( $stat );
	}

	/**
	 * Tests that get_id() returns the ability slug.
	 */
	public function testGetIdReturnsAbilitySlug(): void {
		$this->assertSame( 'imagify/get-nextgen-coverage', $this->make_ability()->get_id() );
	}

	/**
	 * Tests that get_name() returns the human-readable ability label.
	 */
	public function testGetNameReturnsAbilityLabel(): void {
		$this->assertSame( 'Get next-gen coverage', $this->make_ability()->get_name() );
	}

	public function testReturnsTrueWhenContextAllows(): void {
		$context = new class {
			public function current_user_can( string $capability ): bool {
				return true;
			}
		};
		Functions\when( 'imagify_get_context' )->justReturn( $context );

		$this->assertTrue( $this->make_ability()->check_permissions() );
	}

	public function testReturnsFalseWhenContextDenies(): void {
		$context = new class {
			public function current_user_can( string $capability ): bool {
				return false;
			}
		};
		Functions\when( 'imagify_get_context' )->justReturn( $context );
		Actions\expectDone( 'imagify_mcp_permission_denied' )
			->once()
			->with( 'imagify/get-nextgen-coverage', 'Get next-gen coverage', 'manage' );

		$this->assertFalse( $this->make_ability()->check_permissions() );
	}
}
