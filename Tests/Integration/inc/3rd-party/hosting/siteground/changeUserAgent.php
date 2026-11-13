<?php

declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\ThirdParty\Hosting\Siteground;

use Imagify\Tests\Integration\TestCase;

/**
 * @covers ::imagify_siteground_change_user_agent
 * @covers ::_imagify_is_siteground
 *
 * @group ThirdParty
 * @group Siteground
 */
class Test_ChangeUserAgent extends TestCase {
	/**
	 * This suite does not need the Imagify API.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Number of times the User-Agent filter has been invoked.
	 *
	 * @var int
	 */
	private $filter_calls = 0;

	/**
	 * Prepares the test environment before each test.
	 */
	public function set_up() {
		parent::set_up();

		$this->filter_calls = 0;
	}

	/**
	 * Forces the SiteGround detection result.
	 *
	 * @param bool $value Detection result.
	 */
	private function forceSiteground( bool $value ): void {
		add_filter(
			'imagify_is_siteground',
			function () use ( $value ) {
				return $value;
			}
		);
	}

	/**
	 * Hooks a User-Agent filter callback returning a fixed value, counting calls.
	 *
	 * @param mixed $value Value to return.
	 */
	private function returnUserAgent( $value ): void {
		add_filter(
			'imagify_user_agent_for_internal_requests',
			function () use ( $value ) {
				++$this->filter_calls;
				return $value;
			}
		);
	}

	/**
	 * Hooks a User-Agent filter callback that only counts calls.
	 */
	private function countUserAgentCalls(): void {
		add_filter(
			'imagify_user_agent_for_internal_requests',
			function ( $user_agent ) {
				++$this->filter_calls;
				return $user_agent;
			}
		);
	}

	/**
	 * Gets request arguments.
	 *
	 * @return array
	 */
	private function getArgs(): array {
		return [ 'user-agent' => 'WordPress/6.9; ' . home_url( '/' ) ];
	}

	/**
	 * Provides loopback URLs targeted by the override.
	 *
	 * @return array
	 */
	public function providerLoopbackUrls(): array {
		return [
			'admin-ajax' => [ admin_url( 'admin-ajax.php' ) ],
			'admin-post' => [ admin_url( 'admin-post.php' ) ],
			'wp-cron'    => [ site_url( 'wp-cron.php' ) ],
		];
	}

	/**
	 * Provides URLs that must be left untouched.
	 *
	 * @return array
	 */
	public function providerIgnoredUrls(): array {
		return [
			'external host'                => [ 'https://example.org/wp-admin/admin-ajax.php' ],
			'host differing from site_url' => [ 'https://custom-background.example.net/wp-admin/admin-ajax.php' ],
			'no host'                      => [ '/wp-admin/admin-ajax.php' ],
			'non matching path'            => [ site_url( 'wp-json/x' ) ],
		];
	}

	/**
	 * Provides filter return values that must be ignored.
	 *
	 * @return array
	 */
	public function providerInvalidFilterValues(): array {
		return [
			'array'      => [ [ 'a' ] ],
			'null'       => [ null ],
			'int'        => [ 123 ],
			'false'      => [ false ],
			'empty'      => [ '' ],
			'whitespace' => [ '  ' ],
			'control'    => [ "\r\n\t" ],
		];
	}

	/**
	 * @dataProvider providerLoopbackUrls
	 */
	public function testShouldKeepUserAgentWhenNotSiteground( string $url ) {
		$this->forceSiteground( false );
		$args = $this->getArgs();

		$this->assertSame( $args, imagify_siteground_change_user_agent( $args, $url ) );
	}

	/**
	 * @dataProvider providerLoopbackUrls
	 */
	public function testShouldUseFilterValueWhenNotSiteground( string $url ) {
		$this->forceSiteground( false );
		$this->returnUserAgent( 'Custom/1.0' );

		$result = imagify_siteground_change_user_agent( $this->getArgs(), $url );

		$this->assertSame( 'Custom/1.0', $result['user-agent'] );
	}

	public function testShouldPassDefaultArgsAndParsedUrlToFilterWhenNotSiteground() {
		$this->forceSiteground( false );
		$args     = $this->getArgs();
		$received = [];

		add_filter(
			'imagify_user_agent_for_internal_requests',
			function ( $user_agent, $r, $url ) use ( &$received ) {
				$received = [ $user_agent, $r, $url ];
				return $user_agent;
			},
			10,
			3
		);

		imagify_siteground_change_user_agent( $args, admin_url( 'admin-ajax.php' ) );

		$this->assertSame( $args['user-agent'], $received[0] );
		$this->assertSame( $args, $received[1] );
		$this->assertIsArray( $received[2] );
		$this->assertArrayHasKey( 'host', $received[2] );
		$this->assertArrayHasKey( 'path', $received[2] );
	}

