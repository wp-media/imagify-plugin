<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GenerateMissingNextgen;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Imagify\Abilities\GenerateMissingNextgen;
use Imagify\Bulk\Bulk;
use Imagify\Stats\StatInterface;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Abilities\GenerateMissingNextgen::check_permissions().
 *
 * Since Option B (finding 4), `check_permissions()` is inherited unmodified from
 * `AbstractAbility` and delegates to this class's `has_permission()` override; a
 * denial fires `imagify_mcp_permission_denied` with `get_required_capability()`'s
 * value (`manage_options`, not the base class's `manage` default).
 *
 * @covers \Imagify\Abilities\GenerateMissingNextgen::check_permissions
 * @group  MCP
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Builds a GenerateMissingNextgen instance with a permissive StatInterface mock.
	 *
	 * @return GenerateMissingNextgen
	 */
	private function make_ability(): GenerateMissingNextgen {
		$stat = Mockery::mock( StatInterface::class );

		return new GenerateMissingNextgen( Bulk::get_instance(), $stat );
	}

	/**
	 * Tests that check_permissions() returns true for a user with manage_options.
	 */
	public function testReturnsTrueWhenUserCanManageOptions(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$ability = $this->make_ability();
		$this->assertTrue( $ability->check_permissions() );
	}

	/**
	 * Tests that check_permissions() returns false for a user without manage_options.
	 */
	public function testReturnsFalseWhenUserCannotManageOptions(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$ability = $this->make_ability();
		$this->assertFalse( $ability->check_permissions() );
	}

	/**
	 * Finding 4 regression guard: get_required_capability() returns 'manage_options', matching
	 * has_permission()'s real capability check, so a denial reports the accurate capability
	 * (not the AbstractAbility default of 'manage').
	 */
	public function testGetRequiredCapabilityReturnsManageOptions(): void {
		$ability = $this->make_ability();

		$reflection = new \ReflectionMethod( $ability, 'get_required_capability' );
		$reflection->setAccessible( true );

		$this->assertSame( 'manage_options', $reflection->invoke( $ability ) );
	}

	/**
	 * Finding 4 regression guard: a denial fires imagify_mcp_permission_denied with
	 * 'manage_options', not the inherited 'manage' default.
	 */
	public function testDenialFiresPermissionDeniedActionWithManageOptions(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		Actions\expectDone( 'imagify_mcp_permission_denied' )
			->once()
			->with( GenerateMissingNextgen::ABILITY_ID, GenerateMissingNextgen::ABILITY_NAME, 'manage_options' );

		$ability = $this->make_ability();
		$ability->check_permissions();
	}
}
