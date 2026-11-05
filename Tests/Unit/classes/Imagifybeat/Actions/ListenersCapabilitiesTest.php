<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Actions;

use Brain\Monkey\Functions;
use Imagify\Bulk\Bulk;
use Imagify\Imagifybeat\Actions;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for the capability and input checks of the Imagifybeat listeners.
 *
 * @covers \Imagify\Imagifybeat\Actions
 * @group  Imagifybeat
 */
class ListenersCapabilitiesTest extends TestCase {
	/**
	 * Contexts passed to the stubbed Bulk::get_bulk_instance().
	 *
	 * @var string[]
	 */
	private $bulk_contexts = [];

	/**
	 * Stub the Bulk singleton and the helpers used by the listeners.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->bulk_contexts = [];

		$bulk_contexts = &$this->bulk_contexts;

		$this->setPropertyValue(
			'instance',
			Bulk::class,
			new class( $bulk_contexts ) {
				private $contexts;

				public function __construct( array &$contexts ) {
					$this->contexts = &$contexts;
				}

				public function get_bulk_instance( string $context ) {
					$this->contexts[] = $context;

					return new class( $context ) {
						private $context;

						public function __construct( string $context ) {
							$this->context = $context;
						}

						public function get_context_data() {
							return [ 'context' => $this->context ];
						}

						public function get_optimized_media_ids_without_format( $format ) {
							return [ 'ids' => [ 1, 2 ] ];
						}
					};
				}
			}
		);

		Functions\when( 'sanitize_key' )->alias(
			function ( $key ) {
				return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
			}
		);
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( true );
	}

	/**
	 * Restore the Bulk singleton.
	 */
	protected function tearDown(): void {
		$this->resetPropertyValue( 'instance', Bulk::class );

		parent::tearDown();
	}

	/**
	 * Test: requirements are only sent to users of the settings or bulk optimization pages.
	 */
	public function testShouldNotSendRequirementsWithoutCapability(): void {
		$this->mockContexts( [ 'wp:optimize', 'custom-folders:optimize' ] );

		$response = Actions::get_instance()->add_requirements_to_response( [ 'a' => 1 ], [ 'imagify_requirements' => 1 ] );

		$this->assertSame( [ 'a' => 1 ], $response );
	}

	/**
	 * Test: bulk stats are only computed for the folder types the user can bulk-optimize.
	 */
	public function testShouldOnlySendBulkStatsForAllowedContexts(): void {
		$this->mockContexts( [ 'wp:bulk-optimize' ] );

		Functions\expect( 'imagify_get_bulk_stats' )
			->once()
			->with( [ 'library|wp' => 0 ], [ 'fullset' => true ] )
			->andReturn( [ 'stats' => 1 ] );

		$response = Actions::get_instance()->add_bulk_optimization_stats_to_response(
			[],
			[ 'imagify_bulk_optimization_stats' => [ 'library|wp', 'custom-folders|custom-folders', 'nopipe', [ 'library|wp' ] ] ]
		);

		$this->assertSame( [ 'imagify_bulk_optimization_stats' => [ 'stats' => 1 ] ], $response );
	}

	/**
	 * Test: no bulk stats at all when the user can't bulk-optimize any requested folder type.
	 */
	public function testShouldNotSendBulkStatsWithoutCapability(): void {
		$this->mockContexts( [ 'wp:optimize' ] );

		Functions\expect( 'imagify_get_bulk_stats' )->never();

		$actions = Actions::get_instance();

		$this->assertSame( [], $actions->add_bulk_optimization_stats_to_response( [], [ 'imagify_bulk_optimization_stats' => [ 'library|wp' ] ] ) );
		$this->assertSame( [], $actions->add_bulk_optimization_stats_to_response( [], [ 'imagify_bulk_optimization_stats' => 'library|wp' ] ) );
	}

	/**
	 * Test: the bulk status only uses valid contexts the user can bulk-optimize.
	 */
	public function testShouldOnlySendBulkStatusForAllowedContexts(): void {
		$this->mockContexts( [ 'wp:bulk-optimize' ] );

		$data = [
			'imagify_bulk_optimization_status' => [
				'not-an-array',
				[ 'groupID' => 'library' ],
				[ 'context' => [ 'wp' ] ],
				[ 'context' => '' ],
				[ 'context' => 'custom-folders' ],
				[ 'context' => '../bogus' ],
				[
					'groupID' => 'library',
					'context' => 'WP',
				],
			],
		];

		$response = Actions::get_instance()->add_bulk_optimization_status_to_response( [], $data );

		$this->assertSame( [ 'wp' ], $this->bulk_contexts );
		$this->assertSame( [ 'wp' => [ 'context' => 'wp' ] ], $response['imagify_bulk_optimization_status']['groups_data'] );
	}

