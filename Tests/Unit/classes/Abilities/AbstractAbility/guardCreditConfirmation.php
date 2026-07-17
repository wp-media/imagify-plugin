<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\AbstractAbility;

use Brain\Monkey\Functions;
use Imagify\Abilities\AbstractAbility;
use Imagify\Abilities\CreditConsumingAbilityInterface;
use Imagify\Tests\Unit\TestCase;
use Imagify\User\User;
use Mockery;

/**
 * Local contract for the anonymous AbstractAbility test double built by make_ability(),
 * so static analysis knows call_guard() exists without widening AbstractAbility itself.
 */
interface GuardCreditConfirmationTestDouble {
	/**
	 * Public wrapper exposing the protected guard_credit_confirmation() for direct testing.
	 *
	 * @param array    $args Raw input arguments.
	 * @param callable $run  Closure invoked once confirmed.
	 * @return array
	 */
	public function call_guard( array $args, callable $run ): array;
}

/**
 * Tests for \Imagify\Abilities\AbstractAbility::guard_credit_confirmation().
 *
 * `Imagify_Requirements` is a legacy classmap-autoloaded class; it is stubbed via
 * a Mockery alias mock, which requires each test to run in its own process so the
 * real class is never loaded before the alias is registered.
 *
 * @covers \Imagify\Abilities\AbstractAbility::guard_credit_confirmation
 * @group  AbstractAbility
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Test_GuardCreditConfirmation extends TestCase {

	/**
	 * Builds a concrete AbstractAbility test double exposing guard_credit_confirmation()
	 * publicly and implementing CreditConsumingAbilityInterface with a fixed impact estimate.
	 *
	 * @param array     $impact Value returned by get_impact_estimate().
	 * @param User|null $user   User instance returned by fetch_user(); a permissive mock by default.
	 * @return AbstractAbility&GuardCreditConfirmationTestDouble
	 */
	private function make_ability( array $impact = [
		'unit'  => 'image',
		'count' => 1,
		'label' => 'this media',
	], ?User $user = null ) {
		if ( null === $user ) {
			/** @var User&Mockery\LegacyMockInterface $user */
			$user                   = Mockery::mock( User::class )->shouldIgnoreMissing();
			$user->next_date_update = '2026-08-01';
		}

		return new class( $impact, $user ) extends AbstractAbility implements CreditConsumingAbilityInterface, GuardCreditConfirmationTestDouble {
			/**
			 * Fixed impact estimate returned by get_impact_estimate().
			 *
			 * @var array
			 */
			private $impact;

			/**
			 * User instance returned by fetch_user().
			 *
			 * @var User
			 */
			private $user;

			/**
			 * Constructor.
			 *
			 * @param array $impact Value returned by get_impact_estimate().
			 * @param User  $user   User instance returned by fetch_user().
			 */
			public function __construct( array $impact, User $user ) {
				$this->impact = $impact;
				$this->user   = $user;
			}

			/**
			 * Returns the test ability slug.
			 *
			 * @return string
			 */
			public function get_id(): string {
				return 'test/ability';
			}

			/**
			 * Returns the test ability label.
			 *
			 * @return string
			 */
			public function get_name(): string {
				return 'Test ability';
			}

			/**
			 * Always allows execution for this test double.
			 *
			 * @return bool
			 */
			protected function has_permission(): bool {
				return true;
			}

			/**
			 * No-op: this test double never registers with the WP Abilities API.
			 *
			 * @return void
			 */
			public function register(): void {}

			/**
			 * Unused by these tests; guard_credit_confirmation() is exercised via call_guard().
			 *
			 * @param array $args Raw input arguments.
			 * @return array
			 */
			public function execute( array $args = [] ): array {
				return [];
			}

			/**
			 * Returns the fixed impact estimate passed to the constructor.
			 *
			 * @param array $args Raw input arguments.
			 * @return array
			 */
			public function get_impact_estimate( array $args ): array {
				return $this->impact;
			}

			/**
			 * Returns the User instance passed to the constructor.
			 *
			 * @return User
			 */
			protected function fetch_user(): User {
				return $this->user;
			}

			/**
			 * Public wrapper so tests can call the protected guard directly.
			 *
			 * @param array    $args Raw input arguments.
			 * @param callable $run  Closure invoked once confirmed.
			 * @return array
			 */
			public function call_guard( array $args, callable $run ): array {
				return $this->guard_credit_confirmation( $args, $run );
			}
		};
	}

	/**
	 * Stubs the translation and Imagify_Requirements static calls.
	 *
	 * @param bool $api_key_valid Value returned by is_api_key_valid().
	 * @param bool $over_quota    Value returned by is_over_quota().
	 */
	private function stub_requirements( bool $api_key_valid, bool $over_quota ): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'imagify_get_external_url' )->justReturn( 'https://example.com/subscription' );

		$mock = Mockery::mock( 'alias:Imagify_Requirements' );
		$mock->shouldReceive( 'is_api_key_valid' )->andReturn( $api_key_valid );
		$mock->shouldReceive( 'is_over_quota' )->andReturn( $over_quota );
	}

	/**
	 * Case 1: invalid API key returns `invalid_api_key` and never invokes $run, even before the quota check.
	 */
	public function testReturnsInvalidApiKeyResponseAndNeverCallsRunWhenApiKeyIsInvalid(): void {
		$this->stub_requirements( false, false );

		$run_called = false;
		$ability    = $this->make_ability();

		$result = $ability->call_guard(
			[ 'confirm' => true ],
			function () use ( &$run_called ) {
				$run_called = true;
				return [ 'status' => 'success' ];
			}
		);

		$this->assertSame( 'invalid_api_key', $result['status'] );
		$this->assertFalse( $run_called );
	}

	/**
	 * Case 2: over-quota returns `insufficient_quota` and never invokes $run.
	 */
	public function testReturnsInsufficientQuotaResponseAndNeverCallsRunWhenOverQuota(): void {
		$this->stub_requirements( true, true );

		$run_called = false;
		$ability    = $this->make_ability();

		$result = $ability->call_guard(
			[ 'confirm' => true ],
			function () use ( &$run_called ) {
				$run_called = true;
				return [ 'status' => 'success' ];
			}
		);

		$this->assertSame( 'insufficient_quota', $result['status'] );
		$this->assertFalse( $run_called );
		$this->assertArrayHasKey( 'next_date_update', $result );
		$this->assertArrayHasKey( 'upgrade_url', $result );
	}

	/**
	 * Case 3: missing/false `confirm` returns `confirmation_required` and never invokes $run.
	 */
	public function testReturnsConfirmationRequiredResponseAndNeverCallsRunWhenConfirmIsMissing(): void {
		$this->stub_requirements( true, false );

		$run_called = false;
		$ability    = $this->make_ability(
			[
				'unit'  => 'image',
				'count' => 3,
				'total' => 10,
				'label' => 'unoptimized images',
			]
		);

		$result = $ability->call_guard(
			[],
			function () use ( &$run_called ) {
				$run_called = true;
				return [ 'status' => 'success' ];
			}
		);

		$this->assertSame( 'confirmation_required', $result['status'] );
		$this->assertFalse( $run_called );
		$this->assertSame(
			[
				'unit'  => 'image',
				'count' => 3,
				'total' => 10,
			],
			$result['impact']
		);
		$this->assertSame( [ 'confirm' => true ], $result['confirm_with'] );
	}

	/**
	 * `confirm: "true"` (string, not boolean) must not be treated as confirmed.
	 */
	public function testReturnsConfirmationRequiredWhenConfirmIsStringNotBoolean(): void {
		$this->stub_requirements( true, false );

		$ability = $this->make_ability();

		$result = $ability->call_guard(
			[ 'confirm' => 'true' ],
			function () {
				return [ 'status' => 'success' ];
			}
		);

		$this->assertSame( 'confirmation_required', $result['status'] );
	}

	/**
	 * Case 4: `confirm: true` + quota OK + valid key invokes $run exactly once and passes its result through unchanged.
	 */
	public function testInvokesRunExactlyOnceAndReturnsItsResultUnchangedWhenConfirmedAndQuotaOk(): void {
		$this->stub_requirements( true, false );

		$call_count = 0;
		$ability    = $this->make_ability();

		$result = $ability->call_guard(
			[
				'confirm'  => true,
				'media_id' => 42,
			],
			function ( array $a ) use ( &$call_count ) {
				$call_count++;
				return [
					'status'      => 'success',
					'echoed_args' => $a,
				];
			}
		);

		$this->assertSame( 1, $call_count );
		$this->assertSame( 'success', $result['status'] );
		$this->assertSame(
			[
				'confirm'  => true,
				'media_id' => 42,
			],
			$result['echoed_args']
		);
	}
}
