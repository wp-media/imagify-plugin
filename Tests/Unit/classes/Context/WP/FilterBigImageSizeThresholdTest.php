<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Context\WP;

use Brain\Monkey\Functions;
use Imagify\Context\WP;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Context\WP::filter_big_image_size_threshold() — WordPress 7.1 switches
 * its own downscaling off while the browser handles the upload, because the browser supplies
 * the scaled file itself. Overriding that leaves a conflicting "-scaled" file behind and
 * points `original_image` at it, so a `false` threshold has to be handed back untouched.
 *
 * @covers \Imagify\Context\WP::filter_big_image_size_threshold
 * @group  ContextWP
 * @since  2.3.3
 */
class FilterBigImageSizeThresholdTest extends TestCase {

	/**
	 * Test: a false threshold is returned untouched, so core's opt out wins.
	 */
	public function testReturnsFalseUntouchedWhenCoreDisabledResizing(): void {
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\expect( 'get_imagify_option' )->never();

		$this->assertFalse( ( new WP() )->filter_big_image_size_threshold( false, [ 3800, 2500 ], '/tmp/big.jpg', 123 ) );
	}

	/**
	 * Test: the attachment is flagged so the later, asynchronous optimization does not resize it.
	 */
	public function testFlagsTheAttachmentWhenCoreDisabledResizing(): void {
		$stored = [];

		Functions\when( 'set_transient' )->alias(
			function ( $name, $value ) use ( &$stored ) {
				$stored[ $name ] = $value;
				return true;
			}
		);

		( new WP() )->filter_big_image_size_threshold( false, [ 3800, 2500 ], '/tmp/big.jpg', 123 );

		$this->assertSame( [ 'imagify_client_side_scaled_123' => 1 ], $stored );
	}

	/**
	 * Test: nothing is flagged when no attachment ID is supplied, as happens when the value
	 * is read outside of an upload.
	 */
	public function testDoesNotFlagWithoutAnAttachmentId(): void {
		Functions\expect( 'set_transient' )->never();

		$this->assertFalse( ( new WP() )->filter_big_image_size_threshold( false ) );
	}

	/**
	 * Test: Imagify's own resizing value is still applied when core did not opt out.
	 */
	public function testReturnsImagifyThresholdWhenResizingIsEnabled(): void {
		Functions\when( 'get_imagify_option' )->alias(
			function ( $option ) {
				return 'resize_larger' === $option ? 1 : 1200;
			}
		);

		$this->assertSame( 1200, ( new WP() )->filter_big_image_size_threshold( 2560, [ 3800, 2500 ], '/tmp/big.jpg', 123 ) );
	}

	/**
	 * Test: with the setting off, the threshold is 0 and WordPress skips its own resizing.
	 */
	public function testReturnsZeroWhenResizingIsDisabled(): void {
		Functions\when( 'get_imagify_option' )->justReturn( 0 );

		$this->assertSame( 0, ( new WP() )->filter_big_image_size_threshold( 2560, [ 3800, 2500 ], '/tmp/big.jpg', 123 ) );
	}
}
