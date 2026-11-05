<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Core;

use Brain\Monkey\Functions;
use Imagify\Imagifybeat\Core;
use Imagify\Tests\Unit\TestCase;
use RuntimeException;

/**
 * Tests for \Imagify\Imagifybeat\Core::current_user_can_use_imagifybeat() and the rejection in core_handler().
 *
 * @covers \Imagify\Imagifybeat\Core::current_user_can_use_imagifybeat
 * @covers \Imagify\Imagifybeat\Core::core_handler
 * @group  Imagifybeat
 */
class CurrentUserCanUseImagifybeatTest extends TestCase {
	/**
	 * Test: the user can use Imagifybeat with any of the capabilities of the screens that poll it.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The allowed "context:describer" pairs.
	 * @param bool  $expected Whether the user can use Imagifybeat.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->mockContexts( $config['allowed'] );

		$this->assertSame( $expected, Core::get_instance()->current_user_can_use_imagifybeat() );
	}

	/**
	 * Test: a user who can't use Imagifybeat is rejected before the nonce is checked or refreshed.
	 */
	public function testShouldRejectBeforeCheckingTheNonce(): void {
		$this->mockContexts( [] );

		$_POST['_nonce'] = 'x';

		Functions\expect( 'wp_send_json_error' )
			->once()
			->with( null, 403 )
			->andThrow( new RuntimeException( 'rejected' ) );
		Functions\expect( 'wp_verify_nonce' )->never();
		Functions\expect( 'wp_create_nonce' )->never();

		try {
			$this->expectException( RuntimeException::class );
			Core::get_instance()->core_handler();
		} finally {
			unset( $_POST['_nonce'] );
		}
	}

	/**
	 * Mock imagify_get_context(): each context allows the given "context:describer" pairs.
	 *
	 * @param array $allowed The allowed "context:describer" pairs.
	 */
	private function mockContexts( array $allowed ): void {
		Functions\when( 'imagify_get_context' )->alias(
			function ( $name ) use ( $allowed ) {
				return new class( $name, $allowed ) {
					private $name;
					private $allowed;

					public function __construct( $name, array $allowed ) {
						$this->name    = $name;
						$this->allowed = $allowed;
					}

					public function current_user_can( $describer ): bool {
						return in_array( "{$this->name}:{$describer}", $this->allowed, true );
					}
				};
			}
		);
	}
}