	/**
	 * Test: no bulk status when no requested context is allowed.
	 */
	public function testShouldNotSendBulkStatusWithoutCapability(): void {
		$this->mockContexts( [] );

		$response = Actions::get_instance()->add_bulk_optimization_status_to_response(
			[],
			[ 'imagify_bulk_optimization_status' => [ [ 'context' => 'wp' ] ] ]
		);

		$this->assertSame( [], $response );
		$this->assertSame( [], $this->bulk_contexts );
	}

	/**
	 * Test: the settings page progress is only sent to users who can manage Imagify, ignoring invalid contexts.
	 */
	public function testShouldSendOptionsStatusToManagersOnly(): void {
		Functions\when( 'get_transient' )->justReturn( 7 );
		Functions\when( 'get_imagify_option' )->justReturn( 'webp' );

		$data = [ 'imagify_options_optimization_status' => [ 'wp', [ 'wp' ], '', 'Custom-Folders' ] ];

		$this->mockContexts( [ 'wp:bulk-optimize' ] );
		$this->assertSame( [], Actions::get_instance()->add_options_optimization_status_to_response( [], $data ) );

		$this->mockContexts( [ 'wp:manage' ] );
		$response = Actions::get_instance()->add_options_optimization_status_to_response( [], $data );

		$this->assertSame( [ 'wp', 'custom-folders' ], $this->bulk_contexts );
		$this->assertSame(
			[
				'remaining' => 4,
				'total'     => 7,
			],
			$response['imagify_options_optimization_status']
		);
	}

	/**
	 * Test: the library status only contains media the user can optimize, and no key at all otherwise.
	 */
	public function testShouldOnlySendLibraryStatusOfAllowedMedia(): void {
		$this->mockContexts( [] );
		$this->mockProcesses( [ 5 ] );

		Functions\when( 'get_imagify_media_column_content' )->justReturn( 'html' );

		$actions = Actions::get_instance();

		$response = $actions->add_library_optimization_status_to_response(
			[],
			[ 'imagify_library_optimization_status' => [ 'wp' => [ '_5' => 1, '_6' => 1, '_9999' => 1 ] ] ] // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		);

		$this->assertSame( [ 'imagify_library_optimization_status' => [ 'wp_5' => 'html' ] ], $response );

		$response = $actions->add_library_optimization_status_to_response(
			[],
			[ 'imagify_library_optimization_status' => [ 'wp' => [ '_6' => 1, '_9999' => 1 ] ] ] // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		);

		$this->assertSame( [], $response, 'Forbidden and missing media must leave no trace in the response.' );
	}

	/**
	 * Test: the "Other Media" status needs the custom folders capability, and only accepts the custom-folders context.
	 */
	public function testShouldGuardCustomFoldersStatus(): void {
		Functions\expect( 'imagify_get_optimization_process' )->never();

		$data = [ 'imagify_custom_folders_optimization_status' => [ 'custom-folders' => [ '_1' => 1 ] ] ];

		$this->mockContexts( [ 'wp:optimize', 'wp:manage' ] );
		$this->assertSame( [], Actions::get_instance()->add_custom_folders_optimization_status_to_response( [], $data ) );

		$this->mockContexts( [ 'custom-folders:optimize' ] );
		$this->assertSame(
			[],
			Actions::get_instance()->add_custom_folders_optimization_status_to_response(
				[],
				[ 'imagify_custom_folders_optimization_status' => [ 'wp' => [ '_5' => 1 ] ] ]
			)
		);
	}

	/**
	 * Mock imagify_get_context(): each context allows the given "context:describer" pairs.
	 *
	 * @param array $allowed The allowed "context:describer" pairs.
	 */
	private function mockContexts( array $allowed ): void {
		Functions\when( 'imagify_get_context' )->alias(
			function ( $name ) use ( $allowed ) {
				$name = preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $name ) );

				return new class( $name, $allowed ) {
					private $name;
					private $allowed;

					public function __construct( $name, array $allowed ) {
						$this->name    = $name;
						$this->allowed = $allowed;
					}

					public function get_name() {
						return $this->name;
					}

					public function is_network_wide() {
						return false;
					}

					public function current_user_can( $describer ): bool {
						return in_array( "{$this->name}:{$describer}", $this->allowed, true );
					}
				};
			}
		);
	}

	/**
	 * Mock imagify_get_optimization_process(): the user can optimize the given media IDs only.
	 *
	 * @param int[] $allowed_ids IDs of the media the user can optimize.
	 */
	private function mockProcesses( array $allowed_ids ): void {
		Functions\when( 'imagify_get_optimization_process_class_name' )->justReturn( '\Imagify\Optimization\Process\WP' );
		Functions\when( 'imagify_get_optimization_process' )->alias(
			function ( $media_id ) use ( $allowed_ids ) {
				return new class( in_array( (int) $media_id, $allowed_ids, true ) ) {
					private $allowed;

					public function __construct( bool $allowed ) {
						$this->allowed = $allowed;
					}

					public function current_user_can( $describer ): bool {
						return 'manual-optimize' === $describer && $this->allowed;
					}
				};
			}
		);
	}
}
