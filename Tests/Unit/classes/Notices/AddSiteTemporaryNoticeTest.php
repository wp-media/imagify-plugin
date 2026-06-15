<?php
declare( strict_types=1 );

namespace Imagify\Tests\Unit\classes\Notices;

use Brain\Monkey\Functions;
use Imagify\Notices\Notices;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Notices\Notices::add_site_temporary_notice().
 *
 * @covers \Imagify\Notices\Notices::add_site_temporary_notice
 * @group  Notices
 */
class AddSiteTemporaryNoticeTest extends TestCase {

	/**
	 * Instance under test.
	 *
	 * @var Notices
	 */
	private $notices;

	/**
	 * Set up test instance.
	 */
	public function setUp(): void {
		parent::setUp();

		// Reset the singleton instance before each test.
		$ref = new \ReflectionProperty( Notices::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );

		$this->notices = Notices::get_instance();
	}

	/**
	 * Test that adding the same message twice results in only one notice being stored.
	 */
	public function testShouldNotAddDuplicateMessage() {
		$stored = [];

		Functions\when( 'get_transient' )->justReturn( false );

		Functions\expect( 'set_transient' )
			->once()
			->andReturnUsing(
				function ( $transient, $value ) use ( &$stored ) {
					$stored = $value;
					return true;
				}
			);

		$this->notices->add_site_temporary_notice( 'The .htaccess file is not writable.' );

		// Second call with the same message — set_transient should NOT be called again.
		Functions\when( 'get_transient' )->justReturn( $stored );

		$this->notices->add_site_temporary_notice( 'The .htaccess file is not writable.' );

		$this->assertCount( 1, $stored );
		$this->assertSame( 'The .htaccess file is not writable.', $stored[0]['message'] );
	}

	/**
	 * Test that two distinct messages are both stored.
	 */
	public function testShouldAddDistinctMessages() {
		$call_count = 0;
		$first_stored = [];
		$second_stored = [];

		Functions\expect( 'get_transient' )
			->twice()
			->andReturnUsing(
				function () use ( &$call_count, &$first_stored ) {
					++$call_count;
					if ( 1 === $call_count ) {
						return false;
					}
					return $first_stored;
				}
			);

		Functions\expect( 'set_transient' )
			->twice()
			->andReturnUsing(
				function ( $transient, $value ) use ( &$call_count, &$first_stored, &$second_stored ) {
					if ( 1 === $call_count ) {
						$first_stored = $value;
					} else {
						$second_stored = $value;
					}
					return true;
				}
			);

		$this->notices->add_site_temporary_notice( 'First notice message.' );
		$this->notices->add_site_temporary_notice( 'Second notice message.' );

		$this->assertCount( 2, $second_stored );
	}

	/**
	 * Test that a notice with an empty message is not stored.
	 */
	public function testShouldNotAddNoticeWithEmptyMessage() {
		Functions\when( 'get_transient' )->justReturn( false );

		// set_transient should never be called because message is empty.
		Functions\expect( 'set_transient' )->never();

		$this->notices->add_site_temporary_notice( [ 'type' => 'error' ] );
	}

	/**
	 * Test that a string notice_data is wrapped into an array with 'message' key.
	 */
	public function testShouldAcceptStringNoticeData() {
		$stored = [];

		Functions\when( 'get_transient' )->justReturn( false );

		Functions\expect( 'set_transient' )
			->once()
			->andReturnUsing(
				function ( $transient, $value ) use ( &$stored ) {
					$stored = $value;
					return true;
				}
			);

		$this->notices->add_site_temporary_notice( 'My notice message.' );

		$this->assertCount( 1, $stored );
		$this->assertSame( 'My notice message.', $stored[0]['message'] );
	}
}
