<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\OptimizeMedia;

use Imagify\Tests\Integration\TestCase;

/**
 * @group Abilities
 */
class RegisterAbilityTest extends TestCase {

	protected $useApi = false;

	public function set_up() {
		parent::set_up();

		if ( version_compare( $GLOBALS['wp_version'], '6.9', '<' ) ) {
			$this->markTestSkipped( 'WordPress 6.9+ required for Abilities API.' );
		}
	}

	public function tear_down() {
		wp_set_current_user( 0 );
		remove_all_filters( 'imagify_capacity' );
		parent::tear_down();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( array $config, array $expected ): void {
		$this->set_up_user( $config['has_permission'] );

		$ability = wp_get_ability( 'imagify/optimize-media' );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		$result = $ability->execute( $config['args'] ?? [] );

		if ( $expected['is_error'] ) {
			$this->assertInstanceOf( 'WP_Error', $result, 'Should return WP_Error when user lacks permission.' );
			return;
		}

		$this->assertIsArray( $result, 'Should return array when user has permission.' );

		foreach ( $expected['has_keys'] as $key ) {
			$this->assertArrayHasKey( $key, $result, "Result should contain key '{$key}'." );
		}
	}

	/**
	 * Tests that all response fields have the correct types when an invalid media ID (0) is given.
	 */
	public function testErrorResponseFieldTypesOnInvalidMediaId(): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$ability = wp_get_ability( 'imagify/optimize-media' );
		$result  = $ability->execute( [ 'media_id' => 0 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['original_size'] );
		$this->assertNull( $result['optimized_size'] );
		$this->assertNull( $result['savings_percent'] );
		$this->assertIsString( $result['error_message'] );
		$this->assertNotEmpty( $result['error_message'] );
	}

	/**
	 * Tests that an error response is returned when a non-attachment post ID is passed.
	 */
	public function testErrorResponseForNonAttachmentPostId(): void {
		$post_id = self::factory()->post->create();
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$ability = wp_get_ability( 'imagify/optimize-media' );
		$result  = $ability->execute( [ 'media_id' => $post_id ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertIsString( $result['error_message'] );
		$this->assertNotEmpty( $result['error_message'] );
		$this->assertNull( $result['original_size'] );
		$this->assertNull( $result['optimized_size'] );
		$this->assertNull( $result['savings_percent'] );
	}

	/**
	 * Test that the imagify_capacity filter is honoured for an administrator.
	 *
	 * An admin user would normally pass the permission check. When a filter
	 * replaces the resolved capacity with 'do_not_allow' (a reserved WordPress
	 * capability no user can be granted), the ability must return a WP_Error.
	 *
	 * @return void
	 */
	public function testShouldDenyAccessWhenCapacityFilterReturnsFalse(): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		add_filter( 'imagify_capacity', static function () { return 'do_not_allow'; } );

		$ability = wp_get_ability( 'imagify/optimize-media' );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		$result = $ability->execute( [ 'media_id' => 0 ] );

		$this->assertInstanceOf( 'WP_Error', $result, 'Should return WP_Error when imagify_capacity filter denies access.' );
	}

	/**
	 * Creates and sets a current user with or without the manage_options capability.
	 */
	private function set_up_user( bool $has_permission ): void {
		$user_id = self::factory()->user->create( [
			'role' => $has_permission ? 'administrator' : 'subscriber',
		] );
		wp_set_current_user( $user_id );
	}
}
