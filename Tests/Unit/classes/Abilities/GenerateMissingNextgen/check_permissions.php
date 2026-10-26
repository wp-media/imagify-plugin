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
 * `check_permissions()` is inherited from `AbstractAbility` and delegates to
 * this class's `has_permission()` override: the `manage` capability in the `wp`
 * context, plus `bulk-optimize` in every context the generation runs on.
 *
 * @covers \Imagify\Abilities\GenerateMissingNextgen::check_permissions
 * @group  MCP
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Builds a GenerateMissingNextgen instance whose Bulk returns the given contexts.
	 *
	 * @param string[] $contexts Contexts returned by the stubbed get_contexts().
	 * @return GenerateMissingNextgen
	 */
	private function make_ability( array $contexts ): GenerateMissingNextgen {
		$ability = new GenerateMissingNextgen( Bulk::get_instance(), Mockery::mock( StatInterface::class ) );

		$stub = new class( $contexts ) {
			private $contexts;

			public function __construct( array $contexts ) {
				$this->contexts = $contexts;
			}

			public function get_contexts(): array {
				return $this->contexts;
			}
		};

		$this->setPropertyValue( 'bulk', $ability, $stub );

		return $ability;
	}

	/**
	 * Stubs imagify_get_context() so only the given "context:describer" pairs are allowed.
	 *
	 * @param string[] $allowed Allowed pairs, e.g. [ 'wp:manage', 'wp:bulk-optimize' ].
	 * @return void
	 */
	private function allow( array $allowed ): void {
		Functions\when( 'imagify_get_context' )->alias(
			function ( $name ) use ( $allowed ) {
				return new class( $name, $allowed ) {
					private $name;
					private $allowed;

					public function __construct( $name, array $allowed ) {
						$this->name    = $name;
						$this->allowed = $allowed;
					}

					public function current_user_can( $describer ): bool {
						return in_array( "{$this->name}:{$describer}", $this->allowed, true );
					}
				};
			}
		);
	}

	public function testReturnsTrueWhenUserCanBulkOptimizeEveryContext(): void {
		$this->allow( [ 'wp:manage', 'wp:bulk-optimize', 'custom-folders:bulk-optimize' ] );

		$this->assertTrue( $this->make_ability( [ 'wp', 'custom-folders' ] )->check_permissions() );
	}

	public function testReturnsFalseWhenUserCannotManage(): void {
		$this->allow( [ 'wp:bulk-optimize' ] );

		$this->assertFalse( $this->make_ability( [ 'wp' ] )->check_permissions() );
	}

	public function testReturnsFalseWhenUserCannotBulkOptimizeCustomFolders(): void {
		$this->allow( [ 'wp:manage', 'wp:bulk-optimize' ] );

		$this->assertFalse( $this->make_ability( [ 'wp', 'custom-folders' ] )->check_permissions() );
	}

	public function testDenialFiresPermissionDeniedAction(): void {
		$this->allow( [] );

		Actions\expectDone( 'imagify_mcp_permission_denied' )
			->once()
			->with( GenerateMissingNextgen::ABILITY_ID, GenerateMissingNextgen::ABILITY_NAME, 'manage' );

		$this->make_ability( [ 'wp' ] )->check_permissions();
	}
}
