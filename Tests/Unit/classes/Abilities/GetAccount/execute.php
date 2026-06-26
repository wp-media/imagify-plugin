<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetAccount;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetAccount;
use Imagify\Tests\Unit\TestCase;
use Imagify\User\User;
use WP_Error;

/**
 * Tests for \Imagify\Abilities\GetAccount::execute().
 *
 * Uses a testable subclass to avoid bootstrapping the full Imagify API layer
 * (which would call get_imagify_user() / get_transient() etc.).
 *
 * @covers \Imagify\Abilities\GetAccount::execute
 * @group  GetAccount
 */
class Test_Execute extends TestCase {

	/**
	 * Tests that execute() returns an array.
	 */
	public function testReturnsArray(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );

		$ability = $this->make_testable_ability();

		$this->assertIsArray( $ability->execute() );
	}

	/**
	 * Tests that execute() returns all 7 expected keys.
	 */
	public function testReturnsExpectedKeys(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );

		$ability = $this->make_testable_ability();
		$result  = $ability->execute();

		$this->assertArrayHasKey( 'plan_label', $result );
		$this->assertArrayHasKey( 'quota', $result );
		$this->assertArrayHasKey( 'consumed_current_month_quota', $result );
		$this->assertArrayHasKey( 'extra_quota', $result );
		$this->assertArrayHasKey( 'extra_quota_consumed', $result );
		$this->assertArrayHasKey( 'next_date_update', $result );
		$this->assertArrayHasKey( 'is_api_key_valid', $result );
	}

	/**
	 * Tests that is_api_key_valid is true when get_error() returns false (no error).
	 */
	public function testIsApiKeyValidTrueWhenNoError(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );

		$stub        = new User();
		$stub->quota = 1000;
		$ability     = $this->make_testable_ability( $stub, false );
		$result      = $ability->execute();

		$this->assertTrue( $result['is_api_key_valid'] );
	}

	/**
	 * Tests that is_api_key_valid is false when get_error() returns a WP_Error.
	 */
	public function testIsApiKeyValidFalseWhenWpError(): void {
		$wp_error = new WP_Error( 'invalid_key', 'Invalid API key.' );

		Functions\when( 'is_wp_error' )->alias(
			function ( $thing ) use ( $wp_error ) {
				return $thing === $wp_error;
			}
		);

		$ability = $this->make_testable_ability( null, $wp_error );
		$result  = $ability->execute();

		$this->assertFalse( $result['is_api_key_valid'] );
	}

	/**
	 * Tests that quota figures in execute() match the injected User values.
	 */
	public function testReturnsQuotaFigures(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );

		$stub                               = new User();
		$stub->quota                        = 500;
		$stub->consumed_current_month_quota = 150;
		$stub->extra_quota                  = 100;
		$stub->extra_quota_consumed         = 50;

		$ability = $this->make_testable_ability( $stub, false );
		$result  = $ability->execute();

		$this->assertSame( 500, $result['quota'] );
		$this->assertSame( 150, $result['consumed_current_month_quota'] );
		$this->assertSame( 100, $result['extra_quota'] );
		$this->assertSame( 50, $result['extra_quota_consumed'] );
	}

	/**
	 * Build a testable GetAccount instance with an injected User stub.
	 *
	 * @param User|null $user      Optional User stub to inject. Defaults to a bare User instance.
	 * @param mixed     $get_error Return value for get_error() on the User stub. Defaults to false.
	 * @return GetAccount
	 */
	private function make_testable_ability( ?User $user = null, $get_error = false ): GetAccount {
		$injected_user = $user ?? new User();

		return new class( $injected_user, $get_error ) extends GetAccount {
			/**
			 * Injected User stub.
			 *
			 * @var User
			 */
			private $injected_user;

			/**
			 * Value to return from the stub User's get_error().
			 *
			 * @var mixed
			 */
			private $error_value;

			/**
			 * Constructor.
			 *
			 * @param User  $user        User stub.
			 * @param mixed $error_value Value returned by get_error().
			 */
			public function __construct( User $user, $error_value ) {
				$this->injected_user = $user;
				$this->error_value   = $error_value;
			}

			/**
			 * Return the injected User stub instead of calling new User().
			 *
			 * @return User
			 */
			protected function fetch_user(): User {
				// Patch get_error() on the real User instance is complex;
				// use a subclass of User to override get_error().
				$error_value = $this->error_value;
				$base_user   = $this->injected_user;

				return new class( $base_user, $error_value ) extends User {
					/**
					 * Pre-set error value.
					 *
					 * @var mixed
					 */
					private $preset_error;

					/**
					 * Constructor.
					 *
					 * @param User  $base         Source User object.
					 * @param mixed $preset_error Value to return from get_error().
					 */
					public function __construct( User $base, $preset_error ) {
						$this->preset_error = $preset_error;

						// Copy public properties from the base user.
						$this->plan_label                   = $base->plan_label;
						$this->quota                        = $base->quota;
						$this->consumed_current_month_quota = $base->consumed_current_month_quota;
						$this->extra_quota                  = $base->extra_quota;
						$this->extra_quota_consumed         = $base->extra_quota_consumed;
						$this->next_date_update             = $base->next_date_update;
					}

					/**
					 * Return the pre-set error value.
					 *
					 * @return mixed
					 */
					public function get_error() {
						return $this->preset_error;
					}

					/**
					 * No-op init to prevent real API calls.
					 *
					 * @return void
					 */
					public function init_user() {
						// No-op — properties are pre-set in constructor.
					}
				};
			}
		};
	}
}
