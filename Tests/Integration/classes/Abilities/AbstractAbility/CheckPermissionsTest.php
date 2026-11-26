<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\AbstractAbility;

use Imagify\Abilities\BulkOptimize;
use Imagify\Bulk\Bulk;
use Imagify\Tests\Integration\TestCase;
use Imagify\User\User;

/**
 * A caller holding only `manage-site` (a site admin of a network-activated install) gets guard
 * responses without account details, which still validate against the ability output schema.
 *
 * @covers \Imagify\Abilities\AbstractAbility::guard_credit_confirmation
 * @group  Abilities
 */
class CheckPermissionsTest extends TestCase {

	protected $useApi = false;

	/**
	 * Previous value of the Imagify_Requirements cache.
	 *
	 * @var mixed
	 */
	private $previous_supports;

	public function set_up() {
		parent::set_up();

		if ( version_compare( $GLOBALS['wp_version'], '6.9', '<' ) ) {
			$this->markTestSkipped( 'WordPress 6.9+ required for Abilities API.' );
		}

		$this->previous_supports = $this->getNonPublicPropertyValue( 'supports', 'Imagify_Requirements', null );
	}

	public function tear_down() {
		if ( null !== $this->previous_supports ) {
			$this->setPropertyValue( 'supports', 'Imagify_Requirements', $this->previous_supports );
		}

		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Test: the guard response keeps or drops the account details depending on the caller.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The site type required (see skipUnlessSite()), whether the admin is a super admin, and
	 *                        whether the account is over quota.
	 * @param array $expected The response status and the keys that must be present and absent.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->skipUnlessSite( $config['site'] );

		$this->setPropertyValue(
			'supports',
			'Imagify_Requirements',
			[
				'api_key_valid' => true,
				'over_quota'    => $config['over_quota'],
			]
		);

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );

		if ( $config['super_admin'] && is_multisite() ) {
			grant_super_admin( $user_id );
		}

		wp_set_current_user( $user_id );

		$ability = new class( Bulk::get_instance() ) extends BulkOptimize {
			protected function fetch_user(): User {
				$user = new class() extends User {
					public function init_user() {}
				};

				$user->plan_id                      = 1;
				$user->quota                        = 100;
				$user->consumed_current_month_quota = 10;
				$user->extra_quota                  = 0;
				$user->extra_quota_consumed         = 0;
				$user->next_date_update             = '2030-01-01';

				return $user;
			}
		};

		$result = $ability->execute( [ 'context' => 'wp' ] );

		$this->assertSame( $expected['status'], $result['status'] );

		foreach ( $expected['present'] as $key ) {
			$this->assertArrayHasKey( $key, $result );
		}

		foreach ( $expected['absent'] as $key ) {
			$this->assertArrayNotHasKey( $key, $result );
		}

		$schema = wp_get_ability( 'imagify/bulk-optimize' )->get_output_schema();

		$this->assertTrue( rest_validate_value_from_schema( $result, $schema ) );
	}
}
