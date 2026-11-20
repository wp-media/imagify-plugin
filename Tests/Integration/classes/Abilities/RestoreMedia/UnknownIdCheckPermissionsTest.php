<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\RestoreMedia;

use Imagify\Tests\Integration\TestCase;

/**
 * An administrator who targets a media ID nothing resolves to gets the real error from
 * execute(), not a permission error, on every kind of install.
 *
 * @covers \Imagify\Abilities\RestoreMedia::check_permissions
 * @covers \Imagify\Abilities\RestoreMedia::execute
 * @group  Abilities
 */
class UnknownIdCheckPermissionsTest extends TestCase {

	protected $useApi = false;

	public function set_up() {
		parent::set_up();

		if ( version_compare( $GLOBALS['wp_version'], '6.9', '<' ) ) {
			$this->markTestSkipped( 'WordPress 6.9+ required for Abilities API.' );
		}
	}

	public function tear_down() {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Test: the permission check passes and execute() reports the expected error.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The site type required (see skipUnlessSite()), whether the admin is a super admin, and
	 *                        the target: an unknown ID, or an existing attachment.
	 * @param array $expected The error message execute() returns.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->skipUnlessSite( $config['site'] );

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );

		if ( $config['super_admin'] && is_multisite() ) {
			grant_super_admin( $user_id );
		}

		wp_set_current_user( $user_id );

		$media_id = 'attachment' === $config['target'] ? self::factory()->attachment->create() : 999999;
		$input    = [ 'media_id' => $media_id ];
		$ability  = wp_get_ability( 'imagify/restore-media' );

		$this->assertTrue( $ability->check_permissions( $input ) );

		$result = $ability->execute( $input );

		$this->assertIsArray( $result );
		$this->assertSame( 'error', $result['status'] );
		$this->assertStringContainsString( $expected['message'], $result['error_message'] );
	}
}
