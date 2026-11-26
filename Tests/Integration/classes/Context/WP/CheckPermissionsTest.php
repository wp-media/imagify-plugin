<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Context\WP;

use Imagify\Tests\Integration\TestCase;

/**
 * The 'manage-site' describer is 'manage' limited to the current site: a site admin keeps it on a
 * network-activated install, and every filter that can deny 'manage' can deny it too.
 *
 * @covers \Imagify\Context\WP::get_capacity
 * @covers \Imagify\Context\AbstractContext::user_can
 * @group  Context
 */
class CheckPermissionsTest extends TestCase {

	protected $useApi = false;

	public function tear_down() {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Registers the filter behaviour of a scenario.
	 *
	 * @param string $filter Scenario name.
	 * @return void
	 */
	private function apply_filter_scenario( string $filter ): void {
		switch ( $filter ) {
			case 'capacity-denies-manage-site':
				add_filter(
					'imagify_capacity',
					function ( $capacity, $describer ) {
						return 'manage-site' === $describer ? 'do_not_allow' : $capacity;
					},
					10,
					2
				);
				break;

			case 'capacity-keeps-manage-site-network-only':
				// The opt-out documented in docs/api/mcp.md.
				add_filter(
					'imagify_capacity',
					function ( $capacity, $describer ) {
						return 'manage-site' === $describer ? 'manage_network_options' : $capacity;
					},
					10,
					2
				);
				break;

			case 'capacity-sets-manage-to-default':
				add_filter(
					'imagify_capacity',
					function ( $capacity, $describer ) {
						return 'manage' === $describer ? 'manage_network_options' : $capacity;
					},
					10,
					2
				);
				break;

			case 'capacity-customizes-manage':
				add_filter(
					'imagify_capacity',
					function ( $capacity, $describer ) {
						return 'manage' === $describer ? 'manage_network_users' : $capacity;
					},
					10,
					2
				);
				break;

			case 'current-user-can-denies-manage-site':
				add_filter(
					'imagify_current_user_can',
					function ( $user_can, $capacity, $describer ) {
						return 'manage-site' === $describer ? false : $user_can;
					},
					10,
					3
				);
				break;

			case 'user-can-denies-manage-site':
				add_filter(
					'imagify_user_can',
					function ( $user_can, $user_id, $capacity, $describer ) {
						return 'manage-site' === $describer ? false : $user_can;
					},
					10,
					4
				);
				break;

			case 'current-user-can-denies-manage':
				add_filter(
					'imagify_current_user_can',
					function ( $user_can, $capacity, $describer ) {
						return 'manage' === $describer ? false : $user_can;
					},
					10,
					3
				);
				break;

			case 'user-can-denies-manage':
				add_filter(
					'imagify_user_can',
					function ( $user_can, $user_id, $capacity, $describer ) {
						return 'manage' === $describer ? false : $user_can;
					},
					10,
					4
				);
				break;

			case 'filters-grant-manage':
				add_filter(
					'imagify_current_user_can',
					function ( $user_can, $capacity, $describer ) {
						return 'manage' === $describer ? true : $user_can;
					},
					10,
					3
				);
				add_filter(
					'imagify_user_can',
					function ( $user_can, $user_id, $capacity, $describer ) {
						return 'manage' === $describer ? true : $user_can;
					},
					10,
					4
				);
				break;
		}
	}

	/**
	 * Test: the 'manage-site' capacity and the answer for the current user and for another user.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The site type required (see skipUnlessSite()), the user role, whether the user is a super
	 *                        admin, the context name and the filter scenario.
	 * @param array $expected The capacity (when relevant), and whether `manage-site` is granted when the user is the
	 *                        current user and when it is not.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->skipUnlessSite( $config['site'] );

		$user_id = self::factory()->user->create( [ 'role' => $config['role'] ] );
		$other   = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		if ( $config['super_admin'] && is_multisite() ) {
			grant_super_admin( $user_id );
		}

		$this->apply_filter_scenario( $config['filter'] );

		$context = imagify_get_context( $config['context'] );

		if ( isset( $expected['capacity'] ) ) {
			$this->assertSame( $expected['capacity'], $context->get_capacity( 'manage-site' ) );
		}

		wp_set_current_user( $user_id );
		$this->assertSame( $expected['current_user'], $context->current_user_can( 'manage-site' ), 'Current user.' );

		wp_set_current_user( $other );
		$this->assertSame( $expected['other_user'], $context->user_can( $user_id, 'manage-site' ), 'Another user.' );
	}

	/**
	 * Test: describers other than 'manage-site' run each user-can filter once.
	 */
	public function testShouldFireUserCanFiltersOnceForOtherDescribers(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$calls = [];
		add_filter(
			'imagify_current_user_can',
			function ( $user_can, $capacity, $describer ) use ( &$calls ) {
				$calls[] = $describer;

				return $user_can;
			},
			10,
			3
		);

		imagify_get_context( 'wp' )->current_user_can( 'manage' );
		imagify_get_context( 'wp' )->current_user_can( 'manage-site' );

		$this->assertSame( [ 'manage', 'manage-site', 'manage' ], $calls );
	}

	/**
	 * Test: one 'manage-site' check resolves the 'manage' capacity twice: inside get_capacity( 'manage-site' ),
	 * then once for both deny-only passes.
	 */
	public function testShouldResolveManageCapacityTwiceForManageSite(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$calls = [];
		add_filter(
			'imagify_capacity',
			function ( $capacity, $describer, $context ) use ( &$calls ) {
				if ( 'wp' === $context ) {
					$calls[] = $describer;
				}

				return $capacity;
			},
			10,
			3
		);

		$this->assertTrue( imagify_get_context( 'wp' )->current_user_can( 'manage-site' ) );
		$this->assertSame( [ 'manage', 'manage-site', 'manage' ], $calls );
	}
}
