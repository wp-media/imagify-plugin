<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\CLI;

use Imagify\Bulk\Bulk;
use Imagify\CLI\GenerateMissingNextgenCommand;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\CLI\GenerateMissingNextgenCommand.
 *
 * @covers \Imagify\CLI\GenerateMissingNextgenCommand
 * @group  GenerateMissingNextgenCommand
 * @since  2.3
 */
class GenerateMissingNextgenCommandTest extends TestCase {

	/**
	 * The command under test.
	 *
	 * @var GenerateMissingNextgenCommand
	 */
	private $command;

	/**
	 * Sets up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();
		\WP_CLI::reset();
		$this->command = new GenerateMissingNextgenCommand();
	}

	/**
	 * Tears down the test fixture.
	 */
	protected function tearDown(): void {
		$this->resetPropertyValue( 'instance', Bulk::class );
		parent::tearDown();
	}

	/**
	 * Injects a stub into Bulk::$instance that captures run_generate_nextgen() arguments.
	 *
	 * @return object The injected stub (for argument inspection).
	 */
	private function injectBulkStub(): object {
		$stub = new class() {
			/**
			 * Last $contexts argument received by run_generate_nextgen().
			 *
			 * @var array|null
			 */
			public $received_contexts = null;

			/**
			 * Last $formats argument received by run_generate_nextgen().
			 *
			 * @var array|null
			 */
			public $received_formats = null;

			/**
			 * Records the arguments and returns a success result.
			 *
			 * @param array $contexts The bulk contexts.
			 * @param array $formats  The next-gen formats to generate.
			 *
			 * @return array
			 */
			public function run_generate_nextgen( array $contexts, array $formats ): array {
				$this->received_contexts = $contexts;
				$this->received_formats  = $formats;

				return [
					'success' => true,
					'message' => 1,
				];
			}
		};

		$this->setPropertyValue( 'instance', Bulk::class, $stub );

		return $stub;
	}

	/**
	 * Test: get_name() returns the correct WP-CLI command name.
	 *
	 * @covers \Imagify\CLI\GenerateMissingNextgenCommand::get_command_name
	 */
	public function testGetName(): void {
		$this->assertSame( 'imagify generate-missing-nextgen', $this->command->get_name() );
	}

	/**
	 * Test: get_synopsis() returns a single required repeating positional 'contexts' argument.
	 *
	 * @covers \Imagify\CLI\GenerateMissingNextgenCommand::get_synopsis
	 */
	public function testGetSynopsisHasRequiredRepeatingContextsArgument(): void {
		$synopsis = $this->command->get_synopsis();

		$this->assertCount( 1, $synopsis );
		$this->assertSame( 'positional', $synopsis[0]['type'] );
		$this->assertSame( 'contexts', $synopsis[0]['name'] );
		$this->assertFalse( $synopsis[0]['optional'] );
		$this->assertTrue( $synopsis[0]['repeating'] );
	}

	/**
	 * Test: __invoke() passes both $contexts and a non-empty $formats to run_generate_nextgen().
	 *
	 * This is the core regression test for the ArgumentCountError introduced when the
	 * required $formats argument was omitted from the run_generate_nextgen() call.
	 *
	 * @covers \Imagify\CLI\GenerateMissingNextgenCommand::__invoke
	 */
	public function testPassesContextsAndFormatsToRunGenerateNextgen(): void {
		$stub = $this->injectBulkStub();

		( $this->command )( [ 'wp', 'custom-folders' ], [] );

		$this->assertSame( [ 'wp', 'custom-folders' ], $stub->received_contexts );
		$this->assertNotEmpty( $stub->received_formats );
	}

	/**
	 * Test: __invoke() emits a log message after triggering generation.
	 *
	 * @covers \Imagify\CLI\GenerateMissingNextgenCommand::__invoke
	 */
	public function testEmitsLogMessageOnSuccess(): void {
		$this->injectBulkStub();

		( $this->command )( [ 'wp' ], [] );

		$this->assertNotEmpty( \WP_CLI::$log_messages );
	}
}