	/**
	 * @dataProvider providerLoopbackUrls
	 */
	public function testShouldRandomizeUserAgentOnSiteground( string $url ) {
		$this->forceSiteground( true );
		$args = $this->getArgs();

		$result = imagify_siteground_change_user_agent( $args, $url );

		$this->assertNotSame( $args['user-agent'], $result['user-agent'] );
		$this->assertMatchesRegularExpression( '/^[A-Za-z0-9]{12}$/', $result['user-agent'] );
	}

	public function testShouldUseFilterValueOnSiteground() {
		$this->forceSiteground( true );
		$this->returnUserAgent( 'Custom/1.0' );

		$result = imagify_siteground_change_user_agent( $this->getArgs(), admin_url( 'admin-ajax.php' ) );

		$this->assertSame( 'Custom/1.0', $result['user-agent'] );
	}

	/**
	 * @dataProvider providerIgnoredUrls
	 */
	public function testShouldLeaveRequestUntouchedWhenNotAMatchingLoopback( string $url ) {
		$args = $this->getArgs();
		$this->countUserAgentCalls();

		foreach ( [ false, true ] as $is_siteground ) {
			$this->forceSiteground( $is_siteground );

			$this->assertSame( $args, imagify_siteground_change_user_agent( $args, $url ) );
		}

		$this->assertSame( 0, $this->filter_calls );
	}

	public function testShouldTreatUnparsableUrlAsExternal() {
		$args = $this->getArgs();
		$this->countUserAgentCalls();

		$this->assertSame( $args, imagify_siteground_change_user_agent( $args, 'http:///' ) );
		$this->assertSame( 0, $this->filter_calls );
	}

	public function testShouldMatchHostCaseInsensitively() {
		$this->forceSiteground( false );
		$this->returnUserAgent( 'Custom/1.0' );

		$url    = str_replace( wp_parse_url( site_url(), PHP_URL_HOST ), strtoupper( wp_parse_url( site_url(), PHP_URL_HOST ) ), admin_url( 'admin-ajax.php' ) );
		$result = imagify_siteground_change_user_agent( $this->getArgs(), $url );

		$this->assertSame( 'Custom/1.0', $result['user-agent'] );
		$this->assertSame( 1, $this->filter_calls );
	}

	public function testShouldStripControlCharactersFromFilterValue() {
		$this->forceSiteground( false );
		$this->returnUserAgent( "Custom/1.0\r\nX-Injected: 1" );

		$result = imagify_siteground_change_user_agent( $this->getArgs(), admin_url( 'admin-ajax.php' ) );

		$this->assertSame( 'Custom/1.0X-Injected: 1', $result['user-agent'] );
	}

	/**
	 * @dataProvider providerInvalidFilterValues
	 *
	 * @param mixed $value Invalid filter return value.
	 */
	public function testShouldIgnoreInvalidFilterValue( $value ) {
		$this->forceSiteground( false );
		$this->returnUserAgent( $value );
		$args = $this->getArgs();

		$this->assertSame( $args, imagify_siteground_change_user_agent( $args, admin_url( 'admin-ajax.php' ) ) );
	}

	public function testShouldNotWriteUserAgentWhenUnsetAndNoFilterValue() {
		$this->forceSiteground( false );

		$result = imagify_siteground_change_user_agent( [], admin_url( 'admin-ajax.php' ) );

		$this->assertArrayNotHasKey( 'user-agent', $result );
	}

	public function testDetectionShouldFollowFilter() {
		$this->forceSiteground( true );
		$this->assertTrue( _imagify_is_siteground() );

		remove_all_filters( 'imagify_is_siteground' );
		$this->forceSiteground( false );
		$this->assertFalse( _imagify_is_siteground() );
	}

	public function testDetectionShouldBeFalseByDefaultOffSiteground() {
		if ( file_exists( '/etc/yum.repos.d/baseos.repo' ) && file_exists( '/Z' ) ) {
			$this->markTestSkipped( 'Running on a host with the SiteGround markers.' );
		}

		$this->assertFalse( _imagify_is_siteground() );
	}
}
