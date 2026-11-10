<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Stubs;

use Brain\Monkey\Functions;
use Imagify\Bulk\Bulk;

/**
 * Mocks shared by the Imagifybeat unit tests.
 */
trait ImagifybeatMocksTrait {
	/**
	 * Load the fixture from the Unit/ subfolder of the fixtures folder, e.g.
	 * Tests/Fixtures/classes/Imagifybeat/Core/Unit/CoreHandlerTest.php: the integration test of the same method has
	 * the same class name, and its fixture sits one folder up.
	 *
	 * @return void
	 */
	protected function loadTestDataConfig() {
		$file    = ( new \ReflectionObject( $this ) )->getFileName();
		$fixture = str_replace( '/Tests/Unit/', '/Tests/Fixtures/', dirname( $file ) ) . '/Unit/' . basename( $file );

		$this->config = is_readable( $fixture ) ? require $fixture : [];
	}

	/**
	 * Mock imagify_get_context(): each context allows the given "context:describer" pairs.
	 * Context names are sanitized, as imagify_get_context() does.
	 * The registered contexts are 'wp', 'custom-folders' and the ones in the allowed pairs (third-party contexts).
	 *
	 * @param string[] $allowed Allowed "context:describer" pairs.
	 */
	protected function mockContexts( array $allowed ): void {
		$this->stubSanitizeKey();

		$names = [ 'wp', 'custom-folders' ];

		foreach ( $allowed as $pair ) {
			$names[] = strtok( $pair, ':' );
		}

		$names = array_values( array_unique( $names ) );
		sort( $names );

		Functions\when( 'imagify_get_context_names' )->justReturn( $names );

		Functions\when( 'imagify_get_context' )->alias(
			function ( $name ) use ( $allowed ) {
				return new ContextStub( sanitize_key( $name ), $allowed );
			}
		);
	}

	/**
	 * Mock sanitize_key() like WordPress does.
	 */
	protected function stubSanitizeKey(): void {
		Functions\when( 'sanitize_key' )->alias(
			function ( $key ) {
				return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
			}
		);
	}

	/**
	 * Replace the Bulk singleton with a stub.
	 *
	 * @return BulkStub
	 */
	protected function stubBulk(): BulkStub {
		$bulk = new BulkStub();

		$this->setPropertyValue( 'instance', Bulk::class, $bulk );

		return $bulk;
	}

	/**
	 * Mock imagify_get_optimization_process(): the user can optimize the given media IDs only.
	 * Locks are read from get_transient(): the given media IDs are locked.
	 *
	 * @param int[] $allowed_ids IDs of the media the user can optimize.
	 * @param int[] $locked_ids  IDs of the media being optimized.
	 */
	protected function mockProcesses( array $allowed_ids, array $locked_ids = [] ): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( true );
		Functions\when( 'imagify_get_optimization_process_class_name' )->justReturn( '\Imagify\Optimization\Process\WP' );
		Functions\when( 'get_transient' )->alias(
			function ( $name ) use ( $locked_ids ) {
				foreach ( $locked_ids as $id ) {
					if ( preg_match( "/^imagify_[a-z-]+_{$id}_process_locked$/", $name ) ) {
						return 1;
					}
				}

				return false;
			}
		);
		Functions\when( 'imagify_get_optimization_process' )->alias(
			function ( $media_id ) use ( $allowed_ids ) {
				return new ProcessStub( (int) $media_id, in_array( (int) $media_id, $allowed_ids, true ) );
			}
		);
	}
}
