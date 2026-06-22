<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetSettings;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetSettings;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Abilities\GetSettings::execute().
 *
 * @covers \Imagify\Abilities\GetSettings::execute
 * @group  GetSettings
 */
class Test_Execute extends TestCase {

	/**
	 * Builds a mock Imagify_Options instance and injects it as the singleton.
	 *
	 * @param array<string, mixed> $return_values The values that get_all() should return.
	 * @return \Mockery\MockInterface
	 */
	private function mockImagifyOptions( array $return_values ): \Mockery\MockInterface {
		$mock = Mockery::mock( 'overload:Imagify_Options' );
		$mock->shouldReceive( 'get_all' )
			->once()
			->andReturn( $return_values );
		$mock->shouldReceive( 'get_instance' )
			->andReturn( $mock );

		return $mock;
	}

	/**
	 * Tests that execute() returns an array.
	 */
	public function testReturnsArray(): void {
		$options = [
			'optimization_level' => 2,
			'auto_optimize'      => 1,
			'backup'             => 1,
			'api_key'            => 'test-key-123',
			'version'            => '2.2.9',
		];

		$mock = Mockery::mock( 'overload:Imagify_Options' );
		$mock->shouldReceive( 'get_instance' )->andReturn( $mock );
		$mock->shouldReceive( 'get_all' )->andReturn( $options );

		$ability = new GetSettings();
		$result  = $ability->execute();

		$this->assertIsArray( $result );
	}

	/**
	 * Tests that execute() returns a non-empty array with expected keys.
	 */
	public function testReturnsExpectedKeys(): void {
		$options = [
			'optimization_level' => 2,
			'auto_optimize'      => 1,
			'backup'             => 1,
			'resize_larger'      => 0,
			'api_key'            => 'test-key-123',
			'version'            => '2.2.9',
		];

		$mock = Mockery::mock( 'overload:Imagify_Options' );
		$mock->shouldReceive( 'get_instance' )->andReturn( $mock );
		$mock->shouldReceive( 'get_all' )->andReturn( $options );

		$ability = new GetSettings();
		$result  = $ability->execute();

		$this->assertArrayHasKey( 'optimization_level', $result );
		$this->assertArrayHasKey( 'auto_optimize', $result );
		$this->assertArrayHasKey( 'backup', $result );
	}

	/**
	 * Tests that execute() strips the internal 'version' key.
	 */
	public function testStripsVersionKey(): void {
		$options = [
			'optimization_level' => 2,
			'auto_optimize'      => 1,
			'api_key'            => 'test-key-123',
			'version'            => '2.2.9',
		];

		$mock = Mockery::mock( 'overload:Imagify_Options' );
		$mock->shouldReceive( 'get_instance' )->andReturn( $mock );
		$mock->shouldReceive( 'get_all' )->andReturn( $options );

		$ability = new GetSettings();
		$result  = $ability->execute();

		$this->assertArrayNotHasKey( 'version', $result );
	}

	/**
	 * Tests that execute() omits the api_key to prevent credential exposure.
	 */
	public function testOmitsApiKey(): void {
		$options = [
			'optimization_level' => 2,
			'auto_optimize'      => 1,
			'backup'             => 1,
			'api_key'            => 'super-secret-api-key',
			'version'            => '2.2.9',
		];

		$mock = Mockery::mock( 'overload:Imagify_Options' );
		$mock->shouldReceive( 'get_instance' )->andReturn( $mock );
		$mock->shouldReceive( 'get_all' )->andReturn( $options );

		$ability = new GetSettings();
		$result  = $ability->execute();

		$this->assertArrayNotHasKey( 'api_key', $result );
	}

	/**
	 * Tests that execute() returns a non-empty result.
	 */
	public function testReturnsNonEmptyResult(): void {
		$options = [
			'optimization_level' => 2,
			'auto_optimize'      => 1,
			'backup'             => 1,
			'api_key'            => 'test-key',
			'version'            => '2.2.9',
		];

		$mock = Mockery::mock( 'overload:Imagify_Options' );
		$mock->shouldReceive( 'get_instance' )->andReturn( $mock );
		$mock->shouldReceive( 'get_all' )->andReturn( $options );

		$ability = new GetSettings();
		$result  = $ability->execute();

		$this->assertNotEmpty( $result );
	}
}
