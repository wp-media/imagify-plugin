<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\CLI;

use Imagify\Bulk\Bulk;
use Imagify\CLI\RestoreCommand;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\CLI\RestoreCommand.
 *
 * @covers \Imagify\CLI\RestoreCommand
 * @group  RestoreCommand
 * @since  2.3
 */
class RestoreCommandTest extends TestCase {

	/**
	 * The command under test.
	 *
	 * @var RestoreCommand
	 */
	private $command;

	/**
	 * Sets up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();
		\WP_CLI::reset();
		$this->command = new RestoreCommand();
	}

	/**
	 * Tears down the test fixture.
	 */
	protected function tearDown(): void {
		$this->resetPropertyValue( 'instance', Bulk::class );
		parent::tearDown();
	}

	/**
	 * Injects a stub into Bulk::$instance with controlled run_restore() outcomes.
	 *
	 * @param array $result_map Keyed by internal context string (e.g. 'wp', 'custom-folders').
	 */
	private function injectBulkStub( array $result_map ): void {
		$stub = new class( $result_map ) {
			/**
			 * Map of context => result array.
			 *
			 * @var array
			 */
			private $map;

			/**
			 * Constructs the stub.
			 *
			 * @param array $map Map of context => result.
			 */
			public function __construct( array $map ) {
				$this->map = $map;
			}

			/**
			 * Returns a controlled restore result for the given context.
			 *
			 * @param string $context The bulk context.
			 *
			 * @return array
			 */
			public function run_restore( string $context ): array {
				return $this->map[ $context ] ?? [
					'success'  => false,
					'message'  => 'no-images',
					'restored' => 0,
					'errors'   => 0,
					'total'    => 0,
				];
			}
		};

		$this->setPropertyValue( 'instance', Bulk::class, $stub );
	}

	/**
	 * Test: get_name() returns the correct WP-CLI command name.
	 *
	 * @covers \Imagify\CLI\RestoreCommand::get_command_name
	 */
	public function testGetName(): void {
		$this->assertSame( 'imagify restore', $this->command->get_name() );
	}

	/**
	 * Test: get_synopsis() returns a single optional repeating positional 'contexts' argument.
	 *
	 * @covers \Imagify\CLI\RestoreCommand::get_synopsis
	 */
	public function testGetSynopsisHasOptionalRepeatingContextsArgument(): void {
		$synopsis = $this->command->get_synopsis();

		$this->assertCount( 1, $synopsis );
		$this->assertSame( 'positional', $synopsis[0]['type'] );
		$this->assertSame( 'contexts', $synopsis[0]['name'] );
		$this->assertTrue( $synopsis[0]['optional'] );
		$this->assertTrue( $synopsis[0]['repeating'] );
	}

	/**
	 * Test: no arguments defaults to restoring both 'wp' and 'custom-folders' and emits success.
	 *
	 * @covers \Imagify\CLI\RestoreCommand::__invoke
	 */
	public function testDefaultsToAllContextsWhenNoArgumentsGiven(): void {
		$this->injectBulkStub(
			[
				'wp'             => [
					'success'  => true,
					'message'  => 'success',
					'restored' => 3,
					'errors'   => 0,
					'total'    => 3,
				],
				'custom-folders' => [
					'success'  => true,
					'message'  => 'success',
					'restored' => 2,
					'errors'   => 0,
					'total'    => 2,
				],
			]
		);

		( $this->command )( [], [] );

		$this->assertNotEmpty( \WP_CLI::$success_messages );
		$this->assertStringContainsString( '5', \WP_CLI::$success_messages[0] );
		$this->assertEmpty( \WP_CLI::$warning_messages );
		$this->assertEmpty( \WP_CLI::$error_messages );
	}

	/**
	 * Test: 'library' argument maps to the internal 'wp' context.
	 *
	 * @covers \Imagify\CLI\RestoreCommand::__invoke
	 */
	public function testLibraryArgumentMapsToWpContext(): void {
		$this->injectBulkStub(
			[
				'wp' => [
					'success'  => true,
					'message'  => 'success',
					'restored' => 1,
					'errors'   => 0,
					'total'    => 1,
				],
			]
		);

		( $this->command )( [ 'library' ], [] );

		$this->assertNotEmpty( \WP_CLI::$success_messages );
		$this->assertStringContainsString( '1', \WP_CLI::$success_messages[0] );
		$this->assertEmpty( \WP_CLI::$warning_messages );
	}

	/**
	 * Test: 'custom-folders' argument is passed through to Bulk::run_restore() as-is.
	 *
	 * @covers \Imagify\CLI\RestoreCommand::__invoke
	 */
	public function testCustomFoldersArgumentPassedThrough(): void {
		$this->injectBulkStub(
			[
				'custom-folders' => [
					'success'  => true,
					'message'  => 'success',
					'restored' => 2,
					'errors'   => 0,
					'total'    => 2,
				],
			]
		);

		( $this->command )( [ 'custom-folders' ], [] );

		$this->assertNotEmpty( \WP_CLI::$success_messages );
		$this->assertEmpty( \WP_CLI::$warning_messages );
	}

	/**
	 * Test: when no optimized media exist a warning is emitted and no success message.
	 *
	 * @covers \Imagify\CLI\RestoreCommand::__invoke
	 */
	public function testEmitsWarningWhenNoMediaToRestore(): void {
		$this->injectBulkStub(
			[
				'wp'             => [
					'success'  => false,
					'message'  => 'no-images',
					'restored' => 0,
					'errors'   => 0,
					'total'    => 0,
				],
				'custom-folders' => [
					'success'  => false,
					'message'  => 'no-images',
					'restored' => 0,
					'errors'   => 0,
					'total'    => 0,
				],
			]
		);

		( $this->command )( [], [] );

		$this->assertNotEmpty( \WP_CLI::$warning_messages );
		$this->assertEmpty( \WP_CLI::$success_messages );
	}

	/**
	 * Test: an invalid context emits an error and aborts without success.
	 *
	 * @covers \Imagify\CLI\RestoreCommand::__invoke
	 */
	public function testEmitsErrorForInvalidContext(): void {
		( $this->command )( [ 'invalid-context' ], [] );

		$this->assertNotEmpty( \WP_CLI::$error_messages );
		$this->assertStringContainsString( 'invalid-context', \WP_CLI::$error_messages[0] );
		$this->assertEmpty( \WP_CLI::$success_messages );
	}

	/**
	 * Test: when some restores fail a warning is used instead of success.
	 *
	 * @covers \Imagify\CLI\RestoreCommand::__invoke
	 */
	public function testEmitsWarningWhenRestoreHasPartialErrors(): void {
		$this->injectBulkStub(
			[
				'wp' => [
					'success'  => true,
					'message'  => 'success',
					'restored' => 2,
					'errors'   => 1,
					'total'    => 3,
				],
			]
		);

		( $this->command )( [ 'library' ], [] );

		$this->assertNotEmpty( \WP_CLI::$warning_messages );
		$this->assertEmpty( \WP_CLI::$success_messages );
	}
}
