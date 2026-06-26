<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\MCP\ServiceProvider;

use Imagify\Abilities\BulkOptimize;
use Imagify\Abilities\GetAccount;
use Imagify\Abilities\GetMediaStatus;
use Imagify\Abilities\GetNextgenCoverage;
use Imagify\Abilities\GetSettings;
use Imagify\Abilities\GetStats;
use Imagify\Abilities\OptimizeMedia;
use Imagify\Abilities\RestoreMedia;
use Imagify\Abilities\UpdateSettings;
use Imagify\MCP\AbilitiesSubscriber;
use Imagify\MCP\ConfigSubscriber;
use Imagify\MCP\ServiceProvider;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests for \Imagify\MCP\ServiceProvider::get_subscribers() and provides().
 *
 * These tests live in the Integration suite because ServiceProvider extends
 * Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider
 * (a Strauss-prefixed vendored class). The Unit bootstrap uses a hand-listed
 * file set — NOT the full vendor autoloader — so the prefixed base class is not
 * available there. The Integration suite bootstraps the full WP environment with
 * the complete autoloader, making the class resolvable.
 *
 * @covers \Imagify\MCP\ServiceProvider::get_subscribers
 * @covers \Imagify\MCP\ServiceProvider::provides
 * @group  MCP
 */
class Test_GetSubscribers extends TestCase {

	/**
	 * Whether to use the Imagify API for these tests.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Tests that get_subscribers() returns ConfigSubscriber and AbilitiesSubscriber.
	 */
	public function testReturnsConfigAndAbilitiesSubscribers(): void {
		$provider    = new ServiceProvider();
		$subscribers = $provider->get_subscribers();

		$this->assertContains( ConfigSubscriber::class, $subscribers );
		$this->assertContains( AbilitiesSubscriber::class, $subscribers );
	}

	/**
	 * Tests that get_subscribers() returns exactly two subscribers.
	 */
	public function testReturnsTwoSubscribers(): void {
		$provider    = new ServiceProvider();
		$subscribers = $provider->get_subscribers();

		$this->assertCount( 2, $subscribers );
	}

	/**
	 * Tests that provides() returns true for ConfigSubscriber.
	 */
	public function testProvidesTrueForConfigSubscriber(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( ConfigSubscriber::class ) );
	}

	/**
	 * Tests that provides() returns true for AbilitiesSubscriber.
	 */
	public function testProvidesTrueForAbilitiesSubscriber(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( AbilitiesSubscriber::class ) );
	}

	/**
	 * Tests that provides() returns true for GetNextgenCoverage.
	 */
	public function testProvidesTrueForGetNextgenCoverage(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( GetNextgenCoverage::class ) );
	}

	/**
	 * Tests that provides() returns true for GetStats.
	 */
	public function testProvidesTrueForGetStats(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( GetStats::class ) );
	}

	/**
	 * Tests that provides() returns true for UpdateSettings.
	 */
	public function testProvidesTrueForUpdateSettings(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( UpdateSettings::class ) );
	}

	/**
	 * Tests that provides() returns true for GetMediaStatus.
	 */
	public function testProvidesTrueForGetMediaStatus(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( GetMediaStatus::class ) );
	}

	/**
	 * Tests that provides() returns false for an unknown service.
	 */
	public function testProvidesFalseForUnknownService(): void {
		$provider = new ServiceProvider();

		$this->assertFalse( $provider->provides( 'some_unknown_service' ) );
	}

	/**
	 * Tests that provides() returns true for GetAccount.
	 */
	public function testProvidesTrueForGetAccount(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( GetAccount::class ) );
	}

	/**
	 * Tests that provides() returns true for OptimizeMedia.
	 */
	public function testProvidesTrueForOptimizeMedia(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( OptimizeMedia::class ) );
	}

	/**
	 * Tests that provides() returns true for GetSettings.
	 */
	public function testProvidesTrueForGetSettings(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( GetSettings::class ) );
	}

	/**
	 * Tests that provides() returns true for BulkOptimize.
	 */
	public function testProvidesTrueForBulkOptimize(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( BulkOptimize::class ) );
	}

	/**
	 * Tests that provides() returns true for RestoreMedia.
	 */
	public function testProvidesTrueForRestoreMedia(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( RestoreMedia::class ) );
	}
}
