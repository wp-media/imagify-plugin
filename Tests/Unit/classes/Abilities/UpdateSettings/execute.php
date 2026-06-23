<?php

namespace Imagify\Tests\Unit\Abilities\UpdateSettings;

use Imagify\Abilities\UpdateSettings;
use WP_UnitTestCase;

/**
 * Unit tests for UpdateSettings::execute()
 *
 * @covers Imagify\Abilities\UpdateSettings::execute()
 */
class ExecuteTest extends WP_UnitTestCase {

	/**
	 * @var UpdateSettings
	 */
	private $ability;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->ability = new UpdateSettings();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Test partial update: only one key changes.
	 */
	public function test_partial_update_single_key() {
		$result = $this->ability->execute( [ 'auto_optimize' => 1 ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'updated', $result );
		$this->assertArrayHasKey( 'settings', $result );
		$this->assertContains( 'auto_optimize', $result['updated'] );
	}

	/**
	 * Test partial update: multiple keys.
	 */
	public function test_partial_update_multiple_keys() {
		$result = $this->ability->execute(
			[
				'auto_optimize' => 1,
				'backup'        => 0,
			]
		);

		$this->assertIsArray( $result );
		$this->assertContains( 'auto_optimize', $result['updated'] );
		$this->assertContains( 'backup', $result['updated'] );
	}

	/**
	 * Test empty update returns no changed keys.
	 */
	public function test_empty_update() {
		$result = $this->ability->execute( [] );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result['updated'] );
		$this->assertIsArray( $result['settings'] );
	}

	/**
	 * Test invalid value is rejected.
	 */
	public function test_invalid_value_rejected() {
		$result = $this->ability->execute( [ 'optimization_level' => 99 ] );

		$this->assertInstanceOf( '\WP_Error', $result );
	}

	/**
	 * Test API key update is rejected when constant is defined.
	 */
	public function test_api_key_update_rejected_when_constant_defined() {
		if ( defined( 'IMAGIFY_API_KEY' ) && IMAGIFY_API_KEY ) {
			$result = $this->ability->execute( [ 'api_key' => 'new-key' ] );

			$this->assertInstanceOf( '\WP_Error', $result );
			$this->assertStringContainsString( 'api_key cannot be updated', $result->get_error_message() );
		} else {
			$this->markTestSkipped( 'IMAGIFY_API_KEY constant not defined' );
		}
	}

	/**
	 * Test response shape includes required keys.
	 */
	public function test_response_shape() {
		$result = $this->ability->execute( [ 'auto_optimize' => 1 ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'updated', $result );
		$this->assertArrayHasKey( 'settings', $result );
		$this->assertIsArray( $result['updated'] );
		$this->assertIsArray( $result['settings'] );
	}
}
