<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\GetMediaStatus;

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
		parent::tear_down();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( array $config, array $expected ): void {
		$this->set_up_user( $config['has_permission'] );

		$ability = wp_get_ability( 'imagify/get-media-status' );

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

	public function testErrorResponseFieldTypesOnInvalidMediaId(): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$ability = wp_get_ability( 'imagify/get-media-status' );
		$result  = $ability->execute( [ 'media_id' => 0 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertIsString( $result['error_message'] );
		$this->assertNotEmpty( $result['error_message'] );
		$this->assertNull( $result['optimization_level'] );
		$this->assertIsInt( $result['original_size'] );
		$this->assertIsInt( $result['optimized_size'] );
		$this->assertIsBool( $result['webp_available'] );
		$this->assertIsBool( $result['avif_available'] );
	}

	public function testSuccessResponseFieldTypesForUnoptimizedAttachment(): void {
		$attachment_id = self::factory()->attachment->create();
		$user_id       = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$ability = wp_get_ability( 'imagify/get-media-status' );
		$result  = $ability->execute( [ 'media_id' => $attachment_id ] );

		$this->assertIsString( $result['status'] );
		$this->assertContains( $result['status'], [ 'success', 'error', 'unoptimized' ] );
		$this->assertIsInt( $result['original_size'] );
		$this->assertIsInt( $result['optimized_size'] );
		$this->assertGreaterThanOrEqual( 0, $result['original_size'] );
		$this->assertGreaterThanOrEqual( 0, $result['optimized_size'] );
		$this->assertIsBool( $result['webp_available'] );
		$this->assertIsBool( $result['avif_available'] );
	}

	private function set_up_user( bool $has_permission ): void {
		$user_id = self::factory()->user->create( [
			'role' => $has_permission ? 'administrator' : 'subscriber',
		] );
		wp_set_current_user( $user_id );
	}
}
