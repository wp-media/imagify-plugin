<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\BulkOptimize;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Imagify\Abilities\BulkOptimize;
use Imagify\Bulk\BulkOptimizerInterface;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Abilities\BulkOptimize::check_permissions().
 *
 * `check_permissions()` is inherited from `AbstractAbility`: it requires the
 * `manage` capability in the `wp` context, then `bulk-optimize` in the context
 * named by the input.
 *
 * @covers \Imagify\Abilities\BulkOptimize::check_permissions
 * @group  MCP
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Context/describer pairs checked so far, as "context:describer".
	 *
	 * @var string[]
	 */
	private $checked = [];

	/**
	 * Stubs imagify_get_context() so only the given "context:describer" pairs are allowed.
	 *
	 * @param string[] $allowed Allowed pairs, e.g. [ 'wp:manage', 'wp:bulk-optimize' ].
	 * @return void
	 */
	private function allow( array $allowed ): void {
		$checked = &$this->checked;

		Functions\when( 'imagify_get_context' )->alias(
			function ( $name ) use ( $allowed, &$checked ) {
				return new class( $name, $allowed, $checked ) {
					private $name;
					private $allowed;
					private $checked;

					public function __construct( $name, array $allowed, array &$checked ) {
						$this->name    = $name;
						$this->allowed = $allowed;
						$this->checked = &$checked;
					}

					public function current_user_can( $describer ): bool {
						$this->checked[] = "{$this->name}:{$describer}";

						return in_array( "{$this->name}:{$describer}", $this->allowed, true );
					}
				};
			}
		);
	}

	private function make_ability(): BulkOptimize {
		return new BulkOptimize( Mockery::mock( BulkOptimizerInterface::class ) );
	}

	public function testReturnsTrueWhenUserCanBulkOptimizeRequestedContext(): void {
		$this->allow( [ 'wp:manage', 'custom-folders:bulk-optimize' ] );

		$this->assertTrue( $this->make_ability()->check_permissions( [ 'context' => 'custom-folders' ] ) );
		$this->assertSame( [ 'wp:manage', 'custom-folders:bulk-optimize' ], $this->checked );
	}

	public function testReturnsFalseWhenUserCannotManage(): void {
		$this->allow( [ 'wp:bulk-optimize' ] );

		$this->assertFalse( $this->make_ability()->check_permissions( [ 'context' => 'wp' ] ) );
	}

	/**
	 * A user allowed on the media library is still denied custom folders when
	 * the custom-folders capability is not granted (e.g. a subsite admin).
	 */
	public function testReturnsFalseWhenUserCannotBulkOptimizeCustomFolders(): void {
		$this->allow( [ 'wp:manage', 'wp:bulk-optimize' ] );

		$this->assertFalse( $this->make_ability()->check_permissions( [ 'context' => 'custom-folders' ] ) );
	}

	public function testChecksWpContextWhenNoInputIsGiven(): void {
		$this->allow( [ 'wp:manage', 'wp:bulk-optimize' ] );

		$this->assertTrue( $this->make_ability()->check_permissions() );
		$this->assertSame( [ 'wp:manage', 'wp:bulk-optimize' ], $this->checked );
	}

	public function testDenialFiresPermissionDeniedAction(): void {
		$this->allow( [ 'wp:manage' ] );

		Actions\expectDone( 'imagify_mcp_permission_denied' )
			->once()
			->with( BulkOptimize::ABILITY_ID, BulkOptimize::ABILITY_NAME, 'manage' );

		$this->make_ability()->check_permissions( [ 'context' => 'custom-folders' ] );
	}
}
