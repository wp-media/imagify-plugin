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

	/**
	 * Tests that the registered ability's input_schema includes the new
	 * `media_filename` and `media_url` identifier properties.
	 */
	public function testInputSchemaIncludesMediaFilenameAndUrlProperties(): void {
		$ability = wp_get_ability( 'imagify/restore-media' );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		$input_schema = $ability->get_input_schema();

		$this->assertArrayHasKey( 'media_filename', $input_schema['properties'] );
		$this->assertSame( 'string', $input_schema['properties']['media_filename']['type'] );

		$this->assertArrayHasKey( 'media_url', $input_schema['properties'] );
		$this->assertSame( 'string', $input_schema['properties']['media_url']['type'] );
		$this->assertSame( 'uri', $input_schema['properties']['media_url']['format'] );

		$required = $input_schema['required'] ?? [];
		$this->assertNotContains( 'media_id', $required );
	}

	private function set_up_user( bool $has_permission ): void {
		$user_id = self::factory()->user->create( [
			'role' => $has_permission ? 'administrator' : 'subscriber',
		] );
		wp_set_current_user( $user_id );
	}
}
