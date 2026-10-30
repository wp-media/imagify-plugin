<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\RestoreMedia;

use Brain\Monkey\Functions;
use Imagify\Abilities\RestoreMedia;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\RestoreMedia::check_permissions().
 *
 * The `manage` capability in the `wp` context is required, plus `manual-restore`
 * in the context the requested media belongs to.
 *
 * @covers \Imagify\Abilities\RestoreMedia::check_permissions
 * @group  MCP
 */
class Test_CheckPermissions extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		// MediaResolver builds translated WP_Error messages when no identifier resolves.
		Functions\stubTranslationFunctions();
	}

	/**
	 * Test: the permission result for the given media context, input and allowed capabilities.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The context of the resolved media (null when nothing resolves), the input and the allowed "context:describer" pairs.
	 * @param bool  $expected The permission result.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		Functions\when( 'imagify_get_context' )->alias(
			function ( $name ) use ( $config ) {
				return new class( $name, $config['allowed'] ) {
					private $name;
					private $allowed;

					public function __construct( $name, array $allowed ) {
						$this->name    = $name;
						$this->allowed = $allowed;
					}

					public function current_user_can( $describer, $media_id = null ): bool {
						return in_array( "{$this->name}:{$describer}", $this->allowed, true );
					}
				};
			}
		);

		$ability = $this->make_ability( $config['media_context'] );

		$this->assertSame( $expected, $ability->check_permissions( $config['input'] ) );
	}

	/**
	 * Builds a RestoreMedia instance whose get_process() returns a process for
	 * a media in the given context, or null.
	 *
	 * @param string|null $media_context Context of the resolved media, or null when nothing resolves.
	 * @return RestoreMedia
	 */
	private function make_ability( ?string $media_context ): RestoreMedia {
		$process = null;

		if ( null !== $media_context ) {
			$media = new class( $media_context ) {
				private $context;

				public function __construct( string $context ) {
					$this->context = $context;
				}

				public function get_context(): string {
					return $this->context;
				}
			};

			$process = new class( $media ) {
				private $media;

				public function __construct( $media ) {
					$this->media = $media;
				}

				public function get_media() {
					return $this->media;
				}
			};
		}

		return new class( $process ) extends RestoreMedia {
			/** @var mixed */
			private $process;

			public function __construct( $process ) {
				$this->process = $process;
			}

			protected function get_process( int $media_id ) {
				return $this->process;
			}
		};
	}
}
