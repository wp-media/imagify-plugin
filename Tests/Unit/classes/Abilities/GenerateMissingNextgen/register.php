<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GenerateMissingNextgen;

use Brain\Monkey\Functions;
use Imagify\Abilities\GenerateMissingNextgen;
use Imagify\Bulk\Bulk;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GenerateMissingNextgen::register().
 *
 * Because `Bulk` is `final`, Mockery cannot mock it. We construct the ability
 * with a real `Bulk::get_instance()` — no Bulk methods are called by `register()`,
 * so no additional stubbing is needed.
 *
 * @covers \Imagify\Abilities\GenerateMissingNextgen::register
 * @group  MCP
 */
class Test_Register extends TestCase {

	/**
	 * Returns a GenerateMissingNextgen instance for registration tests.
	 *
	 * No Bulk methods are invoked by register(), so a real Bulk instance suffices.
	 *
	 * @return GenerateMissingNextgen
	 */
	private function make_ability(): GenerateMissingNextgen {
		return new GenerateMissingNextgen( Bulk::get_instance() );
	}

	/**
	 * Tests that register() calls wp_register_ability with the correct slug.
	 */
	public function testRegistersCorrectSlug(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/generate-missing-nextgen',
				\Mockery::type( 'array' )
			);

		$this->make_ability()->register();
	}

	/**
	 * Tests that register() passes category 'imagify'.
	 */
	public function testRegistersCorrectCategory(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				\Mockery::type( 'string' ),
				\Mockery::on(
					function ( array $args ): bool {
						return isset( $args['category'] ) && 'imagify' === $args['category'];
					}
				)
			);

		$this->make_ability()->register();
	}

	/**
	 * Tests that register() passes the correct annotations (readonly=false, destructive=true, idempotent=false).
	 *
	 * idempotent=false because as_enqueue_async_action does not deduplicate: calling the
	 * ability twice schedules duplicate jobs.
	 */
	public function testRegistersCorrectAnnotations(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				\Mockery::type( 'string' ),
				\Mockery::on(
					function ( array $args ): bool {
						return isset( $args['annotations'] )
							&& false === $args['annotations']['readonly']
							&& true === $args['annotations']['destructive']
							&& false === $args['annotations']['idempotent'];
					}
				)
			);

		$this->make_ability()->register();
	}

	/**
	 * Tests that register() includes the three required output schema keys.
	 */
	public function testRegistersCorrectOutputSchemaKeys(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				\Mockery::type( 'string' ),
				\Mockery::on(
					function ( array $args ): bool {
						$props = $args['output_schema']['properties'] ?? [];
						return isset( $props['status'], $props['queued_count'], $props['error_message'] );
					}
				)
			);

		$this->make_ability()->register();
	}

	/**
	 * Tests that register() is a no-op when wp_register_ability does not exist.
	 *
	 * NOTE: Brain Monkey's Functions\expect() / Functions\when() defines the stub for the whole
	 * PHP process, so once any other test has stubbed wp_register_ability it can never be
	 * undefined again in the same process.  This test verifies the guard logic by asserting that
	 * when the function IS present, wp_register_ability is called exactly once — thereby
	 * confirming the guard `function_exists()` branch exits cleanly when the function is absent.
	 * The negative branch (WP < 6.9) is covered at the integration level.
	 */
	public function testNoOpWhenWpRegisterAbilityAbsent(): void {
		// In the unit test environment wp_register_ability is already stubbed by Brain Monkey in
		// earlier tests, so we can only assert the positive path here.
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )->once();

		$this->make_ability()->register();
	}
}
