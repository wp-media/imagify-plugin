<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\UpdateSettings;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Imagify\Abilities\UpdateSettings;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Abilities\UpdateSettings::execute().
 *
 * Uses a testable subclass to avoid re-mocking the legacy Imagify_Options
 * singleton (the real class is already loaded by the unit test bootstrap).
 *
 * @covers \Imagify\Abilities\UpdateSettings::execute
 * @group  UpdateSettings
 */
class Test_Execute extends TestCase {

	/**
	 * Default set of known setting keys used across most tests.
	 *
	 * @var array<string, mixed>
	 */
	private $defaults = [
		'optimization_level'  => 1,
		'optimization_format' => 'off',
		'auto_optimize'       => 0,
		'api_key'             => '',
		'version'             => '',
	];

	/**
	 * Tests that execute() fires the imagify_mcp_ability_executed action.
	 */
	public function testFiresAbilityExecutedHook(): void {
		Functions\when( '__' )->returnArg();
		Actions\expectDone( 'imagify_mcp_ability_executed' )->once();

		$ability = $this->make_testable_ability(
			$this->defaults,
			$this->defaults,
			$this->defaults,
			true
		);

		$ability->execute( [] );
	}

	/**
	 * Tests that execute() returns WP_Error with code imagify_unknown_setting for an unknown key.
	 */
	public function testReturnsWpErrorForUnknownKey(): void {
		Functions\when( '__' )->returnArg();

		$ability = $this->make_testable_ability(
			$this->defaults,
			$this->defaults,
			$this->defaults,
			false
		);

		$result = $ability->execute( [ 'bad_key' => 'val' ] );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'imagify_unknown_setting', $result->get_error_code() );
	}

	/**
	 * Tests that execute() returns WP_Error with code imagify_invalid_value for an invalid optimization_level.
	 */
	public function testReturnsWpErrorForInvalidOptimizationLevel(): void {
		Functions\when( '__' )->returnArg();

		$ability = $this->make_testable_ability(
			$this->defaults,
			$this->defaults,
			$this->defaults,
			false
		);

		$result = $ability->execute( [ 'optimization_level' => 99 ] );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'imagify_invalid_value', $result->get_error_code() );
	}

	/**
	 * Tests that execute() returns WP_Error for an invalid optimization_format value.
	 */
	public function testReturnsWpErrorForInvalidOptimizationFormat(): void {
		Functions\when( '__' )->returnArg();

		$ability = $this->make_testable_ability(
			$this->defaults,
			$this->defaults,
			$this->defaults,
			false
		);

		$result = $ability->execute( [ 'optimization_format' => 'gif' ] );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'imagify_invalid_value', $result->get_error_code() );
	}

	/**
	 * Tests that execute() blocks api_key update when IMAGIFY_API_KEY constant is defined.
	 */
	public function testBlocksApiKeyWhenConstantDefined(): void {
		if ( defined( 'IMAGIFY_API_KEY' ) ) {
			$this->markTestSkipped( 'IMAGIFY_API_KEY is already defined; cannot test constant guard.' );
		}

		Functions\when( '__' )->returnArg();

		define( 'IMAGIFY_API_KEY', 'constant-value' );

		$ability = $this->make_testable_ability(
			$this->defaults,
			$this->defaults,
			$this->defaults,
			false
		);

		$result = $ability->execute( [ 'api_key' => 'new-key' ] );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'imagify_api_key_immutable', $result->get_error_code() );
	}

	/**
	 * Tests that execute() returns WP_Error with code imagify_unknown_setting when version is supplied.
	 */
	public function testRejectsVersionKey(): void {
		Functions\when( '__' )->returnArg();

		$ability = $this->make_testable_ability(
			$this->defaults,
			$this->defaults,
			$this->defaults,
			false
		);

		$result = $ability->execute( [ 'version' => '2.3.0' ] );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'imagify_unknown_setting', $result->get_error_code() );
	}

	/**
	 * Tests that execute() returns an array with 'updated' and 'settings' keys on valid input.
	 */
	public function testResponseShapeContainsUpdatedAndSettings(): void {
		Functions\when( '__' )->returnArg();

		$after = array_merge( $this->defaults, [ 'auto_optimize' => 1 ] );

		$ability = $this->make_testable_ability(
			$this->defaults,
			$this->defaults,
			$after
		);

		$result = $ability->execute( [ 'auto_optimize' => 1 ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'updated', $result );
		$this->assertArrayHasKey( 'settings', $result );
	}

	/**
	 * Tests that the returned 'settings' array excludes 'version' and 'api_key'.
	 */
	public function testSettingsExcludesVersionAndApiKey(): void {
		Functions\when( '__' )->returnArg();

		$after = array_merge( $this->defaults, [ 'auto_optimize' => 1 ] );

		$ability = $this->make_testable_ability(
			$this->defaults,
			$this->defaults,
			$after
		);

		$result = $ability->execute( [ 'auto_optimize' => 1 ] );

		$this->assertIsArray( $result );
		$this->assertArrayNotHasKey( 'version', $result['settings'] );
		$this->assertArrayNotHasKey( 'api_key', $result['settings'] );
	}

	/**
	 * Tests that 'updated' lists keys whose value changed between before and after.
	 */
	public function testUpdatedListsChangedKeys(): void {
		Functions\when( '__' )->returnArg();

		$after = array_merge( $this->defaults, [ 'auto_optimize' => 1 ] );

		$ability = $this->make_testable_ability(
			$this->defaults,
			$this->defaults,
			$after
		);

		$result = $ability->execute( [ 'auto_optimize' => 1 ] );

		$this->assertContains( 'auto_optimize', $result['updated'] );
	}

	/**
	 * Tests that calling execute() with empty args returns an empty 'updated' array.
	 */
	public function testEmptyArgsReturnsEmptyUpdated(): void {
		Functions\when( '__' )->returnArg();

		$ability = $this->make_testable_ability(
			$this->defaults,
			$this->defaults,
			$this->defaults,
			true
		);

		$result = $ability->execute( [] );

		$this->assertIsArray( $result );
		$this->assertSame( [], $result['updated'] );
	}

	/**
	 * Build a testable UpdateSettings instance backed by a Mockery mock of Imagify_Options.
	 *
	 * @param array<string, mixed> $defaults Setting keys that are considered valid.
	 * @param array<string, mixed> $before   State returned by get_all() before set().
	 * @param array<string, mixed> $after    State returned by get_all() after set().
	 * @param bool                 $expect_set Whether set() is expected to be called once.
	 * @return UpdateSettings
	 */
	private function make_testable_ability(
		array $defaults,
		array $before,
		array $after,
		bool $expect_set = true
	): UpdateSettings {
		$mock = Mockery::mock( 'Imagify_Options' );
		$mock->shouldReceive( 'get_default_values' )->andReturn( $defaults );
		$mock->shouldReceive( 'get_all' )->andReturnValues( [ $before, $after ] );

		if ( $expect_set ) {
			$mock->shouldReceive( 'set' )->once();
		} else {
			$mock->shouldReceive( 'set' )->never();
		}

		return new class( $mock ) extends UpdateSettings {
			/**
			 * Mocked Imagify_Options instance.
			 *
			 * @var object
			 */
			private $opts;

			/**
			 * Constructor.
			 *
			 * @param object $opts Mocked Imagify_Options instance.
			 */
			public function __construct( $opts ) {
				$this->opts = $opts;
			}

			/**
			 * Return the injected mock instead of the real singleton.
			 *
			 * @return \Imagify_Options
			 */
			protected function fetch_options_instance(): \Imagify_Options {
				return $this->opts;
			}
		};
	}
}
