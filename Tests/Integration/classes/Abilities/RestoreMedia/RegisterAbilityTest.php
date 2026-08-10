<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\RestoreMedia;

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

		$ability = wp_get_ability( 'imagify/restore-media' );

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

	private function set_up_user( bool $has_permission ): void {
		$user_id = self::factory()->user->create( [
			'role' => $has_permission ? 'administrator' : 'subscriber',
		] );
		wp_set_current_user( $user_id );
	}

	/**
	 * The filename and URL inputs must be registered, and no input may be
	 * required on its own since the three identifiers are interchangeable.
	 */
	public function testShouldRegisterFilenameAndUrlInputs(): void {
		$ability = wp_get_ability( 'imagify/restore-media' );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		$input_schema = $ability->get_input_schema();

		$this->assertArrayHasKey( 'media_id', $input_schema['properties'] );
		$this->assertArrayHasKey( 'media_filename', $input_schema['properties'] );
		$this->assertArrayHasKey( 'media_url', $input_schema['properties'] );
		$this->assertArrayNotHasKey( 'required', $input_schema );
	}

	/**
	 * A resolvable file name reaches the restore logic — it fails on the media
	 * not being optimized, which an unresolved file name never gets to.
	 */
	public function testShouldResolveFilenameBeforeRestoring(): void {
		self::factory()->attachment->create(
			[
				'file'           => '2026/08/restore-by-name.jpg',
				'post_mime_type' => 'image/jpeg',
			]
		);
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$ability = wp_get_ability( 'imagify/restore-media' );

		$resolved = $ability->execute( [ 'media_filename' => 'restore-by-name.jpg' ] );
		$unknown  = $ability->execute( [ 'media_filename' => 'not-in-library.jpg' ] );

		$this->assertSame( 'error', $resolved['status'] );
		$this->assertStringContainsString( 'not optimized', $resolved['error_message'] );

		$this->assertSame( 'error', $unknown['status'] );
		$this->assertStringContainsString( 'not-in-library.jpg', $unknown['error_message'] );
	}

	/**
	 * With no identifier at all the caller gets an actionable message naming the
	 * three accepted inputs.
	 */
	public function testShouldReturnErrorWhenNoIdentifierIsGiven(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$result = wp_get_ability( 'imagify/restore-media' )->execute( [] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertStringContainsString( 'media_filename', $result['error_message'] );
	}
}
