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
	 * Test: the permission result for the given contexts and allowed capabilities.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The contexts the generation runs on and the allowed "context:describer" pairs.
	 * @param bool  $expected The permission result.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$ability = new GenerateMissingNextgen( Bulk::get_instance(), Mockery::mock( StatInterface::class ) );

		// Bulk is final: swap it for a stub returning the configured contexts.
		$this->setPropertyValue(
			'bulk',
			$ability,
			new class( $config['contexts'] ) {
				private $contexts;

				public function __construct( array $contexts ) {
					$this->contexts = $contexts;
				}

				public function get_contexts(): array {
					return $this->contexts;
				}
			}
		);

		Functions\when( 'imagify_get_context' )->alias(
			function ( $name ) use ( $config ) {
				return new class( $name, $config['allowed'] ) {
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

		Actions\expectDone( 'imagify_mcp_permission_denied' )
			->times( $expected ? 0 : 1 )
			->with( GenerateMissingNextgen::ABILITY_ID, GenerateMissingNextgen::ABILITY_NAME, 'manage' );

		$this->assertSame( $expected, $ability->check_permissions() );
	}
}
