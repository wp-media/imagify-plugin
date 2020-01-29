<?php
namespace Imagify\tests\Integration\inc\classes\ImagifyUser;

use Brain\Monkey\Functions;
use Imagify\tests\Integration\TestCase;

use Imagify;
use Imagify_Data;
use Imagify_User;

/**
 * Tests for Imagify_User->get_percent_consumed_quota().
 *
 * @covers Imagify_User::get_percent_consumed_quota
 * @group  ImagifyAPI
 */
class Test_GetPercentConsumedQuota extends TestCase {
	protected $api_credentials_config_file = 'imagify-api.php';

	private $invalidApiKey = '1234567890abcdefghijklmnopqrstuvwxyz';

	private $originalImagifyInstance;
	private $originalUserInstance;
	private $originalImagifyInstanceSecureKey;
	private $originalApiKeyOption;
	private $originalPreviousQuotaOption;

	public function setUp() {
		parent::setUp();

		// Store previous state and nullify values.
		$this->originalImagifyInstance = $this->resetPropertyValue( 'instance', Imagify::class );
		$this->originalUserInstance    = $this->resetPropertyValue( 'user', Imagify::class );

		if ( $this->originalImagifyInstance ) {
			$this->originalImagifyInstanceSecureKey = $this->resetPropertyValue( 'secure_key', $this->originalImagifyInstance );
		} else {
			$this->originalImagifyInstanceSecureKey = null;
		}

		$this->originalApiKeyOption        = get_imagify_option( 'api_key' );
		$this->originalPreviousQuotaOption = get_imagify_option( 'previous_quota_percent' );
	}

	public function tearDown() {
		parent::tearDown();

		// Reset state.
		$modifiedImagifyInstance = $this->setPropertyValue( 'instance', Imagify::class, $this->originalImagifyInstance );

		remove_filter( 'http_request_args', [ $modifiedImagifyInstance, 'force_api_key_header' ], IMAGIFY_INT_MAX + 25 );

		$this->setPropertyValue( 'user', Imagify::class, $this->originalUserInstance );

		if ( $this->originalImagifyInstance ) {
			$this->setPropertyValue( 'secure_key', $this->originalImagifyInstance, $this->originalImagifyInstanceSecureKey );
		}

		update_imagify_option( 'api_key', $this->originalApiKeyOption );
		update_imagify_option( 'previous_quota_percent', $this->originalPreviousQuotaOption );
	}

	/**
	 * Test Imagify_User->get_percent_consumed_quota() should return 0 when couldn’t fetch user account data.
	 */
	public function testShouldReturnZeroWhenCouldNotFetchUserData() {
		update_imagify_option( 'api_key', $this->invalidApiKey );

		// Verify the static $user property is null.
		$this->assertNull( $this->getPropertyValue( 'user', Imagify::class ) );

		Functions\expect( 'imagify_round_half_five' )->never();

		$this->assertSame( 0, ( new Imagify_User() )->get_percent_consumed_quota() );
	}

	/**
	 * Test Imagify_User->get_percent_consumed_quota() should return a quota when able to fetch user account data.
	 */
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
