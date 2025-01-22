<?php

namespace functions\Attachments;

use Imagify\Tests\Unit\TestCase;
use Brain\Monkey\Functions;
use Imagify_DB;
use Mockery;
use ReflectionFunction;
use wpdb;

/**
 * Test class covering inc/functions/attachments::imagify_has_attachments_without_required_metadata
 * @group  HealthCheck
 */
class Test_ImagifyAttachmentMetadata extends TestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once IMAGIFY_PLUGIN_TESTS_FIXTURES_DIR . '/wpdb.php';
	}

	public function setUp(): void {
		parent::setUp();

		$GLOBALS['wpdb'] = $this->wpdb = new wpdb( 'dbuser', 'dbpassword', 'dbname', 'dbhost' );

		$this->wpdb->posts = 'wp_posts';


		require_once IMAGIFY_PLUGIN_ROOT . 'inc/functions/attachments.php';
		require_once IMAGIFY_PLUGIN_ROOT . 'inc/functions/common.php';

		$this->reset_static_variable();
	}

	public function reset_static_variable() {
		$reflection = new ReflectionFunction('imagify_has_attachments_without_required_metadata');
		$closure = $reflection->getClosure();
		$closure->bindTo(null, null);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		$this->reset_static_variable();

		parent::tearDown();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( $config, $expected )
	{
		$this->wpdb->set_var( $expected );

		Functions\expect( 'get_post_stati' )
			->times( 1 )
			->andReturn( $config['statuses'] );

		Functions\expect( 'esc_sql' )->andReturnFirstArg();

		$result = imagify_has_attachments_without_required_metadata();

		if( $expected ) {
			$this->assertTrue( $result );
			return;
		}

		$this->assertFalse( $result );
	}
}
