<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetMediaStatus;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Imagify\Abilities\GetMediaStatus;
use Imagify\Tests\Unit\TestCase;

/**
 * @covers \Imagify\Abilities\GetMediaStatus::check_permissions
 * @group  GetMediaStatus
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Tests that get_id() returns the ability slug.
	 */
	public function testGetIdReturnsAbilitySlug(): void {
		$this->assertSame( 'imagify/get-media-status', ( new GetMediaStatus() )->get_id() );
	}

	/**
	 * Tests that get_name() returns the human-readable ability label.
	 */
	public function testGetNameReturnsAbilityLabel(): void {
		$this->assertSame( 'Get Media Status', ( new GetMediaStatus() )->get_name() );
	}

	public function testReturnsTrueWhenContextAllows(): void {
		$context = new class {
			public function current_user_can( string $capability ): bool {
				return true;
			}
		};
		Functions\when( 'imagify_get_context' )->justReturn( $context );

		$this->assertTrue( ( new GetMediaStatus() )->check_permissions() );
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
			->with( 'imagify/get-media-status', 'Get Media Status', 'manage' );

		$this->assertFalse( ( new GetMediaStatus() )->check_permissions() );
	}
}
