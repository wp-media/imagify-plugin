<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Context\WP;

use Brain\Monkey\Functions;
use Imagify\Context\WP;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Context\WP::filter_big_image_size_threshold() — WordPress 7.1 switches its
 * own downscaling off while the browser handles the upload, because the browser supplies the
 * scaled file itself. Overriding that leaves a conflicting "-scaled" file behind and points
 * `original_image` at it, so the threshold has to be handed back untouched there.
 *
 * Only there: a `false` from anywhere else is still overridden, since nothing produced a scaled
 * file in that case and the image would end up resized by nobody.
 *
 * @covers \Imagify\Context\WP::filter_big_image_size_threshold
 * @covers \Imagify\Context\WP::maybe_flag_client_side_scaling
 * @group  ContextWP
 * @since  2.3.3
 */
class FilterBigImageSizeThresholdTest extends TestCase {

	/**
	 * Stubs the resizing option as enabled with the given width.
	 *
	 * @param int $width Configured resizing width.
	 */
	private function stubResizingOption( int $width ): void {
		Functions\when( 'get_imagify_option' )->alias(
			function ( $option ) use ( $width ) {
				return 'resize_larger' === $option ? 1 : $width;
			}
		);
	}

	/**
	 * Build a request stub returning the given value for the 'generate_sub_sizes' parameter.
	 *
	 * @param  mixed $value Value the parameter should return.
	 * @return Mockery\MockInterface
	 */
	private function requestReturning( $value ) {
		$request = Mockery::mock( 'WP_REST_Request' );
		$request->shouldReceive( 'get_param' )->with( 'generate_sub_sizes' )->andReturn( $value );

		return $request;
	}

	/**
	 * Test: the threshold is handed back untouched for the upload the browser scaled.
	 */
	public function testReturnsFalseUntouchedForABrowserScaledUpload(): void {
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\expect( 'get_imagify_option' )->never();

		$context = new WP();

		// The sequence WordPress produces: the upload is noted, then the filter runs.
		$context->maybe_flag_client_side_scaling( (object) [ 'ID' => 123 ], $this->requestReturning( false ), true );

		$this->assertFalse( $context->filter_big_image_size_threshold( false ) );
	}

	/**
	 * Test: a false from anywhere else is overridden with Imagify's value, as it always was.
	 * Nothing scaled the file in that case, so standing down would leave it unresized.
	 */
	public function testOverridesAFalseThatDidNotComeFromTheBrowserFlow(): void {
		$this->stubResizingOption( 2560 );

		// No upload was noted, so this false came from somewhere else.
		$this->assertSame( 2560, ( new WP() )->filter_big_image_size_threshold( false ) );
	}

	/**
	 * Test: Imagify's own resizing value is applied when core did not opt out.
	 */
	public function testReturnsImagifyThresholdWhenResizingIsEnabled(): void {
		$this->stubResizingOption( 1200 );

		$this->assertSame( 1200, ( new WP() )->filter_big_image_size_threshold( 2560 ) );
	}

	/**
	 * Test: with the setting off, the threshold is 0 and WordPress skips its own resizing.
	 */
	public function testReturnsZeroWhenResizingIsDisabled(): void {
		Functions\when( 'get_imagify_option' )->justReturn( 0 );

		$this->assertSame( 0, ( new WP() )->filter_big_image_size_threshold( 2560 ) );
	}

	/**
	 * Test: the attachment is flagged when WordPress hands the sub sizes to the browser.
	 */
	public function testFlagsTheAttachmentWhenTheBrowserOwnsTheSubsizes(): void {
		$stored = [];

		Functions\when( 'set_transient' )->alias(
			function ( $name, $value ) use ( &$stored ) {
				$stored[ $name ] = $value;
				return true;
			}
		);

		( new WP() )->maybe_flag_client_side_scaling( (object) [ 'ID' => 123 ], $this->requestReturning( false ), true );

		$this->assertSame( [ 'imagify_client_side_scaled_123' => 1 ], $stored );
	}

	/**
	 * Test: nothing is flagged for an ordinary upload, where WordPress builds the sub sizes,
	 * nor when the parameter is absent entirely.
	 */
	public function testDoesNotFlagWhenWordPressBuildsTheSubsizes(): void {
		Functions\expect( 'set_transient' )->never();

		$attachment = (object) [ 'ID' => 123 ];

		( new WP() )->maybe_flag_client_side_scaling( $attachment, $this->requestReturning( true ), true );
		( new WP() )->maybe_flag_client_side_scaling( $attachment, $this->requestReturning( null ), true );
	}

	/**
	 * Test: nothing is flagged when an existing attachment is updated rather than created.
	 */
	public function testDoesNotFlagWhenUpdatingAnAttachment(): void {
		Functions\expect( 'set_transient' )->never();

		( new WP() )->maybe_flag_client_side_scaling( (object) [ 'ID' => 123 ], $this->requestReturning( false ), false );
	}
}
