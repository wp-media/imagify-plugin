<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetSettings;

use Brain\Monkey\Actions;
use Imagify\Abilities\GetSettings;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GetSettings::execute().
 *
 * Uses a testable subclass to avoid re-mocking the legacy Imagify_Options
 * singleton (the real class is already loaded by the unit test bootstrap).
 *
 * @covers \Imagify\Abilities\GetSettings::execute
 * @group  GetSettings
 */
class Test_Execute extends TestCase {

	/**
	 * Tests that execute() fires the imagify_mcp_ability_executed action.
	 */
	public function testFiresAbilityExecutedHook(): void {
		Actions\expectDone( 'imagify_mcp_ability_executed' )->once();

		$this->make_testable_ability(
			[
				'optimization_level' => 1,
				'api_key'            => 'k',
				'version'            => '1',
			]
		)->execute();
	}

	/**
	 * Tests that execute() returns an array.
	 */
	public function testReturnsArray(): void {
		$ability = $this->make_testable_ability(
			[
				'optimization_level' => 2,
				'auto_optimize'      => 1,
				'api_key'            => 'test-key',
				'version'            => '2.2.9',
			]
		);

		$this->assertIsArray( $ability->execute() );
	}

	/**
	 * Tests that execute() returns a non-empty result containing expected keys.
	 */
	public function testReturnsExpectedKeys(): void {
		$ability = $this->make_testable_ability(
			[
				'optimization_level' => 2,
				'auto_optimize'      => 1,
				'backup'             => 1,
				'resize_larger'      => 0,
				'api_key'            => 'test-key',
				'version'            => '2.2.9',
			]
		);

		$result = $ability->execute();

		$this->assertArrayHasKey( 'optimization_level', $result );
		$this->assertArrayHasKey( 'auto_optimize', $result );
		$this->assertArrayHasKey( 'backup', $result );
	}

	/**
	 * Tests that execute() strips the internal 'version' key.
	 */
	public function testStripsVersionKey(): void {
		$ability = $this->make_testable_ability(
			[
				'optimization_level' => 2,
				'api_key'            => 'test-key',
				'version'            => '2.2.9',
			]
		);

		$this->assertArrayNotHasKey( 'version', $ability->execute() );
	}

	/**
	 * Tests that execute() omits the api_key to prevent credential exposure.
	 */
	public function testOmitsApiKey(): void {
		$ability = $this->make_testable_ability(
			[
				'optimization_level' => 2,
				'backup'             => 1,
				'api_key'            => 'super-secret-api-key',
				'version'            => '2.2.9',
			]
		);

		$this->assertArrayNotHasKey( 'api_key', $ability->execute() );
	}

	/**
	 * Tests that execute() returns a non-empty result when settings exist.
	 */
	public function testReturnsNonEmptyResult(): void {
		$ability = $this->make_testable_ability(
			[
				'optimization_level' => 2,
				'auto_optimize'      => 1,
				'api_key'            => 'test-key',
				'version'            => '2.2.9',
			]
		);

		$this->assertNotEmpty( $ability->execute() );
	}

	/**
	 * Build a testable GetSettings instance with injected raw settings.
	 *
	 * @param array<string, mixed> $raw_settings Raw options to inject.
	 * @return GetSettings
	 */
	private function make_testable_ability( array $raw_settings ): GetSettings {
		return new class( $raw_settings ) extends GetSettings {
			/**
			 * Injected raw settings for testing.
			 *
			 * @var array<string, mixed>
			 */
			private $raw;

			/**
			 * Constructor.
			 *
			 * @param array<string, mixed> $raw Raw settings to inject.
			 */
			public function __construct( array $raw ) {
				$this->raw = $raw;
			}

			/**
			 * Return the injected settings instead of calling the real singleton.
			 *
			 * @return array<string, mixed>
			 */
			protected function fetch_raw_settings(): array {
				return $this->raw;
			}
		};
	}
}
