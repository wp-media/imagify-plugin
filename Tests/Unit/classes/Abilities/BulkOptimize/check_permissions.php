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
	 * Test: the permission result and the context capabilities checked for the given input.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The allowed "context:describer" pairs and the ability input.
	 * @param array $expected The permission result and the "context:describer" pairs checked, in order.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$checked = [];

		Functions\when( 'imagify_get_context' )->alias(
			function ( $name ) use ( $config, &$checked ) {
				return new class( $name, $config['allowed'], $checked ) {
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

		Actions\expectDone( 'imagify_mcp_permission_denied' )
			->times( $expected['allowed'] ? 0 : 1 )
			->with( BulkOptimize::ABILITY_ID, BulkOptimize::ABILITY_NAME, 'manage' );

		$ability = new BulkOptimize( Mockery::mock( BulkOptimizerInterface::class ) );

		$this->assertSame( $expected['allowed'], $ability->check_permissions( $config['input'] ) );
		$this->assertSame( $expected['checked'], $checked );
	}
}
