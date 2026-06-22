<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\MCP\AbilitiesSubscriber;

use Brain\Monkey\Functions;
use Imagify\Abilities\AbilitiesInterface;
use Imagify\MCP\AbilitiesSubscriber;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\MCP\AbilitiesSubscriber::register_abilities().
 *
 * @covers \Imagify\MCP\AbilitiesSubscriber::register_abilities
 * @group  MCP
 */
class Test_RegisterAbilities extends TestCase {

	/**
	 * Tests that register_abilities() with zero abilities does not call register() on anything
	 * (foundation state — empty loop).
	 *
	 * Note: wp_register_ability is defined via php-stubs in the unit test environment, so
	 * the function_exists() guard always passes. This test verifies the empty loop is safe.
	 */
	public function testNoOpsWithZeroAbilitiesFoundationState(): void {
		// Assert that wp_register_ability is never invoked when no abilities are injected.
		// Brain Monkey's expect()->never() is itself the real assertion here.
		Functions\expect( 'wp_register_ability' )->never();

		$subscriber = new AbilitiesSubscriber();
		$subscriber->register_abilities();
	}

	/**
	 * Tests that register_abilities() calls register() on each injected ability
	 * when wp_register_ability exists.
	 */
	public function testCallsRegisterOnEachInjectedAbility(): void {
		Functions\when( 'wp_register_ability' )->justReturn();

		$ability1 = Mockery::mock( AbilitiesInterface::class );
		$ability1->shouldReceive( 'register' )->once();

		$ability2 = Mockery::mock( AbilitiesInterface::class );
		$ability2->shouldReceive( 'register' )->once();

		$subscriber = new AbilitiesSubscriber( $ability1, $ability2 );
		$subscriber->register_abilities();
	}

	/**
	 * Tests that register_abilities() with zero injected abilities does not call
	 * wp_register_ability even though the function exists.
	 *
	 * The WP Abilities API stubs define wp_register_ability in the test environment,
	 * so we verify the empty loop produces no calls — not that the guard skips it.
	 */
	public function testWithZeroAbilitiesDoesNotCallWpRegisterAbility(): void {
		// Verify wp_register_ability is never invoked when no abilities are injected.
		// Brain Monkey's expect()->never() is itself the real assertion.
		Functions\expect( 'wp_register_ability' )->never();

		$subscriber = new AbilitiesSubscriber();
		$subscriber->register_abilities();
	}
}
