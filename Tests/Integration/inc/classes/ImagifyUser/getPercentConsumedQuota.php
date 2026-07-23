<?php

namespace Imagify\Tests\Integration\inc\classes\ImagifyUser;

use Brain\Monkey\Functions;
use Imagify;
use Imagify\User\User;
use Imagify_Data;

/**
 * @covers \Imagify\User\User::get_percent_consumed_quota
 * @group  ImagifyAPI
 */
class Test_GetPercentConsumedQuota extends TestCase {
	private $originalPreviousQuotaOption;

	public function set_up() {
		parent::set_up();

		$option = get_option( 'imagify_data', [] );
		$this->originalPreviousQuotaOption = isset( $option['previous_quota_percent'] ) ? $option['previous_quota_percent'] : 0.0;
	}

	public function tear_down() {
		// Restore the original option.
		$option = get_option( 'imagify_data', [] );
		$option['previous_quota_percent'] = $this->originalPreviousQuotaOption;
		update_option( 'imagify_data', $option );

		parent::tear_down();
	}

	public function testShouldReturnZeroWhenCouldNotFetchUserData() {
		update_imagify_option( 'api_key', $this->invalidApiKey );

		// Verify the static $user property is null.
		$this->assertNull( $this->getNonPublicPropertyValue( 'user', Imagify::class ) );

		$this->assertSame( 0, ( new User() )->get_percent_consumed_quota() );
	}

	public function testShouldReturnQuotaWhenFetchedUserData() {
		if ( ! $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) ) {
			$this->markTestSkipped( 'IMAGIFY_TESTS_API_KEY not set; requires a valid live API key.' );
		}

		update_imagify_option( 'api_key', $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) );

		// Verify the static $user property is null.
		$this->assertNull( $this->getNonPublicPropertyValue( 'user', Imagify::class ) );

		$option = get_option( 'imagify_data', [] );
		$option['previous_quota_percent'] = 100.0; // Set previous quota to 100%.
		update_option( 'imagify_data', $option );

		$newQuota = ( new User() )->get_percent_consumed_quota();

		$this->assertNotSame( 0, $newQuota );
	}
}
