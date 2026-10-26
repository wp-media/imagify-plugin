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

	/**
	 * Stubs imagify_get_context() so only the given "context:describer" pairs are allowed.
	 *
	 * @param string[] $allowed Allowed pairs, e.g. [ 'wp:manage', 'wp:manual-restore' ].
	 * @return void
	 */
	private function allow( array $allowed ): void {
		Functions\when( 'imagify_get_context' )->alias(
			function ( $name ) use ( $allowed ) {
				return new class( $name, $allowed ) {
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
	}

	public function testReturnsFalseWhenUserCannotManage(): void {
		$this->allow( [ 'wp:manual-restore' ] );

		$this->assertFalse( $this->make_ability( 'wp' )->check_permissions( [ 'media_id' => 12 ] ) );
	}

	public function testReturnsTrueWhenUserCanRestoreLibraryMedia(): void {
		$this->allow( [ 'wp:manage', 'wp:manual-restore' ] );

		$this->assertTrue( $this->make_ability( 'wp' )->check_permissions( [ 'media_id' => 12 ] ) );
	}

	public function testReturnsTrueWhenUserCanRestoreCustomFolderFile(): void {
		$this->allow( [ 'wp:manage', 'custom-folders:manual-restore' ] );

		$this->assertTrue( $this->make_ability( 'custom-folders' )->check_permissions( [ 'media_id' => 12 ] ) );
	}

	/**
	 * A user allowed on the media library is still denied a custom-folder file
	 * when the custom-folders capability is not granted (e.g. a subsite admin).
	 */
	public function testReturnsFalseWhenUserCannotRestoreCustomFolderFile(): void {
		$this->allow( [ 'wp:manage', 'wp:manual-restore' ] );

		$this->assertFalse( $this->make_ability( 'custom-folders' )->check_permissions( [ 'media_id' => 12 ] ) );
	}

	/**
	 * When nothing resolves there is nothing to restore: the manager is let
	 * through so execute() can return its actionable error.
	 */
	public function testReturnsTrueForManagerWhenMediaDoesNotResolve(): void {
		$this->allow( [ 'wp:manage' ] );

		$this->assertTrue( $this->make_ability( null )->check_permissions( [ 'media_id' => 12 ] ) );
		$this->assertTrue( $this->make_ability( 'custom-folders' )->check_permissions() );
	}
}
