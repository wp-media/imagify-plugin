<?php

namespace Imagify\Tests\Integration\inc\classes\ImagifyUser;

use Brain\Monkey\Functions;
use Imagify;
use Imagify_Data;
use Imagify_User;

/**
 * @covers Imagify_User::get_percent_consumed_quota
 * @group  ImagifyAPI
 */
class Test_GetPercentConsumedQuota extends TestCase {
	private $originalPreviousQuotaOption;

	public function setUp() {
		parent::setUp();

		$this->originalPreviousQuotaOption = get_imagify_option( 'previous_quota_percent' );
	}

	public function tearDown() {
		parent::tearDown();

		// Restore the original option.
		update_imagify_option( 'previous_quota_percent', $this->originalPreviousQuotaOption );
	}

	public function testShouldReturnZeroWhenCouldNotFetchUserData() {
		update_imagify_option( 'api_key', $this->invalidApiKey );

		// Verify the static $user property is null.
		$this->assertNull( $this->getPropertyValue( 'user', Imagify::class ) );

		Functions\expect( 'imagify_round_half_five' )->never();

		$this->assertSame( 0, ( new Imagify_User() )->get_percent_consumed_quota() );
	}

	public function testShouldReturnQuotaWhenFetchedUserData() {
		update_imagify_option( 'api_key', $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) );

		// Verify the static $user property is null.
		$this->assertNull( $this->getPropertyValue( 'user', Imagify::class ) );

		Imagify_Data::get_instance()->set( 'previous_quota_percent', 100.0 ); // Previous quota was 100%.

		$newQuota = ( new Imagify_User() )->get_percent_consumed_quota();

		$this->assertNotSame( 0, $newQuota );

		if ( 100.0 !== $newQuota ) {
			// Since the new quota is not 100%, the new value must have been saved:
			$this->assertSame( $newQuota, Imagify_Data::get_instance()->get( 'previous_quota_percent' ) );
		}
	}
}
