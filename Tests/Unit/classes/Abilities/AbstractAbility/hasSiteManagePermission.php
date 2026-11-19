<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\AbstractAbility;

use Brain\Monkey\Functions;
use Imagify\Abilities\AbstractAbility;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\AbstractAbility::has_site_manage_permission() and ::is_site_only_caller().
 *
 * @covers \Imagify\Abilities\AbstractAbility::has_site_manage_permission
 * @covers \Imagify\Abilities\AbstractAbility::is_site_only_caller
 * @group  MCP
 */
class Test_HasSiteManagePermission extends TestCase {

	/**
	 * Test: both helpers for the given allowed describers.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The describers the current user holds in the wp context.
	 * @param array $expected The results of the two helpers.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		Functions\when( 'imagify_get_context' )->justReturn(
			new class( $config['allowed'] ) {
				private $allowed;

				public function __construct( array $allowed ) {
					$this->allowed = $allowed;
				}

				public function current_user_can( $describer ): bool {
					return in_array( $describer, $this->allowed, true );
				}
			}
		);

		$ability = new class() extends AbstractAbility {
			public function get_id(): string {
				return 'test/ability';
			}

			public function get_name(): string {
				return 'Test ability';
			}

			protected function has_permission(): bool {
				return true;
			}

			public function register(): void {}

			public function execute( array $args = [] ): array {
				return [];
			}

			public function site_permission(): bool {
				return $this->has_site_manage_permission();
			}

			public function site_only(): bool {
				return $this->is_site_only_caller();
			}
		};

		$this->assertSame( $expected['site_permission'], $ability->site_permission() );
		$this->assertSame( $expected['site_only'], $ability->site_only() );
	}
}
